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
 * Helper sync
 */
class Lengow_Connector_Helper_Sync extends Mage_Core_Helper_Abstract
{
    /**
     * @var integer cache time for statistic, account status and cms options
     */
    protected $_cacheTime = 18000;

    /**
     * @var array valid sync actions
     */
    protected $_syncActions = array(
        'order',
        'action',
        'catalog',
        'option'
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
     * Is sync action
     *
     * @param string $action sync action
     *
     * @return boolean
     */
    public function isSyncAction($action)
    {
        return in_array($action, $this->_syncActions);
    }

    /**
     * Get Sync Data (Inscription / Update)
     *
     * @return array
     */
    public function getSyncData()
    {
        $helper = Mage::helper('lengow_connector');
        $data = array(
            'domain_name' => $_SERVER["SERVER_NAME"],
            'token' => $this->_configHelper->getToken(),
            'type' => 'magento',
            'version' => Mage::getVersion(),
            'plugin_version' => (string)Mage::getConfig()->getNode()->modules->Lengow_Connector->version,
            'email' => Mage::getStoreConfig('trans_email/ident_general/email'),
            'cron_url' => $helper->getCronUrl(),
            'return_url' => 'http://' . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"],
            'shops' => array()
        );
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $storeId = (int)$store->getId();
                    $export = Mage::getModel('lengow/export', array("store_id" => $storeId));
                    $data['shops'][$storeId] = array(
                        'token' =>  $this->_configHelper->getToken($storeId),
                        'shop_name' =>  $store->getName(),
                        'domain_url' => $store->getBaseUrl(),
                        'feed_url' =>  $helper->getExportUrl($storeId),
                        'total_product_number' => $export->getTotalProduct(),
                        'exported_product_number' => $export->getTotalExportedProduct(),
                        'enabled' => $this->_configHelper->storeIsActive($storeId)
                    );
                }
            }
        }
        return $data;
    }

    /**
     * Set store configuration key from Lengow
     *
     * @param array $params Lengow API credentials
     */
    public function sync($params)
    {
        $this->_configHelper->setAccessIds(
            array(
                'account_id' => $params['account_id'],
                'access_token' => $params['access_token'],
                'secret_token' => $params['secret_token']
            ),
            false
        );
        foreach ($params['shops'] as $storeToken => $storeCatalogIds) {
            $store = $this->_configHelper->getStoreByToken($storeToken);
            if ($store) {
                $this->_configHelper->setCatalogIds($storeCatalogIds['catalog_ids'], (int)$store->getId(), false);
                $this->_configHelper->setActiveStore((int)$store->getId(), false);
            }
        }
        // Clean config cache to valid configuration
        Mage::app()->getCacheInstance()->cleanType('config');
    }

    /**
     * Sync Lengow catalogs for order synchronisation
     */
    public function syncCatalog()
    {
        if ($this->_configHelper->isNewMerchant()) {
            return false;
        }
        $connector = Mage::getModel('lengow/connector');
        $result = $connector->queryApi('get', '/v3.1/cms');
        if (isset($result->cms)) {
            $cmsToken = $this->_configHelper->getToken();
            foreach ($result->cms as $cms) {
                if ($cms->token === $cmsToken) {
                    foreach ($cms->shops as $cmsShop) {
                        $store = $this->_configHelper->getStoreByToken($cmsShop->token);
                        if ($store) {
                            $this->_configHelper->setCatalogIds($cmsShop->catalog_ids, (int)$store->getId(), false);
                            $this->_configHelper->setActiveStore((int)$store->getId(), false);
                        }
                    }
                    break;
                }
            }
        }
        // Clean config cache to valid configuration
        Mage::app()->getCacheInstance()->cleanType('config');
    }

    /**
     * Get options for all store
     *
     * @return array
     */
    public function getOptionData()
    {
        $data = array(
            'token' => $this->_configHelper->getToken(),
            'version' => Mage::getVersion(),
            'plugin_version' => (string)Mage::getConfig()->getNode()->modules->Lengow_Connector->version,
            'options' => $this->_configHelper->getAllValues(),
            'shops' => array()
        );
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $storeId = (int)$store->getId();
                    $export = Mage::getModel('lengow/export', array("store_id" => $storeId));
                    $data['shops'][] = array(
                        'token' => $this->_configHelper->getToken($storeId),
                        'enabled' => $this->_configHelper->storeIsActive($storeId),
                        'total_product_number' => $export->getTotalProduct(),
                        'exported_product_number' => $export->getTotalExportedProduct(),
                        'options' => $this->_configHelper->getAllValues($storeId)
                    );
                }
            }
        }
        return $data;
    }

    /**
     * Set CMS options
     *
     * @param boolean $force force cache update
     *
     * @return boolean
     */
    public function setCmsOption($force = false)
    {
        if ($this->_configHelper->isNewMerchant() || (bool)$this->_configHelper->get('preprod_mode_enable')) {
            return false;
        }
        if (!$force) {
            $updatedAt = $this->_configHelper->get('last_option_cms_update');
            if (!is_null($updatedAt) && (time() - strtotime($updatedAt)) < $this->_cacheTime) {
                return false;
            }
        }
        $options = Mage::helper('core')->jsonEncode($this->getOptionData());
        $connector = Mage::getModel('lengow/connector');
        $connector->queryApi('put', '/v3.1/cms', array(), $options);
        $this->_configHelper->set('last_option_cms_update', date('Y-m-d H:i:s'));
        return true;
    }

    /**
     * Get Status Account
     *
     * @param boolean $force force cache update
     *
     * @return array|false
     */
    public function getStatusAccount($force = false)
    {
        if ($this->_configHelper->isNewMerchant()) {
            return false;
        }
        if (!$force) {
            $updatedAt = $this->_configHelper->get('last_status_update');
            if (!is_null($updatedAt) && (time() - strtotime($updatedAt)) < $this->_cacheTime) {
                return json_decode($this->_configHelper->get('account_status'), true);
            }
        }
        $result = Mage::getModel('lengow/connector')->queryApi('get', '/v3.0/plans');
        if (isset($result->isFreeTrial)) {
            $status = array();
            $status['type'] = $result->isFreeTrial ? 'free_trial' : '';
            $status['day'] = (int)$result->leftDaysBeforeExpired;
            $status['expired'] = (bool)$result->isExpired;
            if ($status['day'] < 0) {
                $status['day'] = 0;
            }
            if ($status) {
                $this->_configHelper->set('account_status', Mage::helper('core')->jsonEncode($status));
                $this->_configHelper->set('last_status_update', date('Y-m-d H:i:s'));
                return $status;
            }
        } else {
            if ($this->_configHelper->get('last_status_update')) {
                return json_decode($this->_configHelper->get('account_status'), true);
            }
        }
        return false;
    }

    /**
     * Get Statistic for all stores
     *
     * @param boolean $force force cache update
     *
     * @return array
     */
    public function getStatistic($force = false)
    {
        if (!$force) {
            $updatedAt = $this->_configHelper->get('last_statistic_update');
            if (!is_null($updatedAt) && (time() - strtotime($updatedAt)) < $this->_cacheTime) {
                return json_decode($this->_configHelper->get('order_statistic'), true);
            }
        }
        $allCurrencyCodes = $this->_configHelper->getAllAvailableCurrencyCodes();
        $connector = Mage::getModel('lengow/connector');
        $result = $connector->queryApi(
            'get',
            '/v3.0/stats',
            array(
                'date_from' => date('c', strtotime(date('Y-m-d') . ' -10 years')),
                'date_to' => date('c'),
                'metrics' => 'year',
            )
        );
        if (isset($result->level0)) {
            $stats = $result->level0[0];
            $return = array(
                'total_order' => $stats->revenue,
                'nb_order' => (int)$stats->transactions,
                'currency' => $result->currency->iso_a3,
                'available' => false
            );
        } else {
            if ($this->_configHelper->get('last_statistic_update')) {
                return json_decode($this->_configHelper->get('order_statistic'), true);
            } else {
                return array(
                    'total_order' => 0,
                    'nb_order' => 0,
                    'currency' => '',
                    'available' => false
                );
            }
        }
        if ($return['total_order'] > 0 || $return['nb_order'] > 0) {
            $return['available'] = true;
        }
        if ($return['currency'] && in_array($return['currency'], $allCurrencyCodes)) {
            $return['total_order'] = Mage::app()->getLocale()
                ->currency($return['currency'])
                ->toCurrency($return['total_order']);
        } else {
            $return['total_order'] = number_format($return['total_order'], 2, ',', ' ');
        }
        $this->_configHelper->set('order_statistic', Mage::helper('core')->jsonEncode($return));
        $this->_configHelper->set('last_statistic_update', date('Y-m-d H:i:s'));
        return $return;
    }
}
