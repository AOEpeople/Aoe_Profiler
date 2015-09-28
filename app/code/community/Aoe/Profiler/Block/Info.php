<?php

class Aoe_Profiler_Block_Info extends Mage_Adminhtml_Block_Template
{
    /**
     * @return Aoe_Profiler_Model_Run
     */
    public function getStack()
    {
        return Mage::registry('current_stack_instance');
    }
}
