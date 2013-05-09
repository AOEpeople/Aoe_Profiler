<?php

/**
 * Stack-based profiler
 * (Drop-in replacement for the default Magento profiler)
 *
 * @author Fabrizio Branca
 */
class Varien_Profiler {

	const TYPE_DEFAULT = 'default';
	const TYPE_DEFAULT_NOCHILDREN = 'default-nochildren';
	const TYPE_DATABASE = 'db';
	const TYPE_TEMPLATE = 'template';
	const TYPE_BLOCK = 'block';
	const TYPE_OBSERVER = 'observer';
	const TYPE_EVENT = 'event';
	const TYPE_MODEL = 'model';
	const TYPE_EAVMODEL = 'eavmodel';


	static private $startValues = array();

	static private $stackLevel = 0;
	static private $stack = array();
	static private $stackLevelMax = array();
	static private $stackLog = array();
	static private $uniqueCounter = 0;
	static private $currentPointerStack = array();

	static private $_enabled = false;
	static private $_checkedEnabled = false;

	static private $_logCallStack = false;

	/**
	 * Check if profiler is enabled.
	 *
	 * @static
	 * @return bool
	 */
	public static function isEnabled() {
		if (!self::$_checkedEnabled) {
			self::$_checkedEnabled = true;
			if ((isset($_GET['profile']) && $_GET['profile'] == true)
				|| (isset($_COOKIE['profile']) && $_COOKIE['profile'] == true)) {
				self::enable();
			}
		}
		return self::$_enabled;
	}

	/**
	 * Pushes to the stack
	 *
	 * @param string $name
	 * @param string $type
	 * @return void
	 */
	public static function start($name, $type='') {
		if (!self::isEnabled()) {
			return;
		}

		$currentPointer = 'timetracker_' . self::$uniqueCounter++;
		array_push(self::$currentPointerStack, $currentPointer);
		array_push(self::$stack, $name);

		self::$stackLevel++;
		self::$stackLevelMax[] = self::$stackLevel;

		self::$stackLog[$currentPointer] = array(
			'level' => self::$stackLevel,
			'stack' => self::$stack,
			'time_start' => microtime(true),
			'realmem_start' => memory_get_usage(true),
		    'emalloc_start' => memory_get_usage(false),
			'type' => $type,
		);

		if ($name == '__EAV_LOAD_MODEL__') {
			$trace = debug_backtrace();
			$className = get_class($trace[1]['args'][0]);
			$entityId = $trace[1]['args'][1];
			$attributes = $trace[1]['args'][2];
			self::$stackLog[$currentPointer]['detail'] = "$className, id: $entityId, attributes: " . var_export($attributes, true);
		}

		if (isset($_GET['links']) && $_GET['links'] == true) {
			$trace = isset($trace) ? $trace : debug_backtrace();
			$fileAndLine = self::getFileAndLine($trace, $type, $name);
			self::$stackLog[$currentPointer]['file'] = $fileAndLine['file'];
			self::$stackLog[$currentPointer]['line'] = $fileAndLine['line'];
		}

	}

