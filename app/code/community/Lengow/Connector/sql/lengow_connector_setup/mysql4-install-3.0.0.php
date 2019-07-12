<?php
/**
 * Copyright 2017 Lengow SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @subpackage  sql
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$installer = $this;
$installer->startSetup();

// *********************************************************
//                   Create Lengow attributes
// *********************************************************

// create attribute lengow_product for product
$lengowProduct = $installer->getAttribute('catalog_product', 'lengow_product');
if (!$lengowProduct) {
    $installer->addAttribute(
        'catalog_product',
        'lengow_product',
        array(
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
            'unique' => 0,
            'visible_on_front' => 0,
            'used_in_product_listing' => 1,
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
$fromLengowCustomer = $installer->getAttribute('customer', 'from_lengow');
if (!$fromLengowCustomer) {
    $installer->addAttribute(
        'customer',
        'from_lengow',
        array(
            'type' => 'int',
            'label' => 'From Lengow',
            'visible' => true,
            'required' => false,
            'unique' => false,
            'sort_order' => 700,
            'default' => 0,
            'input' => 'select',
            'source' => 'eav/entity_attribute_source_boolean',
        )
    );
    $usedInForms = array('adminhtml_customer');
    $attribute = Mage::getSingleton('eav/config')->getAttribute('customer', 'from_lengow');
    $attribute->setData('used_in_forms', $usedInForms);
    $attribute->setData('sort_order', 700);
    $attribute->save();
}

// create attribute from_lengow for order
$listAttributes = array();
$listAttributes[] = array(
    'name' => 'from_lengow',
    'label' => 'From Lengow',
    'type' => 'int',
    'input' => 'select',
    'source' => 'eav/entity_attribute_source_boolean',
    'default' => 0,
    'grid' => true,
);
$listAttributes[] = array(
    'name' => 'order_id_lengow',
    'label' => 'Lengow order ID',
    'type' => 'text',
    'input' => 'text',
    'source' => '',
    'default' => '',
    'grid' => true,
);
$listAttributes[] = array(
    'name' => 'feed_id_lengow',
    'label' => 'Feed ID',
    'type' => 'float',
    'input' => 'text',
    'source' => '',
    'default' => 0,
    'grid' => false,
);
$listAttributes[] = array(
    'name' => 'marketplace_lengow',
    'label' => 'marketplace',
    'type' => 'text',
    'input' => 'text',
    'source' => '',
    'default' => '',
    'grid' => true,
);
$listAttributes[] = array(
    'name' => 'delivery_address_id_lengow',
    'label' => 'Delivery address id lengow',
    'type' => 'int',
    'input' => 'text',
    'source' => '',
    'default' => 0,
    'grid' => false,
);
$listAttributes[] = array(
    'name' => 'is_reimported_lengow',
    'label' => 'Is Reimported Lengow',
    'type' => 'int',
    'input' => 'select',
    'source' => 'eav/entity_attribute_source_boolean',
    'default' => 0,
    'grid' => false,
);
$listAttributes[] = array(
    'name' => 'follow_by_lengow',
    'label' => 'Follow By Lengow',
    'type' => 'int',
    'input' => 'select',
    'source' => 'eav/entity_attribute_source_boolean',
    'default' => 1,
    'grid' => false,
);

foreach ($listAttributes as $attr) {
    $orderAttribute = $installer->getAttribute('order', $attr['name']);
    if (!$orderAttribute) {
        $installer->addAttribute(
            'order',
            $attr['name'],
            array(
                'name' => $attr['name'],
                'label' => $attr['label'],
                'type' => $attr['type'],
                'visible' => true,
                'required' => false,
                'unique' => false,
                'filterable' => 1,
                'sort_order' => 700,
                'default' => $attr['default'],
                'input' => $attr['input'],
                'source' => $attr['source'],
                'grid' => $attr['grid'],
            )
        );
    }
}

// *********************************************************
//          Add Lengow attributes in product page
// *********************************************************

$newAttributes = array('lengow_product');
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
    foreach ($newAttributes as $attributeCode) {
        $attributeId = $installer->getAttributeId('catalog_product', $attributeCode);
        $entityTypeId = $attributeSet->getEntityTypeId();
        $installer->addAttributeToGroup($entityTypeId, $attributeSet->getId(), $attributeGroupId, $attributeId, null);
    }
}

// *********************************************************
//                  Create Lengow tables
// *********************************************************

// Compatibility for version 1.5
$typeText = Mage::getVersion() < '1.6.0.0' ? Varien_Db_Ddl_Table::TYPE_LONGVARCHAR : Varien_Db_Ddl_Table::TYPE_TEXT;

// create table lengow_order
$tableName = $installer->getTable('lengow_order');
if ((bool)$installer->getConnection()->showTableStatus($tableName) != true) {
    $table = $installer->getConnection()
        ->newTable($installer->getTable('lengow_order'))
        ->addColumn(
            'id',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            array(
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary' => true,
            ),
            'Id'
        )
        ->addColumn(
            'order_id',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            array(
                'nullable' => true,
                'unsigned' => true,
                'default' => null,
            ),
            'Order Id'
        )
        ->addColumn(
            'order_sku',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            40,
            array(
                'nullable' => true,
                'default' => null,
            ),
            'Order sku'
        )
        ->addColumn(
            'store_id',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            array(
                'nullable' => false,
                'unsigned' => true,
            ),
            'Store Id'
        )
        ->addColumn(
            'feed_id',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            array(
                'nullable' => true,
                'unsigned' => true,
                'default' => null,
            ),
            'Feed Id'
        )
        ->addColumn(
            'delivery_address_id',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            array(
                'nullable' => true,
                'unsigned' => true,
                'default' => null,
            ),
            'Delivery Address Id'
        )
        ->addColumn(
            'delivery_country_iso',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            3,
            array(
                'nullable' => true,
                'default' => null,
                'length' => 3,
            ),
            'Delivery Country Iso'
        )
        ->addColumn(
            'marketplace_sku',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            100,
            array(
                'nullable' => false,
                'length' => 100,
            ),
            'Marketplace Sku'
        )
        ->addColumn(
            'marketplace_name',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            100,
            array(
                'nullable' => false,
                'length' => 100,
            ),
            'Marketplace Name'
        )
        ->addColumn(
            'marketplace_label',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            100,
            array(
                'nullable' => true,
                'default' => null,
                'length' => 100,
            ),
            'Marketplace Label'
        )
        ->addColumn(
            'order_lengow_state',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            100,
            array(
                'nullable' => false,
                'length' => 100,
            ),
            'Order Lengow State'
        )
        ->addColumn(
            'order_process_state',
            Varien_Db_Ddl_Table::TYPE_SMALLINT,
            null,
            array(
                'nullable' => false,
                'unsigned' => true,
            ),
            'Order Process State'
        )
        ->addColumn(
            'order_date',
            Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            null,
            array(
                'nullable' => false,
            ),
            'Order Date'
        )
        ->addColumn(
            'order_item',
            Varien_Db_Ddl_Table::TYPE_SMALLINT,
            null,
            array(
                'nullable' => true,
                'unsigned' => true,
                'default' => null,
            ),
            'Order Item'
        )
        ->addColumn(
            'currency',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            3,
            array(
                'nullable' => true,
                'default' => null,
                'length' => 3,
            ),
            'Currency'
        )
        ->addColumn(
            'total_paid',
            Varien_Db_Ddl_Table::TYPE_DECIMAL,
            null,
            array(
                'nullable' => true,
                'unsigned' => true,
                'precision' => 17,
                'scale' => 2,
                'default' => null,
            ),
            'Total Paid'
        )
        ->addColumn(
            'commission',
            Varien_Db_Ddl_Table::TYPE_DECIMAL,
            null,
            array(
                'nullable' => true,
                'unsigned' => true,
                'precision' => 17,
                'scale' => 2,
                'default' => null,
            ),
            'Commission'
        )
        ->addColumn(
            'customer_name',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            255,
            array(
                'nullable' => true,
                'length' => 255,
                'default' => null,
            ),
            'Customer Name'
        )
        ->addColumn(
            'customer_email',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            255,
            array(
                'nullable' => true,
                'length' => 255,
                'default' => null,
            ),
            'Customer Email'
        )
        ->addColumn(
            'carrier',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            100,
            array(
                'nullable' => true,
                'length' => 100,
                'default' => null,
            ),
            'Carrier'
        )
        ->addColumn(
            'carrier_method',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            100,
            array(
                'nullable' => true,
                'length' => 100,
                'default' => null,
            ),
            'Carrier Method'
        )
        ->addColumn(
            'carrier_tracking',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            100,
            array(
                'nullable' => true,
                'length' => 100,
                'default' => null,
            ),
            'Carrier Tracking'
        )
        ->addColumn(
            'carrier_id_relay',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            100,
            array(
                'nullable' => true,
                'length' => 100,
                'default' => null,
            ),
            'Carrier Id Relay'
        )
        ->addColumn(
            'sent_marketplace',
            Varien_Db_Ddl_Table::TYPE_BOOLEAN,
            null,
            array(
                'nullable' => false,
                'default' => 0,
            ),
            'Sent Marketplace'
        )
        ->addColumn(
            'is_in_error',
            Varien_Db_Ddl_Table::TYPE_BOOLEAN,
            null,
            array(
                'nullable' => false,
                'default' => 0,
            ),
            'Is In Error'
        )
        ->addColumn(
            'message',
            $typeText,
            null,
            array(
                'nullable' => true,
                'default' => null,
            ),
            'Message'
        )
        ->addColumn(
            'created_at',
            Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            null,
            array(
                'nullable' => false,
            ),
            'Created At'
        )
        ->addColumn(
            'updated_at',
            Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            null,
            array(
                'nullable' => true,
                'default' => null,
            ),
            'Updated At'
        )
        ->addColumn(
            'extra',
            $typeText,
            null,
            array(
                'nullable' => true,
                'default' => null,
            ),
            'Extra'
        );
    $installer->getConnection()->createTable($table);
    // Compatibility with version 1.5
    if (Mage::getVersion() < '1.6.0.0') {
        $installer->getConnection()->modifyColumn($tableName, 'id', 'int(11) NOT NULL auto_increment');
        $installer->getConnection()->modifyColumn($tableName, 'total_paid', 'DECIMAL(17,2) UNSIGNED NULL');
        $installer->getConnection()->modifyColumn($tableName, 'commission', 'DECIMAL(17,2) UNSIGNED NULL');
    }
}

// create table lengow_order_line
$tableName = $installer->getTable('lengow_order_line');
if ((boolean)$installer->getConnection()->showTableStatus($tableName) != true) {
    $table = $installer->getConnection()
        ->newTable($installer->getTable('lengow_order_line'))
        ->addColumn(
            'id',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            array(
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary' => true,
            ),
            'Id'
        )
        ->addColumn(
            'order_id',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            array(
                'nullable' => false,
                'unsigned' => true,
            ),
            'Order Id'
        )
        ->addColumn(
            'order_line_id',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            100,
            array(
                'nullable' => false,
                'length' => 100,
            ),
            'Order Line Id'
        );
    $installer->getConnection()->createTable($table);
    // Compatibility with version 1.5
    if (Mage::getVersion() < '1.6.0.0') {
        $installer->getConnection()->modifyColumn($tableName, 'id', 'int(11) NOT NULL auto_increment');
    }
} else {
    if ($installer->getConnection()->tableColumnExists($tableName, 'id_order')) {
        $installer->getConnection()->changeColumn($tableName, 'id_order', 'order_id', 'int(11) UNSIGNED NOT NULL');
    }
    if ($installer->getConnection()->tableColumnExists($tableName, 'id_order_line')) {
        $installer->getConnection()->changeColumn(
            $tableName,
            'id_order_line',
            'order_line_id',
            'VARCHAR(100) NOT NULL'
        );
    }
}

// create table lengow_order_error
$tableName = $installer->getTable('lengow_order_error');
if ((boolean)$installer->getConnection()->showTableStatus($tableName) != true) {
    $table = $installer->getConnection()
        ->newTable($installer->getTable('lengow_order_error'))
        ->addColumn(
            'id',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            array(
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary' => true,
            ),
            'Id'
        )
        ->addColumn(
            'order_lengow_id',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            array(
                'nullable' => false,
                'unsigned' => true,
            ),
            'Order Lengow Id'
        )
        ->addColumn(
            'message',
            $typeText,
            null,
            array(
                'nullable' => true,
                'default' => null,
            ),
            'Message'
        )
        ->addColumn(
            'type',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            array(
                'nullable' => false,
                'unsigned' => true,
            ),
            'Type'
        )
        ->addColumn(
            'is_finished',
            Varien_Db_Ddl_Table::TYPE_BOOLEAN,
            null,
            array(
                'nullable' => false,
                'default' => 0,
            ),
            'Is Finished'
        )
        ->addColumn(
            'mail',
            Varien_Db_Ddl_Table::TYPE_BOOLEAN,
            null,
            array(
                'nullable' => false,
                'default' => 0,
            ),
            'Mail'
        )
        ->addColumn(
            'created_at',
            Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            null,
            array(
                'nullable' => false,
            ),
            'Created At'
        )
        ->addColumn(
            'updated_at',
            Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            null,
            array(
                'nullable' => true,
                'default' => null,
            ),
            'Updated At'
        );
    $installer->getConnection()->createTable($table);
    // Compatibility with version 1.5
    if (Mage::getVersion() < '1.6.0.0') {
        $installer->getConnection()->modifyColumn($tableName, 'id', 'int(11) NOT NULL auto_increment');
    }
}

// create table lengow_action
$tableName = $installer->getTable('lengow_action');
if ((boolean)$installer->getConnection()->showTableStatus($tableName) != true) {
    $table = $installer->getConnection()
        ->newTable($installer->getTable('lengow_action'))
        ->addColumn(
            'id',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            array(
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary' => true,
            ),
            'Id'
        )
        ->addColumn(
            'order_id',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            array(
                'nullable' => false,
                'unsigned' => true,
            ),
            'Order Id'
        )
        ->addColumn(
            'action_id',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            array(
                'nullable' => false,
                'unsigned' => true,
            ),
            'Action Id'
        )
        ->addColumn(
            'order_line_sku',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            100,
            array(
                'nullable' => true,
                'length' => 100,
                'default' => null,
            ),
            'Order Line Sku'
        )
        ->addColumn(
            'action_type',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            32,
            array(
                'nullable' => false,
                'length' => 32,
            ),
            'Action Type'
        )
        ->addColumn(
            'retry',
            Varien_Db_Ddl_Table::TYPE_SMALLINT,
            null,
            array(
                'nullable' => false,
                'unsigned' => true,
                'default' => 0,
            ),
            'Retry'
        )
        ->addColumn(
            'parameters',
            $typeText,
            null,
            array(
                'nullable' => false,
            ),
            'Parameters'
        )
        ->addColumn(
            'state',
            Varien_Db_Ddl_Table::TYPE_SMALLINT,
            null,
            array(
                'nullable' => false,
                'unsigned' => true,
            ),
            'State'
        )
        ->addColumn(
            'created_at',
            Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            null,
            array(
                'nullable' => false,
            ),
            'Created At'
        )
        ->addColumn(
            'updated_at',
            Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            null,
            array(
                'nullable' => true,
                'default' => null,
            ),
            'Updated At'
        );
    $installer->getConnection()->createTable($table);
    // Compatibility with version 1.5
    if (Mage::getVersion() < '1.6.0.0') {
        $installer->getConnection()->modifyColumn($tableName, 'id', 'int(11) NOT NULL auto_increment');
    }
}

// create table lengow_log
$tableName = $installer->getTable('lengow_log');
if ((boolean)$installer->getConnection()->showTableStatus($tableName) != true) {
    $table = $installer->getConnection()
        ->newTable($installer->getTable('lengow_log'))
        ->addColumn(
            'id',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            array(
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary' => true,
            ),
            'Id'
        )
        ->addColumn(
            'date',
            Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            null,
            array(
                'nullable' => false,
            ),
            'Date'
        )
        ->addColumn(
            'message',
            $typeText,
            null,
            array(
                'nullable' => false,
            ),
            'Message'
        );
    $installer->getConnection()->createTable($table);
    // Compatibility with version 1.5
    if (Mage::getVersion() < '1.6.0.0') {
        $installer->getConnection()->modifyColumn($tableName, 'id', 'int(11) NOT NULL auto_increment');
    }
}

// *********************************************************
//            Create Lengow technical error status
// *********************************************************

//check if order state and status 'Lengow technical error' exists
$collections = Mage::getModel('sales/order_status')->getCollection()->toOptionArray();
$lengowTechnicalExists = false;
foreach ($collections as $value) {
    if ($value['value'] == 'lengow_technical_error') {
        $lengowTechnicalExists = true;
    }
}
// if not exists create new order state and status 'Lengow technical error'
$statusTable = $installer->getTable('sales/order_status');
$statusStateTable = $installer->getTable('sales/order_status_state');
if (!$lengowTechnicalExists) {
    // Insert statuses
    $installer->getConnection()->insertArray(
        $statusTable,
        array('status', 'label'),
        array(
            array(
                'status' => 'lengow_technical_error',
                'label' => 'Lengow Technical Error',
            ),
        )
    );
    // Insert states and mapping of statuses to states
    $installer->getConnection()->insertArray(
        $statusStateTable,
        array('status', 'state', 'is_default'),
        array(
            array(
                'status' => 'lengow_technical_error',
                'state' => 'lengow_technical_error',
                'is_default' => 1,
            ),
        )
    );
}

$installer->endSetup();

// *********************************************************
//                    Setting Migration
// *********************************************************

// All settings update
$newSettings = array(
    array(
        'old_path' => 'lensync/orders/active_store',
        'new_path' => 'lengow_global_options/store_credential/global_store_enable',
        'store' => true,
    ),
    array(
        'old_path' => 'lentracker/tag/identifiant',
        'new_path' => 'lengow_global_options/advanced/global_tracking_id',
        'store' => false,
    ),
    array(
        'old_path' => 'lenexport/performances/valid_ip',
        'new_path' => 'lengow_global_options/advanced/global_authorized_ip',
        'store' => false,
    ),
    array(
        'old_path' => 'lenexport/global/export_only_selected',
        'new_path' => 'lengow_export_options/simple/export_selection_enable',
        'store' => true,
    ),
    array(
        'old_path' => 'lenexport/global/export_soldout',
        'new_path' => 'lengow_export_options/simple/export_out_stock',
        'store' => true,
    ),
    array(
        'old_path' => 'lenexport/global/producttype',
        'new_path' => 'lengow_export_options/simple/export_product_type',
        'store' => true,
    ),
    array(
        'old_path' => 'lenexport/global/productstatus',
        'new_path' => 'lengow_export_options/simple/export_product_status',
        'store' => true,
    ),
    array(
        'old_path' => 'lenexport/data/parentsimages',
        'new_path' => 'lengow_export_options/advanced/export_parent_image',
        'store' => true,
    ),
    array(
        'old_path' => 'lenexport/data/shipping_price_based_on',
        'new_path' => 'lengow_export_options/advanced/export_default_shipping_country',
        'store' => true,
    ),
    array(
        'old_path' => 'lenexport/data/default_shipping_method',
        'new_path' => 'lengow_export_options/advanced/export_default_shipping_method',
        'store' => true,
    ),
    array(
        'old_path' => 'lenexport/data/default_shipping_price',
        'new_path' => 'lengow_export_options/advanced/export_default_shipping_price',
        'store' => true,
    ),
    array(
        'old_path' => 'lenexport/attributelist/attributes',
        'new_path' => 'lengow_export_options/advanced/export_attribute',
        'store' => true,
    ),
    array(
        'old_path' => 'lenexport/performances/usesavefile',
        'new_path' => 'lengow_export_options/advanced/export_file_enable',
        'store' => false,
    ),
    array(
        'old_path' => 'lenexport/performances/active_cron',
        'new_path' => 'lengow_export_options/advanced/export_cron_enable',
        'store' => true,
    ),
    array(
        'old_path' => 'lensync/orders/period',
        'new_path' => 'lengow_import_options/simple/import_days',
        'store' => true,
    ),
    array(
        'old_path' => 'lensync/orders/customer_group',
        'new_path' => 'lengow_import_options/simple/import_customer_group',
        'store' => true,
    ),
    array(
        'old_path' => 'lensync/orders/default_shipping',
        'new_path' => 'lengow_import_options/simple/import_default_shipping_method',
        'store' => true,
    ),
    array(
        'old_path' => 'lensync/performances/debug',
        'new_path' => 'lengow_import_options/advanced/import_preprod_mode_enable',
        'store' => false,
    ),
    array(
        'old_path' => 'lensync/performances/active_cron',
        'new_path' => 'lengow_import_options/advanced/import_cron_enable',
        'store' => false,
    ),
);
// All the settings to delete
$deleteSettings = array(
    'lentracker/general/account_id',
    'lentracker/general/access_token',
    'lentracker/general/secret',
    'lentracker/general/version2',
    'lentracker/general/version3',
    'lentracker/general/login',
    'lentracker/general/group',
    'lentracker/general/api_key',
    'lentracker/tag/type',
    'lentracker/hidden/last_synchro',
    'lentracker/hidden/last_synchro_v3',
    'lenexport/global/active_store',
    'lenexport/global/autoexport_newproduct',
    'lenexport/data/format',
    'lenexport/data/count_images',
    'lenexport/data/without_product_ordering',
    'lenexport/data/formatdata',
    'lenexport/data/html_attributes',
    'lenexport/data/default_shipping_delay',
    'lenexport/data/levelcategory',
    'lenexport/performances/optimizeexport',
    'lensync/orders/version3',
    'lensync/orders/marketplace',
    'lensync/orders/date_import',
    'lensync/orders/processing_fee',
    'lensync/orders/title',
    'lensync/orders/split_name',
    'lensync/orders/fake_email',
    'lensync/hidden/last_synchro',
);
// Get Store collection
$storeCollection = Mage::getResourceModel('core/store_collection')->addFieldToFilter('is_active', 1);
$authorisedIp = Mage::getStoreConfig('lenexport/performances/valid_ip');
$trackerType = Mage::getStoreConfig('lentracker/tag/type');
// Update settings
foreach ($newSettings as $setting) {
    $globalValue = Mage::getStoreConfig($setting['old_path']);
    if (!is_null($globalValue)) {
        Mage::getModel('core/config')->saveConfig($setting['new_path'], $globalValue);
        Mage::getModel('core/config')->deleteConfig($setting['old_path']);
    }
    if ($setting['store']) {
        foreach ($storeCollection as $store) {
            // Get value by collection -> getStoreConfig() by store don't work (already null)
            $storeValues = Mage::getModel('core/config_data')->getCollection()
                ->addFieldToFilter('path', $setting['old_path'])
                ->addFieldToFilter('scope_id', $store->getId())
                ->getData();
            if (count($storeValues) > 0 && $storeValues[0]['value'] != $globalValue) {
                Mage::getModel('core/config')->saveConfig(
                    $setting['new_path'],
                    $storeValues[0]['value'],
                    'stores',
                    $store->getId()
                );
                Mage::getModel('core/config')->deleteConfig(
                    $setting['old_path'],
                    'stores',
                    $store->getId()
                );
            }
        }
    }
}
// Delete settings
foreach ($deleteSettings as $settingPath) {
    foreach ($storeCollection as $store) {
        Mage::getModel('core/config')->deleteConfig($settingPath, 'store', $store->getId());
    }
    Mage::getModel('core/config')->deleteConfig($settingPath);
}

/** @var Lengow_Connector_Helper_Config $configHelper */
$configHelper = Mage::helper('lengow_connector/config');

// *********************************************************
//      Active ip authorization if authorized ips exist
// *********************************************************

if (!is_null($authorisedIp) && strlen($authorisedIp) > 0) {
    $configHelper->set('ip_enable', 1);
}

// *********************************************************
//      Active Lengow tracker if the old tracker was used
// *********************************************************

if (!is_null($trackerType) && in_array($trackerType, array('simpletag', 'tagcapsule'))) {
    $configHelper->set('tracking_enable', 1);
}

// *********************************************************
//          Order Migration (only for processing)
// *********************************************************

Mage::getModel('lengow/import_order')->migrateOldOrder();
$seeMigrateBlock =  $configHelper->get('see_migrate_block');
if (is_null($seeMigrateBlock)) {
    $configHelper->set('see_migrate_block', 1);
}

$version = '3.0.0';
$installedVersion = $configHelper->get('installed_version');
if (version_compare($installedVersion, $version, '<')) {
    $configHelper->set('installed_version', $version);
}

// *********************************************************
//          Clean Magento cache
// *********************************************************

Mage::app()->getCacheInstance()->cleanType('config');
Mage::app()->getCacheInstance()->cleanType('layout');
Mage::app()->getCacheInstance()->cleanType('block_html');
