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

    /**
     * Prepare layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->_addButton(
            'to_html',
            array(
                'label' => $this->__('To Html'),
                'onclick' => "deleteConfirm('Would you like to export as HTML?','{$this->getUrl('*/*/render', array('_current' => true))}')",
                'class' => 'button'
            )
        );
        $this->_addButton(
            'delete',
            array(
                'label' => $this->__('Delete'),
                'onclick' => "deleteConfirm('Would you like to delete this profile?','{$this->getUrl('*/*/delete', array('_current' => true))}')",
                'class' => 'delete'
            )
        );
        return parent::_prepareLayout();
    }
}