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
     * @var string plugin code
     */
    const PLUGIN_CODE = 'lengow_connector_setup';

    /**
     * @var array lengow authorized ips
     */
    public static $ipsLengow = array(
        '127.0.0.1',
        '46.19.183.204',
        '46.19.183.218',
        '46.19.183.222',
        '89.107.175.172',
        '89.107.175.186',
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
        '95.131.137.18',
        '95.131.137.19',
        '95.131.137.21',
        '95.131.137.26',
        '95.131.137.27',
        '88.164.17.227',
        '88.164.17.216',
        '109.190.78.5',
        '80.11.36.123',
        '95.131.141.169',
        '95.131.141.170',
        '95.131.141.171',
        '82.127.207.67',
        '80.14.226.127',
        '80.236.15.223',
    );

    /**
     * Check if current IP is authorized
     *
     * @return boolean
     */
    public function checkIP()
    {
        $ips = Mage::helper('lengow_connector/config')->get('authorized_ip');
        $ips = trim(str_replace(array("\r\n", ',', '-', '|', ' '), ';', $ips), ';');
        $ips = explode(';', $ips);
        $authorizedIps = array_merge($ips, self::$ipsLengow);
        $authorizedIps[] = $this->getRealIP();
        $hostnameIp = Mage::helper('core/http')->getRemoteAddr();
        if (in_array($hostnameIp, $authorizedIps)) {
            return true;
        }
        return false;
    }

    /**
     * Check if lengow_connector_setup is present in core_ressource table
     *
     * @return boolean
     */
    public function lengowIsInstalled()
    {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $table = $resource->getTableName('core/resource');
        $query = 'SELECT version FROM '.$table.' WHERE code = \''.self::PLUGIN_CODE.'\'';
        $version = $readConnection->fetchOne($query);
        if ($version === $this->getPluginVersion()) {
            return true;
        }
        return false;
    }

    /**
     * Get plugin version
     *
     * @return string
     */
    public function getPluginVersion()
    {
        return (string)Mage::getConfig()->getNode()->modules->Lengow_Connector->version;
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
     * Get serveur IP
     *
     * @return string
     */
    public function getRealIP()
    {
        return $_SERVER['SERVER_ADDR'];
    }

    /**
     * Check if valid magento version
     *
     * @return boolean
     */
    public function checkValidMagentoVersion()
    {
        return ($this->getMagentoVersion() < '1.5.0.0') ? false : true;
    }
}
