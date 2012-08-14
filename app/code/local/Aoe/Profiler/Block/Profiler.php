<?php

class Aoe_Profiler_Block_Profiler extends Mage_Core_Block_Profiler {

	protected $stackLog;
	protected $hideLinesFasterThan = 10;
	protected $highlightLinesSlowerThan = 200;
	protected $metrics = array('time', 'realmem' /*, 'emalloc' */);
	protected $units = array('time' => 'ms', 'realmem' => 'MB', 'emalloc' => 'MB');
	protected $maxValues = array();


	public function renderLine(array $data) {

		$helper = Mage::helper('aoe_profiler'); /* @var $helper Aoe_Profiler_Helper_Data */
		$output = '<ul>';
		foreach ($data as $key => $uniqueId) {
			if (strpos($key, '_children') === false) {

				$tmp = $this->stackLog[$uniqueId];

				$hasChildren = isset($data[$key . '_children']) && count($data[$key . '_children']) > 0;

				$output .= '<li class="'.($tmp['level']>1?'collapsed':'').' level-'.$tmp['level'] .' '.($hasChildren ? 'has-children' : '').'">';
				$output .= '<div class="info">';

					$slowLine = ($tmp['time_total'] * 1000 > $this->highlightLinesSlowerThan);

					$output .= '<div class="label '.($slowLine ? 'slow' : '').'">';
					if ($hasChildren) {
						$output .= '<a id="'.$uniqueId.'" href="#'.$uniqueId.'" class="toggle">'.end($tmp['stack']).'</a>';
					} else {
						$output .= '<span>'.end($tmp['stack']).'</span>';
					}
					$output .= '</div>';

					$output .= '<div class="columns">';
					foreach ($this->metrics as $metric) {

						$output .= '<div class="metric">';

							$formatterMethod = 'format_'.$metric;

							foreach (array('total', 'own', 'sub') as $column) {
								$label = $helper->$formatterMethod($tmp[$metric.'_'.$column]);
								$percent = $tmp[$metric.'_rel_'.$column] * 100;
								$output .= '<div class="'.$metric.' '.$column.' column">'. $this->renderProgressBar($label, $percent) . '</div>';
							}

						$output .= '</div>';

					}
					$output .= '</div>';

				// $output .= '<pre>'.var_export($tmg prop,1) . '</pre>';

				$output .= '</div>';
				if (isset($data[$key . '_children'])) {
					$output .= $this->renderLine($data[$key . '_children']);
				}
				$output .= '</li>';
			}
		}
		$output .= '</ul>';
		return $output;
	}

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

	protected function calcRelativeValues() {
		foreach ($this->stackLog as $key => $value) {
			foreach ($this->metrics as $metric) {
				foreach (array('own', 'sub', 'total') as $column) {
					if (!isset($this->stackLog[$key][$metric.'_'.$column])) {
						continue;
					}
					$this->stackLog[$key][$metric.'_rel_'.$column] = $this->stackLog[$key][$metric.'_'.$column] / $this->stackLog['timetracker_0'][$metric.'_total'];
				}
			}
		}
	}

	protected function _toHtml() {

		if (!$this->_beforeToHtml()
			|| !Mage::getStoreConfig('dev/debug/profiler')
			|| !Mage::helper('core')->isDevAllowed()) {
			return '';
		}

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

		// return '<pre style="background-color:white;">'.var_export($arr, 1).'</pre>';


		$styles = '<style>
			#profiler {
				background-color: white;
				padding: 10px 20px 50px 0;
				color: black;
				font-family: Arial;
			}
			#profiler h1 {
				font-size: 40px;
				margin: 20px;
			}
			#profiler a {
				color: black;
				text-decoration: underline;
			}
			#profiler li.captions {
				font-weight: bold;
			}
			#profiler li.captions-line {
				border-bottom: 2px solid black;
			}
			#profiler ul {
				border-left: 1px solid #ccc;
				padding-left: 16px;
				list-style: none outside none;
			}
			#profiler .level-1 ul {
				background-color: rgba(0, 0, 0, 0.05);
			}
			#profiler .info:hover {
				background-color: #FAD69D;
			}
			#profiler .captions .info:hover {
				background-color: inherit;
			}
			#profiler .info {
				overflow: hidden;
			}
			#profiler .label {
				float: left;
				position: absolute;
				padding-left: 4px;
			}
			#profiler .columns {
				margin-left: 500px;
				float: left;
			}
			#profiler .column {
				float: left;
				width: 70px;
				min-height: 1em;
				text-align: center;
				padding-right: 2px;
			}
			#profiler .column3 {
				float: right;
				width: 216px;
				min-height: 1em;
				text-align: center;
			}
			#profiler .metric {
				float: left;
				margin-right: 60px;
				border: 1px solid black;
				border-width: 0 1px;
			}
			#profiler .level-1 > .info .total {
				font-weight: bold;
				font-size: 1.2em;
			}
			#profiler .has-children > .info {
				border-bottom:  1px solid #ccc;
			}
			#profiler .collapsed > ul {
				display: none;
			}
			#profiler .slow, #profiler .slow a {
				font-weight: bold;
				color: #8b0000;
			}

			#profiler div.progress {
				background: white none repeat scroll 0 0;
				border: 1px solid #CCC;
				margin: 2px;
				float: left;
				padding: 1px;
				position: relative;
				display: block;
				width: 65px;
			}

			#profiler div.progress-bar {
				background-color: #fbab2f;
				height: 15px;
				vertical-align: middle;
			}

			#profiler div.progress-label {
				position: absolute;
				top: 0px;
				left: 4px;
				font-size: 11px;
			}


		</style>';
		
		$script = '<script type="text/javascript">
			$$(".toggle").each(function(element) {
				element.observe("click", function(event) {
					Event.element(event).up("li").toggleClassName("collapsed");
					event.stop();
				})
			});
		</script>';

		$captions = '<ul>
			<li class="captions">
				<div class="info">';
		$captions .= '<div class="columns">';
		foreach ($this->metrics as $metric) {
			$captions .= '<div class="metric">';
				$captions .= '<div class="column3">'.$metric.' [in '.$this->units[$metric].']</div>';
			$captions .= '</div>';
		}
		$captions .= '</div>';
		$captions .= '</div>
			</li>
		</ul>';

		$captions .= '<ul>
			<li class="captions captions-line">
				<div class="info">
					<div class="label">Name</div>';
		$captions .= '<div class="columns">';
		foreach ($this->metrics as $metric) {
			$captions .= '<div class="metric">';
			foreach (array('total', 'own', 'sub') as $column) {
				$captions .= '<div class="column">'.ucfirst($column).'</div>';
			}
			$captions .= '</div>';
		}
		$captions .= '</div>';
		$captions .= '</div>
			</li>
		</ul>';

		return $styles . '<div id="profiler"><h1>Profiler</h1>'.$captions . $this->renderLine($arr).'</div>' . $script;
	}

	protected function renderProgressBar($label, $percent) {
		$percent = max(0, $percent);
		$percent = round($percent);
		return '
		<div class="progress">
			<div class="progress-bar" style="width: '.$percent.'%" ></div>
			<div class="progress-label">'.$label.'</div>
		</div>';
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
