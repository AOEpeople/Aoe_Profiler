<?php

/**
 * Profiler output block
 *
 * @author Fabrizio Branca
 *
 * @method setTitle()
 * @method getTitle()
 * @method setForceRender()
 * @method getForceRender()
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
		$type = Varien_Profiler::getType($type, $label);

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

					$output .= '<div class="profiler-label">';
					if ($hasChildren) {
						$output .= '<div class="toggle profiler-open">&nbsp;</div>';
						$output .= '<div class="toggle profiler-closed">&nbsp;</div>';
					}

					$label = end($tmp['stack']);

					if (isset($tmp['detail'])) {
						$label .= ' ('.htmlspecialchars($tmp['detail']).')';
					}

					$type = $this->getType($tmp['type'], $label);


					$output .= '<span class="caption type-'.$type.'" title="'.htmlspecialchars($label).'" />';

					if (isset($tmp['file'])) {
                        $remoteCallUrlTemplate = Mage::getStoreConfig('dev/debug/remoteCallUrlTemplate');
						$linkTemplate = '<a href="%s" onclick="var ajax = new XMLHttpRequest(); ajax.open(\'GET\', this.href); ajax.send(null); return false">%s</a>';
						$url = sprintf($remoteCallUrlTemplate, $tmp['file'], intval($tmp['line']));
						$output .= sprintf($linkTemplate, $url, htmlspecialchars($label));
					} else {
						$output .= htmlspecialchars($label);
					}

					$output .= '</span>';

					$output .= '</div>'; // class="label"

					$output .= '<div class="profiler-columns">';
					foreach ($this->metrics as $metric) {
                        $formatterMethod = 'format_'.$metric;
                        $ownTitle = 'Own: ' . $helper->$formatterMethod($tmp[$metric.'_own']) . ' '
                            . $this->units[$metric] . ' / ' . round($tmp[$metric.'_rel_own'] * 100, 2) . '%';
                        $subTitle = 'Sub: ' . $helper->$formatterMethod($tmp[$metric.'_sub']) . ' '
                            . $this->units[$metric] . ' / ' . round($tmp[$metric.'_rel_sub'] * 100, 2) . '%';
                        $totalTitle = $helper->$formatterMethod($tmp[$metric.'_own'] + $tmp[$metric.'_sub']) . ' '
                            . $this->units[$metric] . ' / '
                            . round(($tmp[$metric.'_rel_own'] + $tmp[$metric.'_rel_sub']) * 100, 2) . '%';
                        $fullTitle = $totalTitle . ' (' . $ownTitle . ', ' . $subTitle . ')';

                        $output .= '<div class="metric" title="' . $fullTitle . '">';

                        $progressBar = $this->renderProgressBar(
                            $tmp[$metric . '_rel_own'] * 100,
                            $tmp[$metric . '_rel_sub'] * 100,
                            $tmp[$metric . '_rel_offset'] * 100
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

		if ( ! $this->getForceRender()
			&& (!$this->_beforeToHtml()
				|| !Mage::getStoreConfig('dev/debug/profiler')
				|| !Mage::helper('core')->isDevAllowed()
			)
		) {
			return '';
		}

		if (!Varien_Profiler::isEnabled() && ! $this->getForceRender()) {
			if (Mage::getStoreConfig('dev/debug/showDisabledMessage')) {

				// Adding css. Want to be as obtrusive as possible and not add any file to the header (as bundling might be influenced by this)
				// That's why I'm embedding css here into the html code...
				$output = '<style type="text/css">'.Mage::helper('aoe_profiler')->getSkinFileContent('aoe_profiler/css/styles.css').'</style>';

				$url = Mage::helper('core/url')->getCurrentUrl();
				$url .= (strpos($url, '?') === false) ? '?' : '&';
				$url .= 'profile=1';

                $remoteCallUrl = $url . '&links=1';

				$output .= '<div id="profiler">
					<p class="hint">Add <a href="'.$url.'#profiler">?profile=1</a> to the url to enable <strong>profiling</strong>.</p>
					<p class="hint">If you\'re using PHPStorm and have the RemoteCall plugin installed append <a href="'.$remoteCallUrl.'#profiler">?profile=1&links=1</a> to the url to enable <strong>profiling including links to PHPStorm</strong> (this might be a slower).</p>
					<p class="hint-small">(This message can be hidden in System > Configuration > Developer > Profiler.)</p>
				</div>';

				return $output;
			}
			return '';
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

        $hideLinesFasterThan = intval(Mage::getStoreConfig('dev/debug/hideLinesFasterThan'));

        $title = $this->getTitle() ? $this->getTitle() : 'Profiler';

        $output .= <<<HTML
            <div id="profiler"><h1>{$title}</h1>
            <div id="p-filter">
                <form id="filter-form">
                    <div class="form-block">
                        <label for="text-filter">Search for:</label>
                        <input type="text" id="text-filter" value="" placeholder="Text or regular expression"
                               title="Text or regular expression" />
                        (RegExp is allowed)
                        <br />
                        <input type="checkbox" id="text-filter-case-sensitivity" value="" />
                        <label for="text-filter-case-sensitivity">Case sensitive</label>
                        <input type="checkbox" id="show-matches-descendants" value="" />
                        <label for="show-matches-descendants">Do not hide matches' descendants</label>
                    </div>
                    <div class="form-block" style="padding-left: 15px">
                        <div>Hide entries faster than
                            <input type="text" id="duration-filter" value="{$hideLinesFasterThan}" /> ms:
                            <button>Filter!</button>
                        </div>
                        <div id="p-track">
                            <div id="p-handle" class="selected" title="Drag me!">
                                <img src="{$this->getSkinUrl('aoe_profiler/img/slider.png')}" />
                            </div>
                        </div>
                    </div>
                </form>
            </div>
HTML;

        if (Mage::getSingleton('core/resource')->getConnection('core_read')->getProfiler()->getEnabled()) {
            $output .= '<p>Number of database queries: '. Mage::getSingleton('core/resource')->getConnection('core_read')->getProfiler()->getTotalNumQueries() . '</p>';
        }

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
					<div class="profiler-label">'.$this->__('Name').'
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
	 * @return string
	 */
	protected function renderProgressBar($percent1, $percent2=0, $offset=0) {
		$percent1 = round(max(1, $percent1));
		$offset = round(max(0, $offset));
		$offset = round(min(99, $offset));

		$output = '<div class="progress">';
			$output .= '<div class="progress-bar">';
				$output .= '<div class="progress-bar1" style="width: '.$percent1.'%; margin-left: '.$offset.'%;"></div>';

				if ($percent2 > 0) {
					$percent2 = round(max(1, $percent2));
					if ($percent1 + $percent2 + $offset > 100) {
						// preventing line break in css progress bar if widths and margins are bigger than 100%
						$percent2 = 100 - $percent1 - $offset;
						$percent2 = max(0, $percent2);
					}
					$output .= '<div class="progress-bar2" style="width: '.$percent2.'%"></div>';
				}

				$output .= '</div>';
		$output .= '</div>';
		return $output;
	}

}
