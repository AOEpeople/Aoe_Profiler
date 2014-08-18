<?php
/**
 * Used in creating options for Yes|No config value selection
 *
 */
class Aoe_Profiler_Model_Source_Trigger
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'always', 'label' => Mage::helper('aoe_profiler')->__('Always on')),
            array('value' => 'parameter', 'label' => Mage::helper('aoe_profiler')->__('Only if paramter/cookie "profile" is true')),
        );
    }

}
