<?php


$installer = $this;
$installer->startSetup();

$entity_id = $installer->getEntityTypeId('catalog_product');
$attribute = $installer->getAttribute($entity_id,'lengow_product');

if(!$attribute){
    $installer->addAttribute('catalog_product', 'lengow_product', array(
        'type'              => 'int',
        'backend'           => '',
        'frontend'          => '',
        'label'             => 'Publish on Lengow',
        'input'             => 'boolean',
        'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'visible'           => 1,
        'required'          => 0,
        'user_defined'      => 1,
        'default'           => 0,
        'searchable'        => 0,
        'filterable'        => 0,
        'comparable'        => 0,
        'visible_on_front'  => 1,
        'unique'            => 0,
        'used_in_product_listing' => 1
    ));
}

$installer->endSetup();