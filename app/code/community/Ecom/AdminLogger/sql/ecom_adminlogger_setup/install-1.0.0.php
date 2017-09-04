<?php

$installer = $this;
$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($installer->getTable('adminlogger/log'))
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary' => true,
        ), 'Id')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable' => false,
    ), 'Created At')
    ->addColumn('username', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => false,
    ), 'Username')
    ->addColumn('action_type', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => true,
    ), 'Action Type(comment)')
    ->addColumn('controller', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => true,
    ), 'Controller')    
    ->addColumn('store', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => false,
    ), 'Store Code')
    ->addColumn('item', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable' => true,
    ), 'Item - ID')
    ->setComment('Ecom_Logger');
$installer->getConnection()->createTable($table);

$change = $installer->getConnection()
    ->newTable($installer->getTable('adminlogger/details'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Change Id')
    ->addColumn('source_name', Varien_Db_Ddl_Table::TYPE_TEXT, 150, array(
        ), 'Logged Source Name')
    ->addColumn('event_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        ), 'Logged event id')
    ->addColumn('source_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        ), 'Logged Source Id')
    ->addColumn('property_name', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        ), 'Logged Property name')
	->addColumn('original_data', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array(
        ), 'Logged Original Data')
    ->addColumn('result_data', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array(
        ), 'Logged Result Data')
    ->setComment('Ecom_Logger changes');
$installer->getConnection()->createTable($change);

$installer->endSetup();