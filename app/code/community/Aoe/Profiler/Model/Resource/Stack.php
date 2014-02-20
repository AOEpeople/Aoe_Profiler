<?php
/**
 * Class ${NAME}
 *
 * @author Fabrizio Branca
 * @since 2014-02-01
 */
class Aoe_Profiler_Model_Resource_Stack extends Mage_Core_Model_Resource_Db_Abstract {

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('aoe_profiler/stack', 'id');
    }

}
