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
$list_attribute = array();
$list_attribute[] = array(
    'name'    => 'from_lengow',
    'label'   => 'From Lengow',
    'type'    => 'int',
    'input'   => 'select',
    'source'  => 'eav/entity_attribute_source_boolean',
    'default' => 0,
    'grid'    => true,
);
$list_attribute[] = array(
    'name'    => 'order_id_lengow',
    'label'   => 'Lengow order ID',
    'type'    => 'text',
    'input'   => 'text',
    'source'  => '',
    'default' => '',
    'grid'    => true,
);
$list_attribute[] = array(
    'name'    => 'feed_id_lengow',
    'label'   => 'Feed ID',
    'type'    => 'float',
    'input'   => 'text',
    'source'  => '',
    'default' => 0,
    'grid'    => false,
);
$list_attribute[] = array(
    'name'    => 'marketplace_lengow',
    'label'   => 'marketplace',
    'type'    => 'text',
    'input'   => 'text',
    'source'  => '',
    'default' => '',
    'grid'    => true,
);
$list_attribute[] = array(
    'name'    => 'delivery_address_id_lengow',
    'label'   => 'Delivery address id lengow',
    'type'    => 'int',
    'input'   => 'text',
    'source'  => '',
    'default' => 0,
    'grid'    => false,
);
$list_attribute[] = array(
    'name'    => 'is_reimported_lengow',
    'label'   => 'Is Reimported Lengow',
    'type'    => 'int',
    'input'   => 'select',
    'source'  => 'eav/entity_attribute_source_boolean',
    'default' => 0,
    'grid'    => false,
);

foreach ($list_attribute as $attr) {
    $order_attribute = $installer->getAttribute('order', $attr['name']);
    if (!$order_attribute) {
        $installer->addAttribute(
            'order',
            $attr['name'],
            array(
                'name'       => $attr['name'],
                'label'      => $attr['label'],
                'type'       => $attr['type'],
                'visible'    => true,
                'required'   => false,
                'unique'     => false,
                'filterable' => 1,
                'sort_order' => 700,
                'default'    => $attr['default'],
                'input'      => $attr['input'],
                'source'     => $attr['source'],
                'grid'       => $attr['grid']
            )
        );
    }
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
        ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable'  => true,
            'unsigned'  => true,
            'default'   => null
        ), 'Order Id')
        ->addColumn('order_sku', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'nullable'  => true,
            'default'   => null,
            'length'    => 40
        ), 'Order sku')
        ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable'  => false,
            'unsigned'  => true
        ), 'Store Id')
        ->addColumn('feed_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable'  => true,
            'unsigned'  => true,
            'default'   => null
        ), 'Feed Id')
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
        ->addColumn('marketplace_name', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array(
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
        ->addColumn('customer_email', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
            'nullable'  => true,
            'length'    => 255,
            'default'   => null
        ), 'Customer Email')
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
        ->addColumn('is_in_error', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array(
            'nullable'  => false,
            'default'   => 0
        ), 'Is In Error')
        ->addColumn('message', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'nullable'  => true,
            'default'   => null
        ), 'Message')
        ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
            'nullable'  => false,
        ), 'Created At')
        ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
            'nullable'  => true,
            'default'   => null
        ), 'Updated At')
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
        ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable'  => false,
            'unsigned'  => true
        ), 'Order Id')
        ->addColumn('order_line_id', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array(
            'nullable'  => false,
            'length'    => 100
        ), 'Order Line Id');
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
        ->addColumn('order_lengow_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable'  => false,
            'unsigned'  => true
        ), 'Order Lengow Id')
        ->addColumn('message', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'nullable'  => true,
            'default'   => null
        ), 'Message')
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
        ), 'Mail')
        ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
            'nullable'  => false,
        ), 'Created At')
        ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
            'nullable'  => true,
            'default'   => null
        ), 'Updated At');
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
        ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable'  => false,
            'unsigned'  => true
        ), 'Order Id')
        ->addColumn('action_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable'  => false,
            'unsigned'  => true
        ), 'Action Id')
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
            'nullable'  => true,
            'default'   => null
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

// *********************************************************
//                    Setting Migration
// *********************************************************

