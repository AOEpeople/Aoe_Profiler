<?php

/**
 * Profiler output block
 *
 * @author Fabrizio Branca
 */
class Aoe_Profiler_Block_Profiler extends Mage_Core_Block_Abstract
{

//        if (!Varien_Profiler::isEnabled() && !$this->getForceRender()) {
//            if (Mage::getStoreConfig('dev/debug/showDisabledMessage')) {
//
//                // Adding css. Want to be as obtrusive as possible and not add any file to the header (as bundling might be influenced by this)
//                // That's why I'm embedding css here into the html code...
//                $output = '<style type="text/css">' . Mage::helper('aoe_profiler')->getSkinFileContent('aoe_profiler/css/styles.css') . '</style>';
//
//                $url = Mage::helper('core/url')->getCurrentUrl();
//                $url .= (strpos($url, '?') === false) ? '?' : '&';
//                $url .= 'profile=1';
//
//                $remoteCallUrl = $url . '&links=1';
//
//                $output .= '<div id="profiler">
//                    <p class="hint">Add <a href="' . $url . '#profiler">?profile=1</a> to the url to enable <strong>profiling</strong>.</p>
//                    <p class="hint">If you\'re using PHPStorm and have the RemoteCall plugin installed append <a href="' . $remoteCallUrl . '#profiler">?profile=1&links=1</a> to the url to enable <strong>profiling including links to PHPStorm</strong> (this might be a slower).</p>
//                    <p class="hint-small">(This message can be hidden in System > Configuration > Developer > Profiler.)</p>
//                </div>';
//
//                return $output;
//            }
//            return '';
//        }


}
