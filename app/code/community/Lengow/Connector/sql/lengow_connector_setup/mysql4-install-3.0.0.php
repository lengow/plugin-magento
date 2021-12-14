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
    'name' => Lengow_Connector_Model_Import_Order::FIELD_LEGACY_FROM_LENGOW,
    'label' => 'From Lengow',
    'type' => 'int',
    'input' => 'select',
    'source' => 'eav/entity_attribute_source_boolean',
    'default' => 0,
    'grid' => true,
);
$listAttributes[] = array(
    'name' => Lengow_Connector_Model_Import_Order::FIELD_LEGACY_MARKETPLACE_SKU,
    'label' => 'Lengow order ID',
    'type' => 'text',
    'input' => 'text',
    'source' => '',
    'default' => '',
    'grid' => true,
);
$listAttributes[] = array(
    'name' => Lengow_Connector_Model_Import_Order::FIELD_LEGACY_FEED_ID,
    'label' => 'Feed ID',
    'type' => 'float',
    'input' => 'text',
    'source' => '',
    'default' => 0,
    'grid' => false,
);
$listAttributes[] = array(
    'name' => Lengow_Connector_Model_Import_Order::FIELD_LEGACY_MARKETPLACE_NAME,
    'label' => 'marketplace',
    'type' => 'text',
    'input' => 'text',
    'source' => '',
    'default' => '',
    'grid' => true,
);
$listAttributes[] = array(
    'name' => Lengow_Connector_Model_Import_Order::FIELD_LEGACY_DELIVERY_ADDRESS_ID,
    'label' => 'Delivery address id lengow',
    'type' => 'int',
    'input' => 'text',
    'source' => '',
    'default' => 0,
    'grid' => false,
);
$listAttributes[] = array(
    'name' => Lengow_Connector_Model_Import_Order::FIELD_LEGACY_IS_REIMPORTED,
    'label' => 'Is Reimported Lengow',
    'type' => 'int',
    'input' => 'select',
    'source' => 'eav/entity_attribute_source_boolean',
    'default' => 0,
    'grid' => false,
);
$listAttributes[] = array(
    'name' => Lengow_Connector_Model_Import_Order::FIELD_LEGACY_FOLLOW_BY_LENGOW,
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
// add new Attribute group
$groupName = 'Lengow';
$entityTypeId = $installer->getEntityTypeId('catalog_product');
// add group Lengow in all Attribute Set
$attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection')->load();
foreach ($attributeSetCollection as $id => $attributeSet) {
    // add group lengow in attribute set
    $installer->addAttributeGroup($entityTypeId, $attributeSet->getId(), $groupName, 100);
    $attributeGroupId = $installer->getAttributeGroupId($entityTypeId, $attributeSet->getId(), $groupName);
    // add new attribute (lengow_product) on Group (Lengow)
    foreach ($newAttributes as $attributeCode) {
        $attributeId = $installer->getAttributeId('catalog_product', $attributeCode);
        $entityTypeId = $attributeSet->getEntityTypeId();
        $installer->addAttributeToGroup($entityTypeId, $attributeSet->getId(), $attributeGroupId, $attributeId, null);
    }
}

// *********************************************************
//                  Create Lengow tables
// *********************************************************

// create table lengow_order
$tableName = $installer->getTable(Lengow_Connector_Model_Import_Order::TABLE_ORDER);
if (!(bool) $installer->getConnection()->showTableStatus($tableName)) {
    $table = $installer->getConnection()
        ->newTable($tableName)
        ->addColumn(
            Lengow_Connector_Model_Import_Order::FIELD_ID,
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
            Lengow_Connector_Model_Import_Order::FIELD_ORDER_ID,
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
            Lengow_Connector_Model_Import_Order::FIELD_ORDER_SKU,
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            40,
            array(
                'nullable' => true,
                'default' => null,
            ),
            'Order sku'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Order::FIELD_STORE_ID,
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            array(
                'nullable' => false,
                'unsigned' => true,
            ),
            'Store Id'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Order::FIELD_FEED_ID,
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
            Lengow_Connector_Model_Import_Order::FIELD_DELIVERY_ADDRESS_ID,
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
            Lengow_Connector_Model_Import_Order::FIELD_DELIVERY_COUNTRY_ISO,
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
            Lengow_Connector_Model_Import_Order::FIELD_MARKETPLACE_SKU,
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            100,
            array(
                'nullable' => false,
                'length' => 100,
            ),
            'Marketplace Sku'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Order::FIELD_MARKETPLACE_NAME,
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            100,
            array(
                'nullable' => false,
                'length' => 100,
            ),
            'Marketplace Name'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Order::FIELD_MARKETPLACE_LABEL,
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
            Lengow_Connector_Model_Import_Order::FIELD_ORDER_LENGOW_STATE,
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            100,
            array(
                'nullable' => false,
                'length' => 100,
            ),
            'Order Lengow State'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Order::FIELD_ORDER_PROCESS_STATE,
            Varien_Db_Ddl_Table::TYPE_SMALLINT,
            null,
            array(
                'nullable' => false,
                'unsigned' => true,
            ),
            'Order Process State'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Order::FIELD_ORDER_DATE,
            Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            null,
            array(
                'nullable' => false,
            ),
            'Order Date'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Order::FIELD_ORDER_ITEM,
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
            Lengow_Connector_Model_Import_Order::FIELD_ORDER_TYPES,
            Varien_Db_Ddl_Table::TYPE_TEXT,
		    null,
		    array(
			    'nullable' => true,
			    'default' => null,
		    ),
		    'Order Types'
	    )
        ->addColumn(
            Lengow_Connector_Model_Import_Order::FIELD_CURRENCY,
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
            Lengow_Connector_Model_Import_Order::FIELD_TOTAL_PAID,
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
            Lengow_Connector_Model_Import_Order::FIELD_CUSTOMER_VAT_NUMBER,
            Varien_Db_Ddl_Table::TYPE_TEXT,
            null,
            array(
                'nullable' => true,
                'default' => null,
            ),
            'Customer Vat Number'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Order::FIELD_COMMISSION,
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
            Lengow_Connector_Model_Import_Order::FIELD_CUSTOMER_NAME,
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
            Lengow_Connector_Model_Import_Order::FIELD_CUSTOMER_EMAIL,
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
            Lengow_Connector_Model_Import_Order::FIELD_CARRIER,
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
            Lengow_Connector_Model_Import_Order::FIELD_CARRIER_METHOD,
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
            Lengow_Connector_Model_Import_Order::FIELD_CARRIER_TRACKING,
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
            Lengow_Connector_Model_Import_Order::FIELD_CARRIER_RELAY_ID,
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
            Lengow_Connector_Model_Import_Order::FIELD_SENT_MARKETPLACE,
            Varien_Db_Ddl_Table::TYPE_BOOLEAN,
            null,
            array(
                'nullable' => false,
                'default' => 0,
            ),
            'Sent Marketplace'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Order::FIELD_IS_IN_ERROR,
            Varien_Db_Ddl_Table::TYPE_BOOLEAN,
            null,
            array(
                'nullable' => false,
                'default' => 0,
            ),
            'Is In Error'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Order::FIELD_MESSAGE,
            Varien_Db_Ddl_Table::TYPE_TEXT,
            null,
            array(
                'nullable' => true,
                'default' => null,
            ),
            'Message'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Order::FIELD_CREATED_AT,
            Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            null,
            array(
                'nullable' => false,
            ),
            'Created At'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Order::FIELD_UPDATED_AT,
            Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            null,
            array(
                'nullable' => true,
                'default' => null,
            ),
            'Updated At'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Order::FIELD_EXTRA,
            Varien_Db_Ddl_Table::TYPE_TEXT,
            null,
            array(
                'nullable' => true,
                'default' => null,
            ),
            'Extra'
        );
    $installer->getConnection()->createTable($table);
}

// create table lengow_order_line
$tableName = $installer->getTable(Lengow_Connector_Model_Import_Orderline::TABLE_ORDER_LINE);
if (!(bool) $installer->getConnection()->showTableStatus($tableName)) {
    $table = $installer->getConnection()
        ->newTable($tableName)
        ->addColumn(
            Lengow_Connector_Model_Import_Orderline::FIELD_ID,
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
            Lengow_Connector_Model_Import_Orderline::FIELD_ORDER_ID,
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            array(
                'nullable' => false,
                'unsigned' => true,
            ),
            'Order Id'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Orderline::FIELD_ORDER_LINE_ID,
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            100,
            array(
                'nullable' => false,
                'length' => 100,
            ),
            'Order Line Id'
        );
    $installer->getConnection()->createTable($table);
} else {
    if ($installer->getConnection()->tableColumnExists($tableName, 'id_order')) {
        $installer->getConnection()->changeColumn(
            $tableName,
            'id_order',
            Lengow_Connector_Model_Import_Orderline::FIELD_ORDER_ID,
            'int(11) UNSIGNED NOT NULL'
        );
    }
    if ($installer->getConnection()->tableColumnExists($tableName, 'id_order_line')) {
        $installer->getConnection()->changeColumn(
            $tableName,
            'id_order_line',
            Lengow_Connector_Model_Import_Orderline::FIELD_ORDER_LINE_ID,
            'VARCHAR(100) NOT NULL'
        );
    }
}

// create table lengow_order_error
$tableName = $installer->getTable(Lengow_Connector_Model_Import_Ordererror::TABLE_ORDER_ERROR);
if (!(bool) $installer->getConnection()->showTableStatus($tableName)) {
    $table = $installer->getConnection()
        ->newTable($tableName)
        ->addColumn(
            Lengow_Connector_Model_Import_Ordererror::FIELD_ID,
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
            Lengow_Connector_Model_Import_Ordererror::FIELD_ORDER_LENGOW_ID,
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            array(
                'nullable' => false,
                'unsigned' => true,
            ),
            'Order Lengow Id'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Ordererror::FIELD_MESSAGE,
            Varien_Db_Ddl_Table::TYPE_TEXT,
            null,
            array(
                'nullable' => true,
                'default' => null,
            ),
            'Message'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Ordererror::FIELD_TYPE,
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            array(
                'nullable' => false,
                'unsigned' => true,
            ),
            'Type'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Ordererror::FIELD_IS_FINISHED,
            Varien_Db_Ddl_Table::TYPE_BOOLEAN,
            null,
            array(
                'nullable' => false,
                'default' => 0,
            ),
            'Is Finished'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Ordererror::FIELD_MAIL,
            Varien_Db_Ddl_Table::TYPE_BOOLEAN,
            null,
            array(
                'nullable' => false,
                'default' => 0,
            ),
            'Mail'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Ordererror::FIELD_CREATED_AT,
            Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            null,
            array(
                'nullable' => false,
            ),
            'Created At'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Ordererror::FIELD_UPDATED_AT,
            Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            null,
            array(
                'nullable' => true,
                'default' => null,
            ),
            'Updated At'
        );
    $installer->getConnection()->createTable($table);
}

// create table lengow_action
$tableName = $installer->getTable(Lengow_Connector_Model_Import_Action::TABLE_ACTION);
if (!(bool) $installer->getConnection()->showTableStatus($tableName)) {
    $table = $installer->getConnection()
        ->newTable($tableName)
        ->addColumn(
            Lengow_Connector_Model_Import_Action::FIELD_ID,
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
            Lengow_Connector_Model_Import_Action::FIELD_ORDER_ID,
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            array(
                'nullable' => false,
                'unsigned' => true,
            ),
            'Order Id'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Action::FIELD_ACTION_ID,
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            array(
                'nullable' => false,
                'unsigned' => true,
            ),
            'Action Id'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Action::FIELD_ORDER_LINE_SKU,
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
            Lengow_Connector_Model_Import_Action::FIELD_ACTION_TYPE,
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            32,
            array(
                'nullable' => false,
                'length' => 32,
            ),
            'Action Type'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Action::FIELD_RETRY,
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
            Lengow_Connector_Model_Import_Action::FIELD_PARAMETERS,
            Varien_Db_Ddl_Table::TYPE_TEXT,
            null,
            array(
                'nullable' => false,
            ),
            'Parameters'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Action::FIELD_STATE,
            Varien_Db_Ddl_Table::TYPE_SMALLINT,
            null,
            array(
                'nullable' => false,
                'unsigned' => true,
            ),
            'State'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Action::FIELD_CREATED_AT,
            Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            null,
            array(
                'nullable' => false,
            ),
            'Created At'
        )
        ->addColumn(
            Lengow_Connector_Model_Import_Action::FIELD_UPDATED_AT,
            Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            null,
            array(
                'nullable' => true,
                'default' => null,
            ),
            'Updated At'
        );
    $installer->getConnection()->createTable($table);
}

// create table lengow_log
$tableName = $installer->getTable(Lengow_Connector_Model_Log::TABLE_LOG);
if (!(bool) $installer->getConnection()->showTableStatus($tableName)) {
    $table = $installer->getConnection()
        ->newTable($tableName)
        ->addColumn(
            Lengow_Connector_Model_Log::FIELD_ID,
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
            Lengow_Connector_Model_Log::FIELD_DATE,
            Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            null,
            array(
                'nullable' => false,
            ),
            'Date'
        )
        ->addColumn(
            Lengow_Connector_Model_Log::FIELD_MESSAGE,
            Varien_Db_Ddl_Table::TYPE_TEXT,
            null,
            array(
                'nullable' => false,
            ),
            'Message'
        );
    $installer->getConnection()->createTable($table);
}

// *********************************************************
//            Create Lengow technical error status
// *********************************************************

// check if order state and status 'Lengow technical error' exists
$collections = Mage::getModel('sales/order_status')->getCollection()->toOptionArray();
$lengowTechnicalExists = false;
foreach ($collections as $value) {
    if ($value['value'] === Lengow_Connector_Helper_Data::LENGOW_TECHNICAL_ERROR_STATE) {
        $lengowTechnicalExists = true;
    }
}
// if not exists create new order state and status 'Lengow technical error'
$statusTable = $installer->getTable('sales/order_status');
$statusStateTable = $installer->getTable('sales/order_status_state');
if (!$lengowTechnicalExists) {
    // insert statuses
    $installer->getConnection()->insertArray(
        $statusTable,
        array('status', 'label'),
        array(
            array(
                'status' => Lengow_Connector_Helper_Data::LENGOW_TECHNICAL_ERROR_STATE,
                'label' => 'Lengow Technical Error',
            ),
        )
    );
    // insert states and mapping of statuses to states
    $installer->getConnection()->insertArray(
        $statusStateTable,
        array('status', 'state', 'is_default'),
        array(
            array(
                'status' => Lengow_Connector_Helper_Data::LENGOW_TECHNICAL_ERROR_STATE,
                'state' => Lengow_Connector_Helper_Data::LENGOW_TECHNICAL_ERROR_STATE,
                'is_default' => 1,
            ),
        )
    );
}

$installer->endSetup();

// *********************************************************
//                    Setting Migration
// *********************************************************

// all settings update
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
        'new_path' => 'lengow_import_options/advanced/import_debug_mode_enable',
        'store' => false,
    ),
    array(
        'old_path' => 'lensync/performances/active_cron',
        'new_path' => 'lengow_import_options/advanced/import_cron_enable',
        'store' => false,
    ),
);
// all the settings to delete
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
    'lenexport/global/productstatus',
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
// get Store collection
$storeCollection = Mage::getResourceModel('core/store_collection')->addFieldToFilter('is_active', 1);
$authorisedIp = Mage::getStoreConfig('lenexport/performances/valid_ip');
$trackerType = Mage::getStoreConfig('lentracker/tag/type');
// update settings
foreach ($newSettings as $setting) {
    $globalValue = Mage::getStoreConfig($setting['old_path']);
    if ($globalValue !== null) {
        Mage::getModel('core/config')->saveConfig($setting['new_path'], $globalValue);
        Mage::getModel('core/config')->deleteConfig($setting['old_path']);
    }
    if ($setting['store']) {
        foreach ($storeCollection as $store) {
            // get value by collection -> getStoreConfig() by store don't work (already null)
            $storeValues = Mage::getModel('core/config_data')->getCollection()
                ->addFieldToFilter('path', $setting['old_path'])
                ->addFieldToFilter('scope_id', $store->getId())
                ->getData();
            if (!empty($storeValues) && $storeValues[0]['value'] != $globalValue) {
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
// delete settings
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

if ($authorisedIp !== null && strlen($authorisedIp) > 0) {
    $configHelper->set(Lengow_Connector_Helper_Config::AUTHORIZED_IP_ENABLED, 1);
}

// *********************************************************
//      Active Lengow tracker if the old tracker was used
// *********************************************************

if ($trackerType !== null && in_array($trackerType, array('simpletag', 'tagcapsule'))) {
    $configHelper->set(Lengow_Connector_Helper_Config::TRACKING_ENABLED, 1);
}

// *********************************************************
//          Order Migration (only for processing)
// *********************************************************

Mage::getModel('lengow/import_order')->migrateOldOrder();
$seeMigrateBlock =  $configHelper->get(Lengow_Connector_Helper_Config::MIGRATE_BLOCK_ENABLED);
if ($seeMigrateBlock === null) {
    $configHelper->set(Lengow_Connector_Helper_Config::MIGRATE_BLOCK_ENABLED, 1);
}

$version = '3.0.0';
$installedVersion = $configHelper->get(Lengow_Connector_Helper_Config::PLUGIN_VERSION);
if (version_compare($installedVersion, $version, '<')) {
    $configHelper->set(Lengow_Connector_Helper_Config::PLUGIN_VERSION, $version);
}

// *********************************************************
//          Clean Magento cache
// *********************************************************

Mage::app()->getCacheInstance()->cleanType('config');
Mage::app()->getCacheInstance()->cleanType('layout');
Mage::app()->getCacheInstance()->cleanType('block_html');
