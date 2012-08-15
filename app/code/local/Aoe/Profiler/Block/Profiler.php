<?php

/**
 * Profiler output block
 *
 * @author Fabrizio Branca
 */
class Aoe_Profiler_Block_Profiler extends Mage_Core_Block_Profiler {

	protected $stackLog;
	protected $hideLinesFasterThan = 10;
	protected $metrics = array('time', 'realmem' /*, 'emalloc' */);
	protected $units = array('time' => 'ms', 'realmem' => 'MB', 'emalloc' => 'MB');

	/**
	 * Render tree
	 *
	 * @param array $data
	 * @return string
	 */
	public function renderTree(array $data) {

		$helper = Mage::helper('aoe_profiler'); /* @var $helper Aoe_Profiler_Helper_Data */
		$output = '';
		foreach ($data as $key => $uniqueId) {
			if (strpos($key, '_children') === false) {

				$tmp = $this->stackLog[$uniqueId];

				$hasChildren = isset($data[$key . '_children']) && count($data[$key . '_children']) > 0;

				$output .= '<li class="'.($tmp['level']>1?'collapsed':'').' level-'.$tmp['level'] .' '.($hasChildren ? 'has-children' : '').'">';

				$output .= '<div class="info">';


					$output .= '<div class="label">';
					if ($hasChildren) {
						$output .= '<a id="'.$uniqueId.'" href="#'.$uniqueId.'" class="toggle">';
						$output .= '<div class="profiler-open"><img src="'.$this->getSkinUrl('aoe_profiler/img/button-open.png').'" /></div>';
						$output .= '<div class="profiler-closed"><img src="'.$this->getSkinUrl('aoe_profiler/img/button-closed.png').'" /></div>';
						$output .= end($tmp['stack']).'</a>';
					} else {
						$output .= '<span>'.end($tmp['stack']).'</span>';
					}
					$output .= '</div>';

					$output .= '<div class="profiler-columns">';
					foreach ($this->metrics as $metric) {

						$output .= '<div class="metric">';

							$formatterMethod = 'format_'.$metric;

							$progressBar = $this->renderProgressBar(
								$tmp[$metric.'_rel_own'] * 100,
								$tmp[$metric.'_rel_sub'] * 100,
								$tmp[$metric.'_rel_offset'] * 100,
								'Own: ' . $helper->$formatterMethod($tmp[$metric.'_own']) . ' ' . $this->units[$metric],
								'Sub: ' . $helper->$formatterMethod($tmp[$metric.'_sub']) . ' ' . $this->units[$metric]
							);
							$output .= '<div class="'.$metric.' profiler-column">'. $progressBar . '</div>';

						$output .= '</div>';

					}
					$output .= '</div>';

				// $output .= '<pre>'.var_export($tmg prop,1) . '</pre>';

				$output .= '</div>';

				if (isset($data[$key . '_children'])) {
					$output .= '<ul>';
					$output .= $this->renderTree($data[$key . '_children']);
					$output .= '</ul>';
				}
				$output .= '</li>';
			}
		}
		return $output;
	}

