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

    protected function _prepareMassaction() {
        $this->setMassactionIdField('stack_id');
        $this->getMassactionBlock()->setFormFieldName('stack');

        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('aoe_profiler')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('aoe_profiler')->__('Are you sure?')
        ) );
    }

    /**
     * Prepare grid collection object
     *
     * @return Aoe_Profiler_Model_Resource_Run_Collection
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('aoe_profiler/run')->getCollection(); /* @var $collection Aoe_Profiler_Model_Resource_Run_Collection */
        $collection->addFieldToSelect(array('id','created_at', 'route', 'url', 'session_id', 'total_time', 'total_memory'));
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
            'header'    => Mage::helper('aoe_profiler')->__('Id'),
            'align'     => 'left',
            'index'     => 'id',
            'type'      => 'number',
        ));

        $this->addColumn('created_at', array(
            'header'    => Mage::helper('aoe_profiler')->__('Created'),
            'align'     => 'left',
            'index'     => 'created_at',
            'type'      => 'date',
            'format' => Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM)
        ));

        $this->addColumn('route', array(
            'header'    => Mage::helper('aoe_profiler')->__('Route'),
            'align'     => 'left',
            'index'     => 'route',
        ));

        $this->addColumn('url', array(
            'header'    => Mage::helper('aoe_profiler')->__('Url'),
            'align'     => 'left',
            'index'     => 'url',
        ));

        $this->addColumn('session_id', array(
            'header'    => Mage::helper('aoe_profiler')->__('Session Id'),
            'align'     => 'left',
            'index'     => 'session_id',
        ));

        $this->addColumn('total_time', array(
            'header'    => Mage::helper('aoe_profiler')->__('Time [sec]'),
            'align'     => 'right',
            'index'     => 'total_time',
            'type'      => 'number',
        ));

        $this->addColumn('total_memory', array(
            'header'    => Mage::helper('aoe_profiler')->__('Memory [MB]'),
            'align'     => 'right',
            'index'     => 'total_memory',
            'type'      => 'number',
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