// All settings update
$new_settings = array(
    array(
        'old_path'  => 'lensync/orders/active_store',
        'new_path'  => 'lengow_global_options/store_credential/global_store_enable',
        'store'     => true
    ),
    array(
        'old_path'  => 'lentracker/general/account_id',
        'new_path'  => 'lengow_global_options/store_credential/global_account_id',
        'store'     => true
    ),
    array(
        'old_path'  => 'lentracker/general/access_token',
        'new_path'  => 'lengow_global_options/store_credential/global_access_token',
        'store'     => true
    ),
    array(
        'old_path'  => 'lentracker/general/secret',
        'new_path'  => 'lengow_global_options/store_credential/global_secret_token',
        'store'     => true
    ),
    array(
        'old_path'  => 'lentracker/tag/identifiant',
        'new_path'  => 'lengow_global_options/advanced/global_tracking_id',
        'store'     => false
    ),
    array(
        'old_path'  => 'lenexport/performances/valid_ip',
        'new_path'  => 'lengow_global_options/advanced/global_authorized_ip',
        'store'     => false
    ),
    array(
        'old_path'  => 'export_only_selected',
        'new_path'  => 'lengow_export_options/simple/export_selection_enable',
        'store'     => true
    ),
    array(
        'old_path'  => 'lenexport/global/export_soldout',
        'new_path'  => 'lengow_export_options/simple/export_out_stock',
        'store'     => true
    ),
    array(
        'old_path'  => 'lenexport/global/producttype',
        'new_path'  => 'lengow_export_options/simple/export_product_type',
        'store'     => true
    ),
    array(
        'old_path'  => 'lenexport/global/productstatus',
        'new_path'  => 'lengow_export_options/simple/export_product_status',
        'store'     => true
    ),
    array(
        'old_path'  => 'lenexport/data/parentsimages',
        'new_path'  => 'lengow_export_options/advanced/export_parent_image',
        'store'     => true
    ),
    array(
        'old_path'  => 'lenexport/data/shipping_price_based_on',
        'new_path'  => 'lengow_export_options/advanced/export_default_shipping_country',
        'store'     => true
    ),
    array(
        'old_path'  => 'lenexport/data/default_shipping_method',
        'new_path'  => 'lengow_export_options/advanced/export_default_shipping_method',
        'store'     => true
    ),
    array(
        'old_path'  => 'lenexport/data/default_shipping_price',
        'new_path'  => 'lengow_export_options/advanced/export_default_shipping_price',
        'store'     => true
    ),
    array(
        'old_path'  => 'lenexport/attributelist/attributes',
        'new_path'  => 'lengow_export_options/advanced/export_attribute',
        'store'     => true
    ),
    array(
        'old_path'  => 'lenexport/performances/usesavefile',
        'new_path'  => 'lengow_export_options/advanced/export_file_enable',
        'store'     => false
    ),
    array(
        'old_path'  => 'lenexport/performances/active_cron',
        'new_path'  => 'lengow_export_options/advanced/export_cron_enable',
        'store'     => false
    ),
    array(
        'old_path'  => 'lensync/orders/period',
        'new_path'  => 'lengow_import_options/simple/import_days',
        'store'     => true
    ),
    array(
        'old_path'  => 'lensync/orders/customer_group',
        'new_path'  => 'lengow_import_options/simple/import_customer_group',
        'store'     => true
    ),
    array(
        'old_path'  => 'lensync/orders/default_shipping',
        'new_path'  => 'lengow_import_options/simple/import_default_shipping_method',
        'store'     => true
    ),
    array(
        'old_path'  => 'lensync/performances/debug',
        'new_path'  => 'lengow_import_options/advanced/import_preprod_mode_enable',
        'store'     => false
    ),
    array(
        'old_path'  => 'lensync/performances/active_cron',
        'new_path'  => 'lengow_import_options/advanced/import_cron_enable',
        'store'     => false
    )
);

// All the settings to delete
$delete_settings = array(
    'lentracker/general/version3',
    'lentracker/general/login',
    'lentracker/general/group',
    'lentracker/general/api_key',
    'lentracker/tag/type',
    'lentracker/hidden/last_synchro',
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
$store_collection = Mage::getResourceModel('core/store_collection')->addFieldToFilter('is_active', 1);

// Update settings
foreach ($new_settings as $setting) {
    if ($setting['store']) {
        foreach ($store_collection as $store) {
            $store_value = Mage::getStoreConfig($setting['old_path'], $store);
            if (!is_null($store_value)) {
                Mage::getModel('core/config')->saveConfig(
                    $setting['new_path'],
                    $store_value,
                    $store->getCode(),
                    $store->getId()
                );
                Mage::getModel('core/config')->deleteConfig(
                    $setting['old_path'],
                    $store->getCode(),
                    $store->getId()
                );
            }
        }
    }
    $global_value = Mage::getStoreConfig($setting['old_path']);
    if (!is_null($global_value)) {
        Mage::getModel('core/config')->saveConfig($setting['new_path'], $global_value);
        Mage::getModel('core/config')->deleteConfig($setting['old_path']);
    }
}

// Delete settings
foreach ($delete_settings as $setting_path) {
    foreach ($store_collection as $store) {
        if (!is_null(Mage::getStoreConfig($setting_path, $store))) {
            Mage::getModel('core/config')->deleteConfig($setting_path, $store->getCode(), $store->getId());
        }
    }
    if (!is_null(Mage::getStoreConfig($setting_path))) {
        Mage::getModel('core/config')->deleteConfig($setting_path);
    }
}
