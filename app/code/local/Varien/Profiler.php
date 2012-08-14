<?php

/**
 * Stack-based profiler
 * (Drop-in replacement for the default Magento profiler)
 *
 * @author Fabrizio Branca
 */
class Varien_Profiler {

	static private $time_start = 0;
	static private $realmem_start = 0;
	static private $emalloc_start = 0;

	static private $stackLevel = 0;
	static private $stack = array();
	static private $stackLevelMax = array();
	static private $stackLog = array();
	static private $uniqueCounter = 0;
	static private $currentPointerStack = array();

	static private $_enabled = false;


	/**
	 * Pushes to the stack
	 *
	 * @param string $name
	 * @param string $value
	 * @return void
	 */
	public static function start($name, $value='') {
		if (!self::$_enabled) {
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
		if (!self::$_enabled) {
			return;
		}

		$currentName = end(self::$stack);
		if ($currentName != $name) {
			$name = '[INVALID NESTING!] ' . $name;
			Mage::log($name . " | expecting: $currentName");
			// Mage::log(var_export(self::$stack, 1));
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
		self::$time_start = microtime(TRUE);
		self::$realmem_start = memory_get_usage(TRUE);
		self::$emalloc_start = memory_get_usage(false);
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
			$data['time_end_relative'] = $data['time_end'] - self::$time_start;
			$data['time_start_relative'] = $data['time_start'] - self::$time_start;
			$data['time_total'] = $data['time_end_relative'] - $data['time_start_relative'];

			$data['realmem_end_relative'] = $data['realmem_end'] - self::$realmem_start;
			$data['realmem_start_relative'] = $data['realmem_start'] - self::$realmem_start;
			$data['realmem_total'] = $data['realmem_end_relative'] - $data['realmem_start_relative'];

			$data['emalloc_end_relative'] = $data['emalloc_end'] - self::$emalloc_start;
			$data['emalloc_start_relative'] = $data['emalloc_start'] - self::$emalloc_start;
			$data['emalloc_total'] = $data['emalloc_end_relative'] - $data['emalloc_start_relative'];
		}

		self::disable();
	}

}
