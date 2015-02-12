<?php

class Aoe_Profiler_Model_Cron
{

    public function cleanup()
    {
        $days = Mage::getStoreConfig('dev/debug/keepDays');
        Mage::getResourceModel('aoe_profiler/run')->deleteOlderThan($days);
    }

}