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
     * @var string cms type
     */
    const CMS_TYPE = 'magento';

    /**
     * @var string sync catalog action
     */
    const SYNC_CATALOG = 'catalog';

    /**
     * @var string sync cms option action
     */
    const SYNC_CMS_OPTION = 'cms_option';

    /**
     * @var string sync status account action
     */
    const SYNC_STATUS_ACCOUNT = 'status_account';

    /**
     * @var string sync marketplace action
     */
    const SYNC_MARKETPLACE = 'marketplace';

    /**
     * @var string sync order action
     */
    const SYNC_ORDER = 'order';

    /**
     * @var string sync action action
     */
    const SYNC_ACTION = 'action';

    /**
     * @var string sync plugin version action
     */
    const SYNC_PLUGIN_DATA = 'plugin';

    /**
     * @var array cache time for catalog, account status, cms options and marketplace synchronisation
     */
    protected $_cacheTimes = array(
        self::SYNC_CATALOG => 21600,
        self::SYNC_CMS_OPTION => 86400,
        self::SYNC_STATUS_ACCOUNT => 86400,
        self::SYNC_MARKETPLACE => 43200,
        self::SYNC_PLUGIN_DATA => 86400,
    );

    /**
     * @var array valid sync actions
     */
    protected $_syncActions = array(
        self::SYNC_ORDER,
        self::SYNC_CMS_OPTION,
        self::SYNC_STATUS_ACCOUNT,
        self::SYNC_MARKETPLACE,
        self::SYNC_ACTION,
        self::SYNC_CATALOG,
        self::SYNC_PLUGIN_DATA,
    );

    /**
     * @var Lengow_Connector_Helper_Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var string marketplace file name
     */
    protected $_marketplaceJson = 'marketplaces.json';

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
        return in_array($action, $this->_syncActions, true);
    }

    /**
     * Plugin is blocked or not
     *
     * @return boolean
     */
    public function pluginIsBlocked()
    {
        if ($this->_configHelper->isNewMerchant()) {
            return true;
        }
        $statusAccount = $this->getStatusAccount();
        return $statusAccount['type'] === 'free_trial' && $statusAccount['expired'];
    }


    /**
     * Get Sync Data (Inscription / Update)
     *
     * @return array
     */
    public function getSyncData()
    {
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector');
        $data = array(
            'domain_name' => $_SERVER['SERVER_NAME'],
            'token' => $this->_configHelper->getToken(),
            'type' => self::CMS_TYPE,
            'version' => Mage::getVersion(),
            'plugin_version' => (string)Mage::getConfig()->getNode()->modules->Lengow_Connector->version,
            'email' => Mage::getStoreConfig('trans_email/ident_general/email'),
            'cron_url' => $helper->getCronUrl(),
            'shops' => array(),
        );
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $storeId = (int)$store->getId();
                    /** @var Lengow_Connector_Model_Export $export */
                    $export = Mage::getModel('lengow/export', array('store_id' => $storeId));
                    $data['shops'][] = array(
                        'token' => $this->_configHelper->getToken($storeId),
                        'shop_name' => $store->getName(),
                        'domain_url' => $store->getBaseUrl(),
                        'feed_url' => $helper->getExportUrl($storeId),
                        'total_product_number' => $export->getTotalProduct(),
                        'exported_product_number' => $export->getTotalExportedProduct(),
                        'enabled' => $this->_configHelper->storeIsActive($storeId),
                    );
                }
            }
        }
        return $data;
    }

    /**
     * Sync Lengow catalogs for order synchronisation
     *
     * @param boolean $force force cache update
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public function syncCatalog($force = false, $logOutput = false)
    {
        $success = false;
        $cleanCache = false;
        if ($this->_configHelper->isNewMerchant()) {
            return $success;
        }
        if (!$force) {
            $updatedAt = $this->_configHelper->get('last_catalog_update');
            if ($updatedAt !== null && (time() - (int)$updatedAt) < $this->_cacheTimes[self::SYNC_CATALOG]) {
                return $success;
            }
        }
        $result = Mage::getModel('lengow/connector')->queryApi(
            Lengow_Connector_Model_Connector::GET,
            Lengow_Connector_Model_Connector::API_CMS,
            array(),
            '',
            $logOutput
        );
        if (isset($result->cms)) {
            $cmsToken = $this->_configHelper->getToken();
            foreach ($result->cms as $cms) {
                if ($cms->token === $cmsToken) {
                    foreach ($cms->shops as $cmsShop) {
                        $store = $this->_configHelper->getStoreByToken($cmsShop->token);
                        if ($store) {
                            $catalogIdsChange = $this->_configHelper->setCatalogIds(
                                $cmsShop->catalog_ids,
                                (int)$store->getId()
                            );
                            $activeStoreChange = $this->_configHelper->setActiveStore((int)$store->getId());
                            if (!$cleanCache && ($catalogIdsChange || $activeStoreChange)) {
                                $cleanCache = true;
                            }
                        }
                    }
                    $success = true;
                    break;
                }
            }
        }
        // clean config cache to valid configuration
        if ($cleanCache) {
            // save last update date for a specific settings (change synchronisation interval time)
            $this->_configHelper->set('last_setting_update', time());
            Mage::app()->getCacheInstance()->cleanType('config');
        }
        $this->_configHelper->set('last_catalog_update', time());
        return $success;
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
            'shops' => array(),
        );
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $storeId = (int)$store->getId();
                    /** @var Lengow_Connector_Model_Export $export */
                    $export = Mage::getModel('lengow/export', array('store_id' => $storeId));
                    $data['shops'][] = array(
                        'token' => $this->_configHelper->getToken($storeId),
                        'enabled' => $this->_configHelper->storeIsActive($storeId),
                        'total_product_number' => $export->getTotalProduct(),
                        'exported_product_number' => $export->getTotalExportedProduct(),
                        'options' => $this->_configHelper->getAllValues($storeId),
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
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public function setCmsOption($force = false, $logOutput = false)
    {
        if ($this->_configHelper->isNewMerchant() || $this->_configHelper->debugModeIsActive()) {
            return false;
        }
        if (!$force) {
            $updatedAt = $this->_configHelper->get('last_option_cms_update');
            if ($updatedAt !== null && (time() - (int)$updatedAt) < $this->_cacheTimes[self::SYNC_CMS_OPTION]) {
                return false;
            }
        }
        $options = Mage::helper('core')->jsonEncode($this->getOptionData());
        Mage::getModel('lengow/connector')->queryApi(
            Lengow_Connector_Model_Connector::PUT,
            Lengow_Connector_Model_Connector::API_CMS,
            array(),
            $options,
            $logOutput
        );
        $this->_configHelper->set('last_option_cms_update', time());
        return true;
    }

    /**
     * Get Status Account
     *
     * @param boolean $force force cache update
     * @param boolean $logOutput see log or not
     *
     * @return array|false
     */
    public function getStatusAccount($force = false, $logOutput = false)
    {
        if ($this->_configHelper->isNewMerchant()) {
            return false;
        }
        if (!$force) {
            $updatedAt = $this->_configHelper->get('last_status_update');
            if ($updatedAt !== null && (time() - (int)$updatedAt) < $this->_cacheTimes[self::SYNC_STATUS_ACCOUNT]) {
                return json_decode($this->_configHelper->get('account_status'), true);
            }
        }
        $result = Mage::getModel('lengow/connector')->queryApi(
            Lengow_Connector_Model_Connector::GET,
            Lengow_Connector_Model_Connector::API_PLAN,
            array(),
            '',
            $logOutput
        );
        if (isset($result->isFreeTrial)) {
            $status = array(
                'type' => $result->isFreeTrial ? 'free_trial' : '',
                'day' => (int)$result->leftDaysBeforeExpired < 0 ? 0 : (int)$result->leftDaysBeforeExpired,
                'expired' => (bool)$result->isExpired,
                'legacy' => $result->accountVersion === 'v2' ? true : false,
            );
            $this->_configHelper->set('account_status', Mage::helper('core')->jsonEncode($status));
            $this->_configHelper->set('last_status_update', time());
            return $status;
        } else {
            if ($this->_configHelper->get('last_status_update')) {
                return json_decode($this->_configHelper->get('account_status'), true);
            }
        }
        return false;
    }

    /**
     * Get marketplace data
     *
     * @param boolean $force force cache update
     * @param boolean $logOutput see log or not
     *
     * @return array|false
     */
    public function getMarketplaces($force = false, $logOutput = false)
    {
        $folderPath = Mage::getModuleDir('etc', 'Lengow_Connector');
        $filePath = $folderPath . DS . $this->_marketplaceJson;
        if (!$force) {
            $updatedAt = $this->_configHelper->get('last_marketplace_update');
            if ($updatedAt !== null
                && (time() - (int)$updatedAt) < $this->_cacheTimes[self::SYNC_MARKETPLACE]
                && file_exists($filePath)
            ) {
                // recovering data with the marketplaces.json file
                $marketplacesData = file_get_contents($filePath);
                if ($marketplacesData) {
                    return json_decode($marketplacesData);
                }
            }
        }
        // recovering data with the API
        $result = Mage::getModel('lengow/connector')->queryApi(
            Lengow_Connector_Model_Connector::GET,
            Lengow_Connector_Model_Connector::API_MARKETPLACE,
            array(),
            '',
            $logOutput
        );
        if ($result && is_object($result) && !isset($result->error)) {
            // updated marketplaces.json file
            try {
                $file = new Varien_Io_File();
                $file->cd($folderPath);
                $file->streamOpen($this->_marketplaceJson, 'w+');
                $file->streamlock();
                $file->streamWrite(json_encode($result));
                $file->streamUnlock();
                $file->streamClose();
                $this->_configHelper->set('last_marketplace_update', time());
            } catch (Exception $e) {
                $helper = Mage::helper('lengow_connector/data');
                $helper->log(
                    Lengow_Connector_Helper_Data::CODE_IMPORT,
                    $helper->setLogMessage(
                        'log.import.marketplace_update_failed',
                        array('error_message' => $e->getMessage())
                    ),
                    $logOutput
                );
            }
            return $result;
        } else {
            // if the API does not respond, use marketplaces.json if it exists
            if (file_exists($filePath)) {
                $marketplacesData = file_get_contents($filePath);
                if ($marketplacesData) {
                    return json_decode($marketplacesData);
                }
            }
        }
        return false;
    }

    /**
     * Get Lengow plugin data (last version and download link)
     *
     * @param boolean $force force cache update
     * @param boolean $logOutput see log or not
     *
     * @return array|false
     */
    public function getPluginData($force = false, $logOutput = false)
    {
        if ($this->_configHelper->isNewMerchant()) {
            return false;
        }
        if (!$force) {
            $updatedAt = $this->_configHelper->get('last_plugin_data_update');
            if ($updatedAt !== null && (time() - (int)$updatedAt) < $this->_cacheTimes[self::SYNC_PLUGIN_DATA]) {
                return json_decode($this->_configHelper->get('plugin_data'), true);
            }
        }
        $plugins = Mage::getModel('lengow/connector')->queryApi(
            Lengow_Connector_Model_Connector::GET,
            Lengow_Connector_Model_Connector::API_PLUGIN,
            array(),
            '',
            $logOutput
        );
        if ($plugins) {
            $pluginData = false;
            foreach ($plugins as $plugin) {
                if ($plugin->type === self::CMS_TYPE) {
                    $pluginData = array(
                        'version' => $plugin->version,
                        'download_link' => $plugin->archive,
                    );
                    break;
                }
            }
            if ($pluginData) {
                $this->_configHelper->set('plugin_data', Mage::helper('core')->jsonEncode($pluginData));
                $this->_configHelper->set('last_plugin_data_update', time());
                return $pluginData;
            }
        } else {
            if ($this->_configHelper->get('plugin_data')) {
                return json_decode($this->_configHelper->get('plugin_data'), true);
            }
        }
        return false;
    }
}
