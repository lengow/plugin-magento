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
 * @subpackage  Helper
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Helper security
 */
class Lengow_Connector_Helper_Security extends Mage_Core_Helper_Abstract
{
    /**
     * @var array lengow authorized ips
     */
    protected $_ipsLengow = array(
        '127.0.0.1',
        '10.0.4.150',
        '46.19.183.204',
        '46.19.183.217',
        '46.19.183.218',
        '46.19.183.219',
        '46.19.183.222',
        '52.50.58.130',
        '89.107.175.172',
        '89.107.175.185',
        '89.107.175.186',
        '89.107.175.187',
        '90.63.241.226',
        '109.190.189.175',
        '146.185.41.180',
        '146.185.41.177',
        '185.61.176.129',
        '185.61.176.130',
        '185.61.176.131',
        '185.61.176.132',
        '185.61.176.133',
        '185.61.176.134',
        '185.61.176.137',
        '185.61.176.138',
        '185.61.176.139',
        '185.61.176.140',
        '185.61.176.141',
        '185.61.176.142',
    );

    /**
     * @var Lengow_Connector_Helper_Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->_configHelper = Mage::helper('lengow_connector/config');
    }

    /**
     * Check Webservice access (export and cron)
     *
     * @param string $token store token
     * @param integer $storeId Magento store id
     *
     * @return boolean
     */
    public function checkWebserviceAccess($token, $storeId = 0)
    {
        if (!(bool) $this->_configHelper->get(Lengow_Connector_Helper_Config::AUTHORIZED_IP_ENABLED)
            && $this->checkToken($token, $storeId)
        ) {
            return true;
        }
        if ($this->checkIp()) {
            return true;
        }
        return false;
    }

    /**
     * Check if token is correct
     *
     * @param string $token store token
     * @param integer $storeId Magento store id
     *
     * @return boolean
     */
    public function checkToken($token, $storeId = 0)
    {
        return $token === $this->_configHelper->getToken($storeId);
    }

    /**
     * Check if current IP is authorized
     *
     * @return boolean
     */
    public function checkIp()
    {
        $authorizedIps = array_merge($this->_configHelper->getAuthorizedIps(), $this->_ipsLengow);
        $authorizedIps[] = $this->getServerIp();
        return in_array($this->getRemoteIp(), $authorizedIps, true);
    }

    /**
     * Get server IP
     *
     * @return string
     */
    public function getServerIp()
    {
        return $_SERVER['SERVER_ADDR'];
    }

    /**
     * Get remote IP
     *
     * @return string
     */
    public function getRemoteIp()
    {
        return Mage::helper('core/http')->getRemoteAddr();
    }

    /**
     * Get plugin version
     *
     * @return string
     */
    public function getPluginVersion()
    {
        return (string) Mage::getConfig()->getNode()->modules->Lengow_Connector->version;
    }

    /**
     * Get Magento version
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return Mage::getVersion();
    }

    /**
     * Check if valid magento version
     *
     * @return boolean
     */
    public function checkValidMagentoVersion()
    {
        return version_compare($this->getMagentoVersion(), '1.7.0.0', '>');
    }
}
