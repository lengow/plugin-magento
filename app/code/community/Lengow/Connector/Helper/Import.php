<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Helper_Import extends Mage_Core_Helper_Abstract
{
    /**
     * @var Lengow_Connector_Helper_Config
     */
    protected $_config = null;

    /**
     * marketplaces collection
     */
    public static $marketplaces = array();

    /**
     * Construct
     */
    public function __construct()
    {
        $this->_config = Mage::helper('lengow_connector/config');
    }

    /**
     * Get Marketplace singleton
     *
     * @param string    $name       markeplace name
     * @param integer   $store_id   store Id
     *
     * @return array Lengow marketplace
     */
    public static function getMarketplaceSingleton($name, $store_id = null)
    {
        if (!array_key_exists($name, self::$marketplaces)) {
            self::$marketplaces[$name] = Mage::getModel(
                'lengow/import_marketplace',
                array(
                    'name'     => $name,
                    'store_id' => $store_id
                )
            );
        }
        return self::$marketplaces[$name];
    }

    /**
     * Check if import is already in process
     *
     * @return boolean
     */
    public function importIsInProcess()
    {
        $timestamp = $this->_config->get('import_in_progress');
        if ($timestamp > 0) {
            // security check : if last import is more than 10 min old => authorize new import to be launched
            if (($timestamp + (60 * 1)) < time()) {
                $this->setImportEnd();
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Get Rest time to make re import order
     *
     * @return boolean
     */
    public function restTimeToImport()
    {
        $timestamp = $this->_config->get('import_in_progress');
        if ($timestamp > 0) {
            return $timestamp + (60 * 1) - time();
        }
        return false;
    }

    /**
     * Set import to "in process" state
     *
     * @return boolean
     */
    public function setImportInProcess()
    {
        return $this->_config->set('import_in_progress', time());
    }

    /**
     * Set import to finished
     *
     * @return boolean
     */
    public function setImportEnd()
    {
        return $this->_config->set('import_in_progress', -1);
    }

    /**
     * Record the date of the last import
     *
     * @param string $type (cron or manual)
     *
     * @return boolean
     */
    public function updateDateImport($type)
    {
        if ($type === 'cron') {
            $this->_config->set('last_import_cron', time());
        } else {
            $this->_config->set('last_import_manual', time());
        }
    }

    /**
     * Get last import (type and timestamp)
     *
     * @return mixed
     */
    public function getLastImport()
    {
        $timestamp_cron = $this->_config->get('last_import_cron');
        $timestamp_manual = $this->_config->get('last_import_manual');

        if ($timestamp_cron && $timestamp_manual) {
            if ((int)$timestamp_cron > (int) $timestamp_manual) {
                return array('type' => 'cron', 'timestamp' => (int)$timestamp_cron);
            } else {
                return array('type' => 'manual', 'timestamp' => (int)$timestamp_manual);
            }
        } elseif ($timestamp_cron && !$timestamp_manual) {
            return array('type' => 'cron', 'timestamp' => (int)$timestamp_cron);
        } elseif ($timestamp_manual && !$timestamp_cron) {
            return array('type' => 'manual', 'timestamp' => (int)$timestamp_manual);
        }

        return array('type' => 'none', 'timestamp' => 'none');
    }

    /**
     * v3
     * Check logs table and send mail for order not imported correctly
     *
     * @param  boolean $log_output See log or not
     *
     * @return void
     */
    public function sendMailAlert($log_output = false)
    {
        
    }
}
