<?php
/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$installer = $this;
$installer->startSetup();

// *********************************************************
//                   Create Lengow attributes
// *********************************************************

// create attribute lengow_product for product
$lengow_product = $installer->getAttribute('catalog_product', 'lengow_product');
if (!$lengow_product) {
    $installer->addAttribute(
        'catalog_product',
        'lengow_product',
        array(
            'type'         => 'int',
            'backend'      => '',
            'frontend'     => '',
            'label'        => 'Publish on Lengow',
            'input'        => 'boolean',
            'global'       => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
            'visible'      => 1,
            'required'     => 0,
            'user_defined' => 1,
            'default'      => 1,
            'searchable'   => 0,
            'filterable'   => 0,
            'comparable'   => 0,
            'unique'       => 0,
            'visible_on_front'        => 0,
            'used_in_product_listing' => 1
        )
    );
} else {
    $installer->updateAttribute('catalog_product', 'lengow_product', 'is_visible_on_front', 0);
    $installer->updateAttribute(
        'catalog_product',
        'lengow_product',
        'is_global',
        Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE
    );
}

// create attribute from_lengow for customer
$from_lengow_customer = $installer->getAttribute('customer', 'from_lengow');
if (!$from_lengow_customer) {
    $installer->addAttribute(
        'customer',
        'from_lengow',
        array(
            'type'       => 'int',
            'label'      => 'From Lengow',
            'visible'    => true,
            'required'   => false,
            'unique'     => false,
            'sort_order' => 700,
            'default'    => 0,
            'input'      => 'select',
            'source'     => 'eav/entity_attribute_source_boolean'
        )
    );
    $usedInForms = array(
        'adminhtml_customer',
    );
    $attribute = Mage::getSingleton('eav/config')->getAttribute('customer', 'from_lengow');
    $attribute->setData('used_in_forms', $usedInForms);
    $attribute->setData('sort_order', 700);
    $attribute->save();
}

// create attribute from_lengow for order
$from_lengow_order = $installer->getAttribute('order', 'from_lengow');
if (!$from_lengow_order) {
    $installer->addAttribute(
        'order',
        'from_lengow',
        array(
            'name'       => 'from_lengow',
            'label'      => 'From Lengow',
            'type'       => 'int',
            'visible'    => true,
            'required'   => false,
            'unique'     => false,
            'filterable' => 1,
            'sort_order' => 700,
            'default'    => 0,
            'input'      => 'select',
            'source'     => 'eav/entity_attribute_source_boolean',
            'grid'       => true
        )
    );
}

// *********************************************************
//          Add Lengow attributes in product page
// *********************************************************

$new_attributes = array("lengow_product");
// Add new Attribute group
$groupName = 'Lengow';
$entityTypeId = $installer->getEntityTypeId('catalog_product');
//Add group Lengow in all Attribute Set
$attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection')->load();
foreach ($attributeSetCollection as $id => $attributeSet) {
    // Add group lengow in attribute set
    $installer->addAttributeGroup($entityTypeId, $attributeSet->getId(), $groupName, 100);
    $attributeGroupId = $installer->getAttributeGroupId($entityTypeId, $attributeSet->getId(), $groupName);
    // Add new attribute (lengow_product) on Group (Lengow)
    foreach ($new_attributes as $attribute_code) {
        $attributeId = $installer->getAttributeId('catalog_product', $attribute_code);
        $entityTypeId = $attributeSet->getEntityTypeId();
        $installer->addAttributeToGroup($entityTypeId, $attributeSet->getId(), $attributeGroupId, $attributeId, null);
    }
}

// *********************************************************
//                  Create Lengow tables
// *********************************************************

// create table lengow_order
$tableName = $installer->getTable('lengow_order');
if ($installer->getConnection()->isTableExists($tableName) != true) {
    $table = $installer->getConnection()
        ->newTable($installer->getTable('lengow_order'))
        ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity'  => true,
            'unsigned'  => true,
            'nullable'  => false,
            'primary'   => true
        ), 'Id')
        ->addColumn('id_order', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable'  => true,
            'unsigned'  => true,
            'default'   => null
        ), 'Id Order')
        ->addColumn('id_store', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable'  => false,
            'unsigned'  => true
        ), 'Id Store')
        ->addColumn('id_feed', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable'  => true,
            'unsigned'  => true,
            'default'   => null
        ), 'Id Feed')
        ->addColumn('delivery_address_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable'  => true,
            'unsigned'  => true,
            'default'   => null
        ), 'Delivery Address Id')
        ->addColumn('delivery_country_iso', Varien_Db_Ddl_Table::TYPE_TEXT, 3, array(
            'nullable'  => true,
            'default'   => null,
            'length'    => 3
        ), 'Delivery Country Iso')
        ->addColumn('marketplace_sku', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array(
            'nullable'  => false,
            'length'    => 100
        ), 'Marketplace Sku')
        ->addColumn('markeplace_name', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array(
            'nullable'  => false,
            'length'    => 100
        ), 'Marketplace Name')
        ->addColumn('marketplace_label', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array(
            'nullable'  => true,
            'default'   => null,
            'length'    => 100
        ), 'Marketplace Label')
        ->addColumn('order_lengow_state', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array(
            'nullable'  => false,
            'length'    => 100
        ), 'Order Lengow State')
        ->addColumn('order_process_state', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
            'nullable'  => false,
            'unsigned'  => true
        ), 'Order Process State')
        ->addColumn('order_date', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
            'nullable'  => false
        ), 'Order Date')
        ->addColumn('order_item', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
            'nullable'  => true,
            'unsigned'  => true,
            'default'   => null
        ), 'Order Item')
        ->addColumn('currency', Varien_Db_Ddl_Table::TYPE_TEXT, 3, array(
            'nullable'  => true,
            'default'   => null,
            'length'    => 3
        ), 'Currency')
        ->addColumn('total_paid', Varien_Db_Ddl_Table::TYPE_DECIMAL, null, array(
            'nullable'  => true,
            'unsigned'  => true,
            'precision' => 17,
            'scale'     => 2,
            'default'   => null
        ), 'Total Paid')
        ->addColumn('commission', Varien_Db_Ddl_Table::TYPE_DECIMAL, null, array(
            'nullable'  => true,
            'unsigned'  => true,
            'precision' => 17,
            'scale'     => 2,
            'default'   => null
        ), 'Commission')
        ->addColumn('customer_name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
            'nullable'  => true,
            'length'    => 255,
            'default'   => null
        ), 'Customer Name')
        ->addColumn('carrier', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array(
            'nullable'  => true,
            'length'    => 100,
            'default'   => null
        ), 'Carrier')
        ->addColumn('carrier_method', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array(
            'nullable'  => true,
            'length'    => 100,
            'default'   => null
        ), 'Carrier Method')
        ->addColumn('carrier_tracking', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array(
            'nullable'  => true,
            'length'    => 100,
            'default'   => null
        ), 'Carrier Tracking')
        ->addColumn('carrier_id_relay', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array(
            'nullable'  => true,
            'length'    => 100,
            'default'   => null
        ), 'Carrier Id Relay')
        ->addColumn('sent_marketplace', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array(
            'nullable'  => false,
            'default'   => 0
        ), 'Sent Marketplace')
        ->addColumn('is_reimported', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array(
            'nullable'  => false,
            'default'   => 0
        ), 'Is Reimported')
        ->addColumn('message', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'nullable'  => true,
            'default'   => null
        ), 'Message')
        ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
            'nullable'  => false,
        ), 'Created At')
        ->addColumn('extra', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'nullable'  => true,
            'default'   => null
        ), 'Extra');
    $installer->getConnection()->createTable($table);
}

