<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Tracker
 * @author      Team Connector <team-connector@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Model_System_Config_Source_Tracking extends Mage_Core_Model_Config_Data
{
    /**
     * Get option array for settings
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'sku', 'label' => Mage::helper('adminhtml')->__('Sku')),
            array('value' => 'entity_id', 'label' => Mage::helper('adminhtml')->__('ID product')),
        );
    }
}
