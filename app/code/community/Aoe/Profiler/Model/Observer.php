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
        // TODO: currently admin requests will not be logged. Later this should be controlled via a white/blacklist
        if (Varien_Profiler::isEnabled() /* && !Mage::app()->getStore()->isAdmin() */) {
            $totals = Varien_Profiler::getTotals();
            $conf = Varien_Profiler::getConfiguration();

            if ((!$conf->filters->timeThreshold || $totals['time'] > $conf->filters->timeThreshold) &&
                (!$conf->filters->memoryThreshold || $totals['realmem'] > $conf->filters->memoryThreshold)
            ) {
                $run = Mage::getModel('aoe_profiler/run'); /* @var $run Aoe_Profiler_Model_Run */
                $run->loadStackLogFromProfiler();
                $run->populateMetata();

                $run->setTotalTime($totals['time']);
                $run->setTotalMemory((float)$totals['realmem']/(1024*1024));

                try {
                    $run->save();
                } catch (Exception $e) {
                    Mage::log('Error while saving Aoe_Profiler data: '. $e->getMessage());
                }
            }
        }
    }

}