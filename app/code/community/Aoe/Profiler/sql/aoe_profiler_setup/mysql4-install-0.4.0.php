<?php

/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

$this->getConnection()->dropTable($this->getTable('aoe_profiler/run'));

/**
 * Create table 'aoe_profiler/profile'
 */
$table = $this->getConnection()
    ->newTable($this->getTable('aoe_profiler/run'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary' => true,
    ), 'Profile Id')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(), 'Creation time')
    ->addColumn('stack_data', Varien_Db_Ddl_Table::TYPE_VARBINARY, Varien_Db_Ddl_Table::MAX_VARBINARY_SIZE, array(), 'Data')
    ->addColumn('route', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(), 'Route')
    ->addColumn('url', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(), 'Url')
    ->addColumn('session_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(), 'Session ID')
    ->addColumn('total_time', Varien_Db_Ddl_Table::TYPE_FLOAT, null, array(), 'Total Time in seconds')
    ->addColumn('total_memory', Varien_Db_Ddl_Table::TYPE_FLOAT, null, array(), 'Total Memory in MB');
$this->getConnection()->createTable($table);

$this->endSetup();