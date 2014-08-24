<?php
/**
 * Class Aoe_Profiler_Model_Resource_Run
 *
 * @author Fabrizio Branca
 * @since 2014-02-01
 */
class Aoe_Profiler_Model_Resource_Run extends Mage_Core_Model_Resource_Db_Abstract {

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('aoe_profiler/run', 'id');
    }

    public function deleteOlderThan($days) {
        $days = max(1, intval($days));
        $this->_getWriteAdapter()->delete(
            $this->getMainTable(),
            'created_at < NOW() - INTERVAL '.$days.' DAY'
        );
    }

}
