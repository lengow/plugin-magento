<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Model_System_Config_Source_Types extends Mage_Core_Model_Config_Data
{
    /**
     * Get option array for settings
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'configurable', 'label' => Mage::helper('adminhtml')->__('Configurable')),
            array('value' => 'simple', 'label' => Mage::helper('adminhtml')->__('Simple')),
            array('value' => 'downloadable', 'label' => Mage::helper('adminhtml')->__('Downloadable')),
            array('value' => 'grouped', 'label' => Mage::helper('adminhtml')->__('Grouped')),
            array('value' => 'virtual', 'label' => Mage::helper('adminhtml')->__('Virtual'))
        );
    }
}
