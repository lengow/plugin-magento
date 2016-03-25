<?php
/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Model_System_Config_Source_Getattributes extends Mage_Core_Model_Config_Data
{
    /**
     * Get option array for settings
     *
     * @return array
     */
    public function toOptionArray()
    {
        $attribute = Mage::getResourceModel('eav/entity_attribute_collection')
                           ->setEntityTypeFilter(Mage::getModel('catalog/product')
                           ->getResource()
                           ->getTypeId());
        $attributeArray = array();
        $attributeArray[] = array('value' => 'none', 'label' => '');
        foreach ($attribute as $option) {
            $attributeArray[] = array(
                'value' => $option->getAttributeCode(),
                'label' => $option->getAttributeCode()
            );
        }
        $this->setDefaultAttributes($attributeArray);
        return $attributeArray;
    }

    /**
     * Set default value with all attributes
     *
     * @param array $attributeArray
     */
    public function setDefaultAttributes($attributeArray = array())
    {
        if (strlen($code = Mage::getSingleton('adminhtml/config_data')->getStore())) {
            $store_id = Mage::getModel('core/store')->load($code)->getId();
        } else {
            $code = 'default';
            $store_id = 0;
        }
        if (is_null(Mage::getStoreConfig('lengow_export_options/advanced/export_attribute', $store_id))) {
            $attribute_list = '';
            foreach ($attributeArray as $attribute) {
                if ($attribute['value'] != 'none') {
                    $attribute_list.= $attribute['value'].',';
                }
            }
            Mage::getModel('core/config')->saveConfig(
                'lengow_export_options/advanced/export_attribute',
                $attribute_list,
                $code,
                $store_id
            );
        }
    }
}
