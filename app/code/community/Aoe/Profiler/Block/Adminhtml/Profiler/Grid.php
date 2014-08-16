<?php
/**
 * Stack grid block
 *
 * @author Fabrizio Branca
 * @since 2014-08-15
 */
class Aoe_Profiler_Block_Adminhtml_Profiler_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Set defaults
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('stackGrid');
        $this->setDefaultSort('stack_id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * Prepare grid collection object
     *
     * @return Aoe_Profiler_Model_Resource_Stack_Collection
     */
    protected function _prepareCollection()
    {
        /* @var $collection Aoe_Profiler_Model_Resource_Stack_Collection */
        $collection = Mage::getModel('aoe_profiler/stack')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare grid columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn('stack_id', array(
            'header'    => Mage::helper('aoe_layout')->__('Id'),
            'align'     => 'left',
            'index'     => 'id',
        ));

        $this->addColumn('created_at', array(
            'header'    => Mage::helper('aoe_layout')->__('Created'),
            'align'     => 'left',
            'index'     => 'created_at',
            'type'      => 'date',
            'format' => Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM)
        ));

        $this->addColumn('route', array(
            'header'    => Mage::helper('aoe_layout')->__('Route'),
            'align'     => 'left',
            'index'     => 'route',
        ));

        $this->addColumn('url', array(
            'header'    => Mage::helper('aoe_layout')->__('Url'),
            'align'     => 'left',
            'index'     => 'url',
        ));

        $this->addColumn('total_time', array(
            'header'    => Mage::helper('aoe_layout')->__('Time'),
            'align'     => 'left',
            'index'     => 'total_time',
        ));

        $this->addColumn('total_memory', array(
            'header'    => Mage::helper('aoe_layout')->__('Memory'),
            'align'     => 'left',
            'index'     => 'total_memory',
        ));

        return parent::_prepareColumns();
    }

    /**
     * Row click url
     *
     * @param object $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/view', array('stack_id' => $row->getId()));
    }

    /**
     * Define row click callback
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
}