<?php

class Aoe_Profiler_Model_Stack extends Mage_Core_Model_Abstract {

	protected $stackLog;
	protected $treeData = array();

	protected $metrics = array('time', 'realmem' /*, 'emalloc' */);

	/**
	 * @return Aoe_Profiler_Model_Stack
	 */
	public function loadStackLogFromProfiler() {
		Varien_Profiler::disable();
		$this->stackLog = Varien_Profiler::getStackLog();
		return $this;
	}

	public function getStackLog() {
		return $this->stackLog;
	}

	/**
	 * @return Aoe_Profiler_Model_Stack
	 */
	public function processRawData() {
		// Create hierarchical array of keys pointing to the stack
		foreach ($this->stackLog as $uniqueId => $data) {
			$this->createHierarchyArray($this->treeData, $data['level'], $uniqueId);
		}

		$this->treeData = end($this->treeData);
		$this->updateValues($this->treeData);

		$this->calcRelativeValues();

		return $this;
	}

	/**
	 * @return array
	 */
	public function getTreeData() {
		return $this->treeData;
	}

	/**
	 * Update values. (Recursive function)
	 *
	 * @param $arr
	 * @param string $vKey
	 */
	protected function updateValues(&$arr, $vKey='') {

		$subSum = array_flip($this->metrics);
		foreach ($arr as $k => $v) {

			if (strpos($k, '_children') === false) {

				if (isset($arr[$k . '_children']) && is_array($arr[$k . '_children'])) {
					$this->updateValues($arr[$k . '_children'], $v);
				} else {
					foreach ($subSum as $key => $value) {
						$this->stackLog[$v][$key.'_sub'] = 0;
						$this->stackLog[$v][$key.'_own'] = $this->stackLog[$v][$key.'_total'];
					}
				}
				foreach ($subSum as $key => $value) {
					$subSum[$key] += $this->stackLog[$v][$key.'_total'];
				}
			}
		}
		if (isset($this->stackLog[$vKey])) {
			foreach ($subSum as $key => $value) {
				$this->stackLog[$vKey][$key.'_sub'] = $subSum[$key];
				$this->stackLog[$vKey][$key.'_own'] = $this->stackLog[$vKey][$key.'_total'] - $subSum[$key];
			}
		}
	}

	/**
	 * Calculate relative values
	 */
	protected function calcRelativeValues() {
		foreach ($this->stackLog as $key => $value) {
			foreach ($this->metrics as $metric) {
				foreach (array('own', 'sub', 'total') as $column) {
					if (!isset($this->stackLog[$key][$metric.'_'.$column])) {
						continue;
					}
					$this->stackLog[$key][$metric.'_rel_'.$column] = $this->stackLog[$key][$metric.'_'.$column] / $this->stackLog['timetracker_0'][$metric.'_total'];
				}
				$this->stackLog[$key][$metric.'_rel_offset'] = $this->stackLog[$key][$metric.'_start_relative'] / $this->stackLog['timetracker_0'][$metric.'_total'];
			}
		}
	}

	/**
	 * Helper function for internal data manipulation (Recursive function)
	 *
	 * @param array $arr
	 * @param int $pointer
	 * @param string $uniqueId
	 * @return void
	 */
	protected function createHierarchyArray(&$arr, $pointer, $uniqueId) {
		if (!is_array($arr)) {
			$arr = array();
		}
		if ($pointer > 0) {
			end($arr);
			$k = key($arr);
			$this->createHierarchyArray($arr[intval($k) . '_children'], $pointer-1, $uniqueId);
		} else {
			$arr[] = $uniqueId;
		}
	}

}