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
 * @subpackage  Model
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Model system config source attribute
 */
class Lengow_Connector_Model_System_Config_Source_Attribute extends Mage_Core_Model_Config_Data
{
    /**
     * @var array attributes excludes
     */
    protected $_excludes = array(
        'media_gallery',
        'tier_price',
        'short_description',
        'description',
        'quantity',
        'price',
        'lengow_product',
        'status',
    );

    /**
     * Get option array for settings
     *
     * @return array
     */
    public function toOptionArray()
    {
        $attribute = Mage::getResourceModel('eav/entity_attribute_collection')->setEntityTypeFilter(
            Mage::getModel('catalog/product')->getResource()->getTypeId()
        );
        $attributeArray = array();
        $attributeArray[] = array('value' => 'none', 'label' => '');
        foreach ($attribute as $option) {
            if (!in_array($option->getAttributeCode(), $this->_excludes)) {
                $attributeArray[] = array(
                    'value' => $option->getAttributeCode(),
                    'label' => $option->getAttributeCode(),
                );
            }
        }
        $this->setDefaultAttributes($attributeArray);
        return $attributeArray;
    }

    /**
     * Set default value with all attributes
     *
     * @param array $attributeArray list of Magento attribute
     */
    public function setDefaultAttributes($attributeArray = array())
    {
        if (strlen($code = Mage::getSingleton('adminhtml/config_data')->getStore())) {
            $storeId = Mage::getModel('core/store')->load($code)->getId();
        } else {
            $code = 'default';
            $storeId = 0;
        }
        if (Mage::getStoreConfig('lengow_export_options/advanced/export_attribute', $storeId) === null) {
            $attributeList = '';
            foreach ($attributeArray as $attribute) {
                if ($attribute['value'] !== 'none') {
                    $attributeList .= $attribute['value'] . ',';
                }
            }
            $attributeList = rtrim($attributeList, ',');
            Mage::getModel('core/config')->saveConfig(
                'lengow_export_options/advanced/export_attribute',
                $attributeList,
                $code,
                $storeId
            );
        }
    }
}
