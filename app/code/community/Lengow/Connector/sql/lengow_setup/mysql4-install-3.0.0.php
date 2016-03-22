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

$installer->removeAttribute('catalog_product','lengow_product');

$attribute = $installer->getAttribute('catalog_product','lengow_product');

if (!$attribute) {
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
        ->addColumn('date', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
            'nullable' => false,
        ), 'Date')
        ->addColumn('message', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'nullable' => false,
        ), 'Message');
    $installer->getConnection()->createTable($table);
}

$installer->endSetup();