	/**
	 * Update values
	 *
	 * @param $arr
	 * @param string $vKey
	 */
	protected function updateValues(&$arr, $vKey='') {
		$subSum = array_flip($this->metrics);
		foreach ($arr as $k => $v) {

			if (strpos($k, '_children') === false) {

				// Filter
				if ($this->stackLog[$v]['time_total'] * 1000 <= $this->hideLinesFasterThan) {
					unset($this->stackLog[$v]);
					unset($arr[$k]);
					continue;
				}

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
	 * To HTML
	 *
	 * @return string
	 */
	protected function _toHtml() {

		if (!$this->_beforeToHtml()
			|| !Mage::getStoreConfig('dev/debug/profiler')
			|| !Mage::helper('core')->isDevAllowed()) {
			return '';
		}

		// Adding css. Want to be as obtrusive as possible and not add any file to the header (as bundling might be influenced by this)
		// That's why I'm embedding css here into the html code...
		$output = '<style type="text/css">'.Mage::helper('aoe_profiler')->getSkinFileContent('aoe_profiler/css/styles.css').'</style>';

		if (!Varien_Profiler::isEnabled()) {
			$url = Mage::helper('core/url')->getCurrentUrl();
			$url .= (strpos($url, '?') === false) ? '?' : '&';
			$url .= 'profile=1#profiler';

			$output .= '<div id="profiler">
				<p class="hint">Add <a href="'.$url.'">?profile=1</a> to the url to enable <strong>profiling</strong>.</p>
			</div>';

			return $output;
		}

		// some data pre-processing
		Varien_Profiler::calculate();
		$this->stackLog = Varien_Profiler::getStackLog();

			// Create hierarchical array of keys pointing to the stack
		$arr = array();
		foreach ($this->stackLog as $uniqueId => $data) {
			$this->createHierarchyArray($arr, $data['level'], $uniqueId);
		}

		$arr = end($arr);
		$this->updateValues($arr);

		$this->calcRelativeValues();

		$output .= '<div id="profiler"><h1>Profiler</h1>';

		if ($this->hideLinesFasterThan) {
			$output .= '<p>' . $this->__('(Hiding all entries faster than %s ms.)', $this->hideLinesFasterThan) . '</p>';
		}

		$output .= $this->renderHeader();

		$output .= '<ul id="treeView" class="treeView">';
		$output .= $this->renderTree($arr);
		$output .= '</ul>';

		$output .= '</div>';

		// adding js
		$output .= '<script type="text/javascript">'.Mage::helper('aoe_profiler')->getSkinFileContent('aoe_profiler/js/profiler.js').'</script>';

		return $output;
	}

	/**
	 * Render header
	 *
	 * @return string
	 */
	protected function renderHeader() {
		$helper = Mage::helper('aoe_profiler'); /* @var $helper Aoe_Profiler_Helper_Data */

		$captions = '<ul>
			<li class="captions">
				<div class="info">';
		$captions .= '<div class="profiler-columns">';
		foreach ($this->metrics as $metric) {
			$captions .= '<div class="metric">';
				$captions .= '<div class="profiler-column3">';
				$captions .= $this->__($metric);
				$captions .= '</div>';
			$captions .= '</div>';
		}
		$captions .= '</div>';
		$captions .= '</div>
			</li>
		</ul>';

		$captions .= '<ul>
			<li class="captions captions-line">
				<div class="info">
					<div class="label">'.$this->__('Name').'
						<a id="expand-all" href="#">['.$this->__('expand all').']</a>
						<a id="collapse-all" href="#">['.$this->__('collapse all').']</a>
					</div>';
		$captions .= '<div class="profiler-columns">';
		foreach ($this->metrics as $metric) {
			$formatterMethod = 'format_'.$metric;
			$captions .= '<div class="metric">';
				$captions .= '<div class="profiler-column3">';
				$captions .= $helper->$formatterMethod($this->stackLog['timetracker_0'][$metric.'_total']) . ' ' . $this->units[$metric];
				$captions .= '</div>';
			$captions .= '</div>';
		}
		$captions .= '</div>';
		$captions .= '</div>
			</li>
		</ul>';

		return $captions;
	}

	/**
	 * Render css progress bar
	 *
	 * @param $percent
	 * @param int $percent2
	 * @param int $offset
	 * @param string $percentLabel
	 * @param string $percent2Label
	 * @return string
	 */
	protected function renderProgressBar($percent, $percent2=0, $offset=0, $percentLabel='', $percent2Label='') {
		$percent = round(max(1, $percent));
		$percent2 = round(max(1, $percent2));
		$offset = round(max(0, $offset));

		if ($percent + $percent2 + $offset > 100) {
			$percent2 = 100 - $percent - $offset;
		}

		// preventing line break in css progress bar if widhs and margins are bigger than 100%
		$output = '<div class="progress">';
			$output .= '<div class="progress-bar">';
				$output .= '<div class="progress-bar1" style="width: '.$percent.'%; margin-left: '.$offset.'%;" title="'.$percentLabel.'"></div>';
				if ($percent2) {
					$output .= '<div class="progress-bar2" style="width: '.$percent2.'%"  title="'.$percent2Label.'"></div>';
				}
				$output .= '</div>';
		$output .= '</div>';
		return $output;
	}

	/**
	 * Helper function for internal data manipulation
	 *
	 * @param	array		Array (passed by reference) and modified
	 * @param	integer		Pointer value
	 * @param	string		Unique ID string
	 * @return	void
	 */
	protected function createHierarchyArray(&$arr, $pointer, $uniqueId) {
		if (!is_array($arr)) {
			$arr = array();
		}
		if ($pointer > 0) {
			end($arr);
			$k = key($arr);
			$this->createHierarchyArray($arr[intval($k) . '_children'], $pointer - 1, $uniqueId);
		} else {
			$arr[] = $uniqueId;
		}
	}

}
