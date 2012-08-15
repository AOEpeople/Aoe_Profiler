<?php

/**
 * Stack-based profiler
 * (Drop-in replacement for the default Magento profiler)
 *
 * @author Fabrizio Branca
 */
class Varien_Profiler {

	static private $startValues = array();

	static private $stackLevel = 0;
	static private $stack = array();
	static private $stackLevelMax = array();
	static private $stackLog = array();
	static private $uniqueCounter = 0;
	static private $currentPointerStack = array();

	static private $_enabled = false;
	static private $_checkedEnabled = false;

	public static function isEnabled() {
		if (!self::$_checkedEnabled) {
			self::$_checkedEnabled = true;
			if (isset($_GET['profile']) && $_GET['profile'] == true) {
				self::enable();
			}
		}
		return self::$_enabled;
	}


	/**
	 * Pushes to the stack
	 *
	 * @param string $name
	 * @param string $value
	 * @return void
	 */
	public static function start($name, $value='') {
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
			'value' => $value,
			'time_start' => microtime(true),
			'realmem_start' => memory_get_usage(true),
		    'emalloc_start' => memory_get_usage(false),
		);
	}

	/**
	 * Pull element from stack
	 *
	 * @param string $name
	 * @param string $content
	 * @throws Exception
	 * @return void
	 */
	public static function stop($name, $content='') {
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

			// Mage::log(var_export(self::$stack, 1));

			$name = '[INVALID NESTING!] ' . $name;
			self::start($name);
			// return;
			// throw new Exception(sprintf("Invalid nesting! Expected: '%s', was: '%s'", $currentName, $name));
		}

		$currentPointer = end(self::$currentPointerStack);
		self::$stackLog[$currentPointer]['time_end'] = microtime(true);
		self::$stackLog[$currentPointer]['realmem_end'] = memory_get_usage(true);
		self::$stackLog[$currentPointer]['emalloc_end'] = memory_get_usage(false);
		self::$stackLog[$currentPointer]['content'] = $content;

		self::$stackLevel--;
		array_pop(self::$stack);
		array_pop(self::$currentPointerStack);
	}


    public static function enable() {
		self::$startValues = array(
			'time' => microtime(true),
			'realmem' => memory_get_usage(true),
			'emalloc' => memory_get_usage(false)
		);
		self::$_enabled = true;
    }

    public static function disable() {
		self::$_enabled = false;
    }

	public static function getStackLog() {
		return self::$stackLog;
	}

	public static function calculate() {
		// stop any timers still on stack (those might be stopped after calculation otherwise)
		$stackCopy = self::$stack;
		while ($timerName = array_pop($stackCopy)) {
			self::stop($timerName);
		}

		foreach (self::$stackLog as $uniqueId => &$data) {
			foreach (array('time', 'realmem', 'emalloc') as $metric) {
				$data[$metric.'_end_relative'] = $data[$metric.'_end'] - self::$startValues[$metric];
				$data[$metric.'_start_relative'] = $data[$metric.'_start'] - self::$startValues[$metric];
				$data[$metric.'_total'] = $data[$metric.'_end_relative'] - $data[$metric.'_start_relative'];
			}
		}

		self::disable();
	}

}
