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
 * @subpackage  Block
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Block adminhtml system config check point
 */
class Lengow_Connector_Block_Adminhtml_System_Config_Check_Point extends Mage_Adminhtml_Block_Template
{
    /**
     * @var Lengow_Connector_Helper_Security Lengow security helper
     */
    protected $_helper;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->setTemplate('lengow/check/point.phtml');
        $this->_helper = Mage::helper('lengow_connector/security');
        parent::_construct();
    }

    /**
     * Get plugin version
     *
     * @return string
     */
    public function getPluginVersion()
    {
        return $this->_helper->getPluginVersion();
    }

    /**
     * Get Magento version
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return $this->_helper->getMagentoVersion();
    }

    /**
     * Check if Magento version is valid
     *
     * @return boolean
     */
    public function checkValidMagentoVersion()
    {
        return $this->_helper->checkValidMagentoVersion();
    }

    /**
     * Get real IP
     *
     * @return string
     */
    public function getRealIP()
    {
        return $this->_helper->getRealIP();
    }
}
