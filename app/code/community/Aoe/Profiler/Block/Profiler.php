<?php

/**
 * Profiler output block
 *
 * @author Fabrizio Branca
 */
class Aoe_Profiler_Block_Profiler extends Mage_Core_Block_Abstract {

	/**
	 * @var array metrics to be displayed
	 */
	protected $metrics = array('time', 'realmem' /*, 'emalloc' */);

	/**
	 * @var array units
	 */
	protected $units = array(
		'time' => 'ms',
		'realmem' => 'MB',
		'emalloc' => 'MB'
	);

	/**
	 * @var array type icons
	 */
	protected $typeIcons = array(
		Varien_Profiler::TYPE_DEFAULT => 'aoe_profiler/img/led-icons/page.png',
		Varien_Profiler::TYPE_DATABASE => 'aoe_profiler/img/led-icons/database.png',
		Varien_Profiler::TYPE_TEMPLATE => 'aoe_profiler/img/led-icons/template.png',
		Varien_Profiler::TYPE_BLOCK => 'aoe_profiler/img/led-icons/brick.png',
		Varien_Profiler::TYPE_EVENT => 'aoe_profiler/img/led-icons/anchor.png',
		Varien_Profiler::TYPE_OBSERVER => 'aoe_profiler/img/led-icons/pin.png',
	);

	/**
	 * @var array stack log data
	 */
	protected $stackLog;

	/**
	 * @var array hierarchical representation of the stack log data
	 */
	protected $treeData;

	/**
	 * Get type icon
	 *
	 * @param $type
	 * @param $label
	 * @return string
	 */
	protected function getType($type, $label) {
		if (empty($type)) {
			if (substr($label, -1 * strlen('.phtml')) == '.phtml') {
				$type = Varien_Profiler::TYPE_TEMPLATE;
			} elseif (strpos($label, 'DISPATCH EVENT:') === 0) {
				$type = Varien_Profiler::TYPE_EVENT;
			} elseif (strpos($label, 'OBSERVER:') === 0) {
				$type = Varien_Profiler::TYPE_OBSERVER;
			} elseif (strpos($label, 'BLOCK:') === 0) {
				$type = Varien_Profiler::TYPE_BLOCK;
			}
		}

		if (!isset($this->typeIcons[$type])) {
			$type = Varien_Profiler::TYPE_DEFAULT;
		}
		return $type;
	}

	/**
	 * Render tree (recursive function)
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

				$hasChildren = isset($data[$key.'_children']) && count($data[$key.'_children']) > 0;

				$duration = round($tmp['time_total'] * 1000);
				$output .= '<li duration="'.$duration.'" class="'.($tmp['level']>1?'collapsed':'').' level-'.$tmp['level'] .' '.($hasChildren ? 'has-children' : '').'">';

				$output .= '<div class="info">';

					$output .= '<div class="label">';
					if ($hasChildren) {
						$output .= '<div class="toggle profiler-open">&nbsp;</div>';
						$output .= '<div class="toggle profiler-closed">&nbsp;</div>';
					}

					$label = end($tmp['stack']);
					$type = $this->getType($tmp['type'], $label);

					$output .= '<span class="caption type-'.$type.'" title="'.htmlspecialchars($label).'" />' . htmlspecialchars($label) . '</span>';

					$output .= '</div>'; // class="label"

					$output .= '<div class="profiler-columns">';
					foreach ($this->metrics as $metric) {

						$output .= '<div class="metric">';

							$formatterMethod = 'format_'.$metric;

							$progressBar = $this->renderProgressBar(
								$tmp[$metric.'_rel_own'] * 100,
								$tmp[$metric.'_rel_sub'] * 100,
								$tmp[$metric.'_rel_offset'] * 100,
								'Own: ' . $helper->$formatterMethod($tmp[$metric.'_own']) . ' ' . $this->units[$metric] . ' / ' . round($tmp[$metric.'_rel_own'] * 100, 2) . '%',
								'Sub: ' . $helper->$formatterMethod($tmp[$metric.'_sub']) . ' ' . $this->units[$metric] . ' / ' . round($tmp[$metric.'_rel_sub'] * 100, 2) . '%'
							);
							$output .= '<div class="'.$metric.' profiler-column">'. $progressBar . '</div>';

						$output .= '</div>'; // class="metric"

					}
					$output .= '</div>'; // class="profiler-columns"

				$output .= '</div>'; // class="info"

				if ($hasChildren) {
					$output .= '<ul>' . $this->renderTree($data[$key.'_children']) . '</ul>';
				}

				$output .= '</li>';
			}
		}
		return $output;
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

		if (!Varien_Profiler::isEnabled()) {
			if (Mage::getStoreConfig('dev/debug/showDisabledMessage')) {

				// Adding css. Want to be as obtrusive as possible and not add any file to the header (as bundling might be influenced by this)
				// That's why I'm embedding css here into the html code...
				$output = '<style type="text/css">'.Mage::helper('aoe_profiler')->getSkinFileContent('aoe_profiler/css/styles.css').'</style>';

				$url = Mage::helper('core/url')->getCurrentUrl();
				$url .= (strpos($url, '?') === false) ? '?' : '&';
				$url .= 'profile=1#profiler';

				$output .= '<div id="profiler">
					<p class="hint">Add <a href="'.$url.'">?profile=1</a> to the url to enable <strong>profiling</strong>.</p>
					<p class="hint-small">(This message can be hidden in System > Configuration > Developer > Profiler.)</p>
				</div>';

				return $output;
			}
			return;
		}

		$stackModel = Mage::getModel('aoe_profiler/stack'); /* @var $stackModel Aoe_Profiler_Model_Stack */

