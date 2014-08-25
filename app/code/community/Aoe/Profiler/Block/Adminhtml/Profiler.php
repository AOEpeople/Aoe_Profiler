<?php
/**
 * Grid
 *
 * @author Fabrizio Branca
 * @since 2014-08-15
 */
class Aoe_Profiler_Block_Adminhtml_Profiler extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Block constructor
     */
    public function __construct()
    {
        $this->_blockGroup = 'aoe_profiler';
        $this->_controller = 'adminhtml_profiler';
        $this->_headerText = Mage::helper('aoe_profiler')->__('Runs');
        parent::__construct();
        $this->removeButton('add');
    }
}