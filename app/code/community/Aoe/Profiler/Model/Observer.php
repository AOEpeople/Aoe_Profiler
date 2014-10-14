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
     * Persist profile
     *
     * This method is called from the controller_front_send_response_after event
     *
     * @author Fabrizio Branca
     * @since 2014-02-01
     *
     * @param Varien_Event_Observer $event
     */
    public function persistProfile(Varien_Event_Observer $event) {
        if (!method_exists('Varien_Profiler', 'isEnabled')) {
            Mage::log('Looks like the wrong Profiler class is being loaded at this point.');
            return;
        }
        if (Varien_Profiler::isEnabled() && Varien_Profiler::checkThresholds()) {
            $run = Mage::getModel('aoe_profiler/run'); /* @var $run Aoe_Profiler_Model_Run */
            $run->loadStackLogFromProfiler();
            $run->populateMetadata();
            try {
                $run->save();
            } catch (Exception $e) {
                Mage::log('Error while saving Aoe_Profiler data: '. $e->getMessage());
            }
        }
    }

}