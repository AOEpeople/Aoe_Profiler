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
			Mage::log('[INVALID NESTING!] Found: ' .$name . " | Expecting: $currentName");

			if (in_array($name, self::$stack)) {
				// trying to stop something that has been started before,
				// but there are other unstopped stack items
				// -> auto-stop them
				while (($latestStackItem = end(self::$stack)) != $name) {
					Mage::log('Auto-stopping timer "' .$latestStackItem . '" because of incorrect nesting');
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

}
