<?php
/**
 * Used in creating options for Yes|No config value selection
 *
 */
class Aoe_Profiler_Model_Source_Backtraces
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
            array('value' => 'parameter', 'label' => Mage::helper('aoe_profiler')->__('Only if paramter/cookie "links" is true')),
            array('value' => 'never', 'label' => Mage::helper('aoe_profiler')->__('Never')),
        );
    }

}
