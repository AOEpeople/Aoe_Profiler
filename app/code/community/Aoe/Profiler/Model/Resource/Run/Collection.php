<?php

/**
 * Stack Collection
 *
 * @author Fabrizio Branca
 * @since 2014-08-07
 */
class Aoe_Profiler_Model_Resource_Run_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Initialize resource collection
     */
    public function _construct()
    {
        $this->_init('aoe_profiler/run');
    }

}