	/**
	 * Get file and line for the current bucket and try to be smart about it.
	 * In some case it is not very helpful to jump to the line where Varien_Profiler::start() is called,
	 * because this could be a generic call. Instead jumping to the line where the actual action is happing.
	 *
	 * @param array $trace
	 * @param $type
	 * @param $name
	 * @return array|bool
	 */
	public static function getFileAndLine(array $trace, $type, $name) {

		$fileAndLine = false;

		switch (self::getType($type, $name)) {

			/**
			 * If we have an "event" let's jump to where the event is dispatched
			 */
			case Varien_Profiler::TYPE_EVENT:
				$fileAndLine = array(
					'file' => $trace[1]['file'],
					'line' => $trace[1]['line'],
				);
				break;

			/**
			 * In case of a template let's jump to the template file
			 */
			case Varien_Profiler::TYPE_TEMPLATE:
				$fileAndLine = array(
					'file' => Mage::getBaseDir('design') . DS . $name,
					'line' => 0,
				);
				break;

			/**
			 * For blocks let's jump the the block class
			 */
			case Varien_Profiler::TYPE_BLOCK:
				$node = $trace[1]['args'][0]; /* @var $node Varien_Simplexml_Element */
				if (!empty($node['class'])) {
					$className = (string)$node['class'];
				} else {
					$className = (string)$node['type'];
				}
				$className = Mage::getConfig()->getBlockClassName($className);
				$fileAndLine = array(
					'file' => mageFindClassFile($className),
					'line' => 0,
				);
				break;

			/**
			 * For models let's extract the class name and jump to this file instead
			 */
			case Varien_Profiler::TYPE_MODEL:
				$className = substr($name, 24);
				$fileAndLine = array(
					'file' => mageFindClassFile($className),
					'line' => 0,
				);
				break;

			case Varien_Profiler::TYPE_EAVMODEL:
				$fileAndLine = array(
					'file' => $trace[3]['file'],
					'line' => $trace[3]['line'],
				);
				break;

			/**
			 * Ok, this is ugly and very slow, but it's so handy... :)
			 * In case of an observer let's find out the class and method that will be executed, find the file and jump
			 * to this method.
			 */
			case Varien_Profiler::TYPE_OBSERVER:
				$observerName = substr($name, 10);
				$eventName = substr(self::$stack[count(self::$stack)-2], 15);

				foreach (array(Mage::getDesign()->getArea(), 'global') as $area) {
					$eventConfig = Mage::app()->getConfig()->getEventConfig($area, $eventName);

					if ($eventConfig) {
						$observers = array();
						foreach ($eventConfig->observers->children() as $obsName=>$obsConfig) {
							$observers[$obsName] = array(
								'type'  => (string)$obsConfig->type,
								'model' => $obsConfig->class ? (string)$obsConfig->class : $obsConfig->getClassName(),
								'method'=> (string)$obsConfig->method,
							);
						}

						if (isset($observers[$observerName])) {
							$model = $observers[$observerName]['model'];
							$method = $observers[$observerName]['method'];
							$className = Mage::getConfig()->getModelClassName($model);
							$file = mageFindClassFile($className);
							$line = self::getLineNumber($file, '/function.*'.$method.'/');
							$fileAndLine = array(
								'file' => $file,
								'line' => $line,
							);
							break;
						}
					}
				}
				if ($fileAndLine) {
					break;
				}
			default:
				$fileAndLine = array(
					'file' => $trace[0]['file'],
					'line' => $trace[0]['line'],
				);
		}
		return $fileAndLine;
	}

	/**
	 * Get the line number of the first line in a file matching a given regex
	 * Not the nicest solution, but probably the fastest
	 *
	 * @param $file
	 * @param $regex
	 * @return bool|int
	 */
	public static function getLineNumber($file, $regex) {
		$i = 0;
		$lineFound = false;
		$handle = @fopen($file, 'r');
		if ($handle) {
			while (($buffer = fgets($handle, 4096)) !== false) {
				$i++;
				if (preg_match($regex, $buffer)) {
					$lineFound = true;
					break;
				}
		    }
		    fclose($handle);
		}
		return $lineFound ? $i : false;
	}

	/**
	 * Get type
	 *
	 * @param $type
	 * @param $label
	 * @return string
	 */
	public static function getType($type, $label) {
		if (empty($type)) {
			if (substr($label, -1 * strlen('.phtml')) == '.phtml') {
				$type = Varien_Profiler::TYPE_TEMPLATE;
			} elseif (strpos($label, 'DISPATCH EVENT:') === 0) {
				$type = Varien_Profiler::TYPE_EVENT;
			} elseif (strpos($label, 'OBSERVER:') === 0) {
				$type = Varien_Profiler::TYPE_OBSERVER;
			} elseif (strpos($label, 'BLOCK:') === 0) {
				$type = Varien_Profiler::TYPE_BLOCK;
			} elseif (strpos($label, 'CORE::create_object_of::') === 0) {
				$type = Varien_Profiler::TYPE_MODEL;
			} elseif (strpos($label, '__EAV_LOAD_MODEL__') === 0) {
				$type = Varien_Profiler::TYPE_EAVMODEL;
			}
		}
		return $type;
	}

