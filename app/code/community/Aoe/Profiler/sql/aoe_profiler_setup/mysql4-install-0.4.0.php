<?php

/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

/**
 * Create table 'aoe_profiler/profile'
 */
$table = $this->getConnection()
    ->newTable($this->getTable('aoe_profiler/stack'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary' => true,
    ), 'Profile Id')
    ->addColumn('stack_data', Varien_Db_Ddl_Table::TYPE_BLOB, null, array(), 'Data')
    ->addColumn('route', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(), 'Route')
    ->addColumn('url', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(), 'Url')
    ->addColumn('total_time', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(), 'Total Time')
    ->addColumn('total_memory', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(), 'Total Memory');
$this->getConnection()->createTable($table);

$this->endSetup();