// create table lengow_order_line
$tableName = $installer->getTable('lengow_order_line');
if ($installer->getConnection()->isTableExists($tableName) != true) {
    $table = $installer->getConnection()
        ->newTable($installer->getTable('lengow_order_line'))
        ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity'  => true,
            'unsigned'  => true,
            'nullable'  => false,
            'primary'   => true,
        ), 'Id')
        ->addColumn('id_order', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable'  => false,
            'unsigned'  => true
        ), 'Id Order')
        ->addColumn('id_order_line', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array(
            'nullable'  => false,
            'length'    => 100
        ), 'Id Order Line');
    $installer->getConnection()->createTable($table);
}

// create table lengow_order_error
$tableName = $installer->getTable('lengow_order_error');
if ($installer->getConnection()->isTableExists($tableName) != true) {
    $table = $installer->getConnection()
        ->newTable($installer->getTable('lengow_order_error'))
        ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity'  => true,
            'unsigned'  => true,
            'nullable'  => false,
            'primary'   => true,
        ), 'Id')
        ->addColumn('id_order_lengow', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable'  => false,
            'unsigned'  => true
        ), 'Id Order Lengow')
        ->addColumn('message', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'nullable'  => true,
            'default'   => null
        ), 'Message')
        ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
            'nullable'  => false,
        ), 'Created At')
        ->addColumn('type', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable'  => false,
            'unsigned'  => true
        ), 'Type')
        ->addColumn('is_finished', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array(
            'nullable'  => false,
            'default'   => 0
        ), 'Is Finished')
        ->addColumn('mail', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array(
            'nullable'  => false,
            'default'   => 0
        ), 'Mail');
    $installer->getConnection()->createTable($table);
}

// create table lengow_action
$tableName = $installer->getTable('lengow_action');
if ($installer->getConnection()->isTableExists($tableName) != true) {
    $table = $installer->getConnection()
        ->newTable($installer->getTable('lengow_action'))
        ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity'  => true,
            'unsigned'  => true,
            'nullable'  => false,
            'primary'   => true,
        ), 'Id')
        ->addColumn('id_order', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable'  => false,
            'unsigned'  => true
        ), 'Id Order')
        ->addColumn('id_action', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable'  => false,
            'unsigned'  => true
        ), 'Id Action')
        ->addColumn('order_line_sku', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array(
            'nullable'  => true,
            'length'    => 100,
            'default'   => null
        ), 'Order Line Sku')
        ->addColumn('action_type', Varien_Db_Ddl_Table::TYPE_TEXT, 32, array(
            'nullable'  => false,
            'length'    => 32
        ), 'Action Type')
        ->addColumn('retry', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
            'nullable'  => false,
            'unsigned'  => true,
            'default'   => 0
        ), 'Retry')
        ->addColumn('parameters', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'nullable'  => false
        ), 'Parameters')
        ->addColumn('state', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
            'nullable'  => false,
            'unsigned'  => true
        ), 'State')
        ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
            'nullable'  => false
        ), 'Created At')
        ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
            'nullable'  => false
        ), 'Updated At');
    $installer->getConnection()->createTable($table);
}

// create table lengow_log
$tableName = $installer->getTable('lengow_log');
if ($installer->getConnection()->isTableExists($tableName) != true) {
    $table = $installer->getConnection()
        ->newTable($installer->getTable('lengow_log'))
        ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity'  => true,
            'unsigned'  => true,
            'nullable'  => false,
            'primary'   => true,
        ), 'Id')
        ->addColumn('date', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
            'nullable'  => false,
        ), 'Date')
        ->addColumn('message', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'nullable'  => false,
        ), 'Message');
    $installer->getConnection()->createTable($table);
}

$installer->endSetup();