	/**
	 * Pull element from stack
	 *
	 * @param string $name
	 * @throws Exception
	 * @return void
	 */
	public static function stop($name) {
		if (!self::isEnabled()) {
			return;
		}

		$currentName = end(self::$stack);
		if ($currentName != $name) {
		    if (Mage::getStoreConfigFlag('dev/debug/logInvalidNesting')) {
			    Mage::log('[INVALID NESTING!] Found: ' .$name . " | Expecting: $currentName");
            }

			if (in_array($name, self::$stack)) {
				// trying to stop something that has been started before,
				// but there are other unstopped stack items
				// -> auto-stop them
				while (($latestStackItem = end(self::$stack)) != $name) {
				    if (Mage::getStoreConfigFlag('dev/debug/logInvalidNesting')) {
					   Mage::log('Auto-stopping timer "' .$latestStackItem . '" because of incorrect nesting');
                    }
					self::stop($latestStackItem);
				}
			} else {
				// trying to stop something that hasn't been started before -> just ignore
				return;
			}

			$name = '[INVALID NESTING!] ' . $name;
			self::start($name);
			// return;
			// throw new Exception(sprintf("Invalid nesting! Expected: '%s', was: '%s'", $currentName, $name));
		}

		$currentPointer = end(self::$currentPointerStack);

		self::$stackLog[$currentPointer]['time_end'] = microtime(true);
		self::$stackLog[$currentPointer]['realmem_end'] = memory_get_usage(true);
		self::$stackLog[$currentPointer]['emalloc_end'] = memory_get_usage(false);

		// TODO: introduce configurable threshold
		if (self::$_logCallStack !== false) {
			self::$stackLog[$currentPointer]['callstack'] = Varien_Debug::backtrace(true, false);
		}

		self::$stackLevel--;
		array_pop(self::$stack);
		array_pop(self::$currentPointerStack);
	}

	/**
	 * Add data to the current stack
	 *
	 * @param $data
	 * @param null $key
	 */
	public static function addData($data, $key=NULL) {
		$currentPointer = end(self::$currentPointerStack);
		if (!isset(self::$stackLog[$currentPointer]['messages'])) {
			self::$stackLog[$currentPointer]['messages'] = array();
		}
		if (is_null($key)) {
			self::$stackLog[$currentPointer]['messages'][] = $data;
		} else {
			self::$stackLog[$currentPointer]['messages'][$key] = $data;
		}
	}

	/**
	 * Enabling profiler
	 *
	 * @return void
	 */
	public static function enable() {
		self::$startValues = array(
			'time' => microtime(true),
			'realmem' => memory_get_usage(true),
			'emalloc' => memory_get_usage(false)
		);
		self::$_enabled = true;
    }

	/**
	 * Disabling profiler
	 *
	 * @return void
	 */
    public static function disable() {

		if (self::isEnabled()) {
			// stop any timers still on stack (those might be stopped after calculation otherwise)
			$stackCopy = self::$stack;
			while ($timerName = array_pop($stackCopy)) {
				self::stop($timerName);
			}
		}
		self::$_enabled = false;

		self::calculate();
    }

	/**
	 * Get raw stack log data
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getStackLog() {
		if (self::isEnabled()) {
			throw new Exception('Disable profiler first!');
		}
		return self::$stackLog;
	}

	/**
	 * Set log stack trace
	 *
	 * @static
	 * @param $logStackTrace
	 */
	public static function setLogCallStack($logStackTrace) {
		self::$_logCallStack = $logStackTrace;
	}

	/**
	 * Calculate relative data
	 *
	 * @return void
	 */
	public static function calculate() {
		foreach (self::$stackLog as &$data) {
			foreach (array('time', 'realmem', 'emalloc') as $metric) {
				$data[$metric.'_end_relative'] = $data[$metric.'_end'] - self::$startValues[$metric];
				$data[$metric.'_start_relative'] = $data[$metric.'_start'] - self::$startValues[$metric];
				$data[$metric.'_total'] = $data[$metric.'_end_relative'] - $data[$metric.'_start_relative'];
			}
		}
	}

	/**
	 * Dummy methods to be fully compatible with the original Varien_Profiler class
	 */

	public static function resume($timerName) {
		return self::start($timerName);
	}

	public static function pause($timerName) {
		return self::stop($timerName);
	}

	public static function reset($timerName) {
		return self::stop($timerName);
	}

	public static function fetch($timerName, $key='sum') {
		return false;
	}

	public static function getTimers() {
		return array();
	}

	public static function getSqlProfiler($res) {
		return '';
	}

}