		$stackModel
			->loadStackLogFromProfiler()
			->processRawData();

		$this->stackLog = $stackModel->getStackLog();
		$this->treeData = $stackModel->getTreeData();

		// Adding css. Want to be as obtrusive as possible and not add any file to the header (as bundling might be influenced by this)
		// That's why I'm embedding css here into the html code...
		$output = '<style type="text/css">'.Mage::helper('aoe_profiler')->getSkinFileContent('aoe_profiler/css/styles.css').'</style>';
		// Icons cannot be embedded using relatives path in this situation.
		$output .= '<style type="text/css">';
		$output .= '#profiler .profiler-open { background-image: url(\''.$this->getSkinUrl('aoe_profiler/img/button-open.png').'\'); }'."\n";
		$output .= '#profiler .profiler-closed { background-image: url(\''.$this->getSkinUrl('aoe_profiler/img/button-closed.png').'\'); }'."\n";
		foreach ($this->typeIcons as $key => $icon) {
			$output .= '#profiler .type-'.$key. ' { background-image: url(\''.$this->getSkinUrl($icon).'\'); }'."\n";
		}
		$output .= '</style>';



		$output .= '<div id="profiler"><h1>Profiler</h1>';

        $output .= <<<HTML
            <div id="p-search">
                <form id="text-filter-form">
                    <label for="text-filter">Search for:</label>
                    <input type="text" id="text-filter" value="" placeholder="Text or regular expression"
                           title="Text or regular expression" />
                    <br />
                    <input type="checkbox" id="text-filter-case-sensitivity" value="" />
                    <label for="text-filter-case-sensitivity">Case insensitive</label>
                    <button>Search!</button>
                </form>
            </div>
HTML;

        $hideLinesFasterThan = intval(Mage::getStoreConfig('dev/debug/hideLinesFasterThan'));

		$output .= '<div id="p-slider">';
			$output .= '<div>Hide entries faster than <form id="duration-filter-form"><input type="text" id="duration-filter" value="'.$hideLinesFasterThan.'" /> ms: <button>Hide!</button></form></div>';
			$output .= '<div id="p-track"><div id="p-handle" class="selected" title="Drag me!"><img src="'.$this->getSkinUrl('aoe_profiler/img/slider.png').'" /></div></div>';
		$output .= '</div>';


		$output .= $this->renderHeader();

		$output .= '<ul id="treeView" class="treeView">';
			$output .= $this->renderTree($this->treeData);
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
	 * @param $percent1
	 * @param int $percent2
	 * @param int $offset
	 * @param string $percent1Label
	 * @param string $percent2Label
	 * @return string
	 */
	protected function renderProgressBar($percent1, $percent2=0, $offset=0, $percent1Label='', $percent2Label='') {
		$percent1 = round(max(1, $percent1));
		$offset = round(max(0, $offset));
		$offset = round(min(99, $offset));

		$output = '<div class="progress">';
			$output .= '<div class="progress-bar">';
				$output .= '<div class="progress-bar1" style="width: '.$percent1.'%; margin-left: '.$offset.'%;" title="'.$percent1Label.'"></div>';

				if ($percent2 > 0) {
					$percent2 = round(max(1, $percent2));
					if ($percent1 + $percent2 + $offset > 100) {
						// preventing line break in css progress bar if widths and margins are bigger than 100%
						$percent2 = 100 - $percent1 - $offset;
						$percent2 = max(0, $percent2);
					}
					$output .= '<div class="progress-bar2" style="width: '.$percent2.'%"  title="'.$percent2Label.'"></div>';
				}

				$output .= '</div>';
		$output .= '</div>';
		return $output;
	}

}
