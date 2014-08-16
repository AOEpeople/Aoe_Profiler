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
        // - configure which requests to log (admin vs. fe? only if parameter is set? only for a given threshold? configurable white/blacklist?)
        if (!Mage::app()->getStore()->isAdmin()) {
            $stack = Mage::getModel('aoe_profiler/stack'); /* @var $stack Aoe_Profiler_Model_Stack */
            $stack->loadStackLogFromProfiler();
            $stack->populateMetata();
            $stack->save();
        }
    }

}