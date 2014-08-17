<?php

class Aoe_Profiler_Model_Observer
{

    public function core_block_abstract_to_html_before($event)
    {
        $block = $event->getBlock(); /* @var $block Mage_Core_Block_Abstract */
        Varien_Profiler::start($block->getNameInLayout(), Varien_Profiler::TYPE_BLOCK);
    }

    public function core_block_abstract_to_html_after($event)
    {
        $block = $event->getBlock(); /* @var $block Mage_Core_Block_Abstract */
        Varien_Profiler::stop($block->getNameInLayout());
    }

    /**
     * Perist profile
     *
     * This method is called from the controller_front_send_response_after event
     *
     * @author Fabrizio Branca
     * @since 2014-02-01
     *
     * @param Varien_Event_Observer $event
     */
    public function persistProfile(Varien_Event_Observer $event) {
        // TODO:
        // - configure which requests to log
        // - admin vs. fe?
        // - only if parameter is set?
        // - only for a given threshold? configurable white/blacklist?
        // - only from a given IP?
        // - only a small sample?
        if (Varien_Profiler::isEnabled() && !Mage::app()->getStore()->isAdmin()) {
            $run = Mage::getModel('aoe_profiler/run'); /* @var $run Aoe_Profiler_Model_Run */
            $run->loadStackLogFromProfiler();
            $run->populateMetata();

            $totals = Varien_Profiler::getTotals();
            $run->setTotalTime($totals['time']);
            $run->setTotalMemory($totals['realmem']);

            $run->save();
        }
    }

}