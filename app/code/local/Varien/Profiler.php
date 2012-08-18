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
	 * @param string $message
	 * @return void
	 */
	public static function start($name, $message='') {
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
		);

		if ($message) {
			self::$stackLog[$currentPointer]['messages'] = array($message);
		}
	}

	/**
	 * Pull element from stack
	 *
	 * @param string $name
	 * @param string $message
	 * @throws Exception
	 * @return void
	 */
	public static function stop($name, $message='') {
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

		if ($message) {
			if (!isset(self::$stackLog[$currentPointer]['messages'])) {
				self::$stackLog[$currentPointer]['messages'] = array($message);
			} else {
				self::$stackLog[$currentPointer]['messages'][] = $message;
			}
		}

		self::$stackLevel--;
		array_pop(self::$stack);
		array_pop(self::$currentPointerStack);
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
	 * @return void
	 */
	public static function getStackLog() {
		if (self::isEnabled()) {
			throw new Exception('Disable profiler first!');
		}
		return self::$stackLog;
	}

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
