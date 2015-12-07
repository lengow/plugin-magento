<?php


$installer = $this;
$installer->startSetup();

$installer->removeAttribute('catalog_product','lengow_product');

$attribute = $installer->getAttribute('catalog_product','lengow_product');

if(!$attribute) {
    $installer->addAttribute('catalog_product', 'lengow_product', array(
        'type' => 'int',
        'backend' => '',
        'frontend' => '',
        'label' => 'Publish on Lengow',
        'input' => 'boolean',
        'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'visible' => 1,
        'required' => 0,
        'user_defined' => 1,
        'default' => 1,
        'searchable' => 0,
        'filterable' => 0,
        'comparable' => 0,
        'visible_on_front' => 1,
        'unique' => 0,
        'used_in_product_listing' => 1
    ));
}

$tableName = $installer->getTable('lengow_log');
if ($installer->getConnection()->isTableExists($tableName) != true) {
    $table = $installer->getConnection()
        ->newTable($installer->getTable('lengow_log'))
        ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary' => true,
        ), 'Id')
        ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
            'nullable' => false,
        ), 'Date')
        ->addColumn('message', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'nullable' => false,
        ), 'Message');
    $installer->getConnection()->createTable($table);
}

$installer->endSetup();