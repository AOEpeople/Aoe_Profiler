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
            'onclick'   => 'setLocation(\'' . $this->getUrl('*/*/') . '\')',
            'class'     => 'back',
        ), -1);
    }
}
