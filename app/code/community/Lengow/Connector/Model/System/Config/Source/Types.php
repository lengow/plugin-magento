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
 * Model system config source types
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
        $helper = Mage::helper('adminhtml');
        return array(
            array('value' => 'configurable', 'label' => $helper->__('Configurable')),
            array('value' => 'simple', 'label' => $helper->__('Simple')),
            array('value' => 'downloadable', 'label' => $helper->__('Downloadable')),
            array('value' => 'grouped', 'label' => $helper->__('Grouped')),
            array('value' => 'virtual', 'label' => $helper->__('Virtual')),
        );
    }
}
