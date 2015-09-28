<?php
/**
 * View
 *
 * @author Fabrizio Branca
 * @since 2014-08-15
 */
class Aoe_Profiler_Block_Adminhtml_Profiler_View extends Mage_Adminhtml_Block_Widget_Container
{

    protected $_template = 'aoe_profiler/view.phtml';

    protected function _construct()
    {
        parent::_construct();

        $this->_addButton('back', array(
            'label'     => Mage::helper('aoe_profiler')->__('Back'),
            'onclick'   => 'setLocation(\'' . $this->getBackUrl() . '\')',
            'class'     => 'back',
        ), -1);

        if ($this->getStack()->getId()) {
            $this->_addButton('delete', array(
                'label'     => Mage::helper('aoe_profiler')->__('Delete'),
                'class'     => 'delete',
                'onclick'   => 'deleteConfirm(\''. Mage::helper('aoe_profiler')->__('Are you sure you want to do this?')
                               .'\', \'' . $this->getDeleteUrl() . '\')',
            ));
        }
    }

    /**
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/*/');
    }

    /**
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', array('stack_id' => $this->getStack()->getId()));
    }

    /**
     * @return Aoe_Profiler_Model_Run
     */
    public function getStack()
    {
        return Mage::registry('current_stack_instance');
    }
}
