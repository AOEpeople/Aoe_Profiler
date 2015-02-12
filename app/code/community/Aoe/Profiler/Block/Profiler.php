<?php

/**
 * Profiler output block
 *
 * @author Fabrizio Branca
 */
class Aoe_Profiler_Block_Profiler extends Mage_Core_Block_Abstract
{

    /**
     * Now that we have a admin module for this we never want to show anything appended to any page...
     *
     * @return string
     */
    protected function _toHtml()
    {
        return '';
    }

}
