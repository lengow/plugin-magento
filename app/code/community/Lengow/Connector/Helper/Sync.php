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

    /* Sync actions */
    const SYNC_CATALOG = 'catalog';
    const SYNC_CMS_OPTION = 'cms_option';
    const SYNC_STATUS_ACCOUNT = 'status_account';
    const SYNC_MARKETPLACE = 'marketplace';
    const SYNC_ORDER = 'order';
    const SYNC_ACTION = 'action';
    const SYNC_PLUGIN_DATA = 'plugin';

    /**
     * @var string marketplace file name
     */
    const MARKETPLACE_FILE = 'marketplaces.json';

    /* Plugin link types */
    const LINK_TYPE_HELP_CENTER = 'help_center';
    const LINK_TYPE_CHANGELOG = 'changelog';
    const LINK_TYPE_UPDATE_GUIDE = 'update_guide';
    const LINK_TYPE_SUPPORT = 'support';

    /* Default plugin links */
    const LINK_HELP_CENTER = 'https://support.lengow.com/kb/guide/en/ClPhE37tgf';
    const LINK_CHANGELOG = 'https://support.lengow.com/kb/guide/en/hI6niKLnJG';
    const LINK_UPDATE_GUIDE = 'https://support.lengow.com/kb/guide/en/ClPhE37tgf/Steps/25858,117750';
    const LINK_SUPPORT = 'https://help-support.lengow.com/hc/en-us/requests/new';

    /* Api iso codes */
    const API_ISO_CODE_EN = 'en';
    const API_ISO_CODE_FR = 'fr';
    const API_ISO_CODE_DE = 'de';

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
     * @var array iso code correspondence for plugin links
     */
    protected $_genericIsoCodes = array(
        self::API_ISO_CODE_EN => Lengow_Connector_Helper_Translation::ISO_CODE_EN,
        self::API_ISO_CODE_FR => Lengow_Connector_Helper_Translation::ISO_CODE_FR,
        self::API_ISO_CODE_DE => Lengow_Connector_Helper_Translation::ISO_CODE_DE,
    );

    /**
     * @var array default plugin links when the API is not available
     */
    protected $_defaultPluginLinks = array(
        self::LINK_TYPE_HELP_CENTER => self::LINK_HELP_CENTER,
        self::LINK_TYPE_CHANGELOG => self::LINK_CHANGELOG,
        self::LINK_TYPE_UPDATE_GUIDE => self::LINK_UPDATE_GUIDE,
        self::LINK_TYPE_SUPPORT => self::LINK_SUPPORT,
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
        return $statusAccount && ($statusAccount['type'] === 'free_trial' && $statusAccount['expired']);
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
            'plugin_version' => (string) Mage::getConfig()->getNode()->modules->Lengow_Connector->version,
            'email' => Mage::getStoreConfig('trans_email/ident_general/email'),
            'cron_url' => $helper->getCronUrl(),
            'toolbox_url' => $helper->getToolboxUrl(),
            'shops' => array(),
        );
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $storeId = (int) $store->getId();
                    /** @var Lengow_Connector_Model_Export $export */
                    $export = Mage::getModel(
                        'lengow/export',
                        array(Lengow_Connector_Model_Export::PARAM_STORE_ID => $storeId)
                    );
                    $data['shops'][] = array(
                        'token' => $this->_configHelper->getToken($storeId),
                        'shop_name' => $store->getName(),
                        'domain_url' => $store->getBaseUrl(),
                        'feed_url' => $helper->getExportUrl($storeId),
                        'total_product_number' => $export->getTotalProduct(),
                        'exported_product_number' => $export->getTotalExportProduct(),
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
            $updatedAt = $this->_configHelper->get(Lengow_Connector_Helper_Config::LAST_UPDATE_CATALOG);
            if ($updatedAt !== null && (time() - (int) $updatedAt) < $this->_cacheTimes[self::SYNC_CATALOG]) {
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
                                (int) $store->getId()
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
            $this->_configHelper->set(Lengow_Connector_Helper_Config::LAST_UPDATE_SETTING, time());
            Mage::app()->getCacheInstance()->cleanType('config');
        }
        $this->_configHelper->set(Lengow_Connector_Helper_Config::LAST_UPDATE_CATALOG, time());
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
            'plugin_version' => (string) Mage::getConfig()->getNode()->modules->Lengow_Connector->version,
            'options' => $this->_configHelper->getAllValues(),
            'shops' => array(),
        );
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $storeId = (int) $store->getId();
                    /** @var Lengow_Connector_Model_Export $export */
                    $export = Mage::getModel(
                        'lengow/export',
                        array(Lengow_Connector_Model_Export::PARAM_STORE_ID => $storeId)
                    );
                    $data['shops'][] = array(
                        'token' => $this->_configHelper->getToken($storeId),
                        'enabled' => $this->_configHelper->storeIsActive($storeId),
                        'total_product_number' => $export->getTotalProduct(),
                        'exported_product_number' => $export->getTotalExportProduct(),
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
            $updatedAt = $this->_configHelper->get(Lengow_Connector_Helper_Config::LAST_UPDATE_OPTION_CMS);
            if ($updatedAt !== null && (time() - (int) $updatedAt) < $this->_cacheTimes[self::SYNC_CMS_OPTION]) {
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
        $this->_configHelper->set(Lengow_Connector_Helper_Config::LAST_UPDATE_OPTION_CMS, time());
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
            $updatedAt = $this->_configHelper->get(Lengow_Connector_Helper_Config::LAST_UPDATE_ACCOUNT_STATUS_DATA);
            if ($updatedAt !== null && (time() - (int) $updatedAt) < $this->_cacheTimes[self::SYNC_STATUS_ACCOUNT]) {
                return json_decode(
                    $this->_configHelper->get(Lengow_Connector_Helper_Config::ACCOUNT_STATUS_DATA),
                    true
                );
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
                'day' => (int) $result->leftDaysBeforeExpired < 0 ? 0 : (int) $result->leftDaysBeforeExpired,
                'expired' => (bool) $result->isExpired,
                'legacy' => $result->accountVersion === 'v2',
            );
            $this->_configHelper->set(
                Lengow_Connector_Helper_Config::ACCOUNT_STATUS_DATA,
                Mage::helper('core')->jsonEncode($status)
            );
            $this->_configHelper->set(Lengow_Connector_Helper_Config::LAST_UPDATE_ACCOUNT_STATUS_DATA, time());
            return $status;
        }
        if ($this->_configHelper->get(Lengow_Connector_Helper_Config::ACCOUNT_STATUS_DATA)) {
            return json_decode($this->_configHelper->get(Lengow_Connector_Helper_Config::ACCOUNT_STATUS_DATA), true);
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
        $filePath = $folderPath . DIRECTORY_SEPARATOR . self::MARKETPLACE_FILE;
        if (!$force) {
            $updatedAt = $this->_configHelper->get(Lengow_Connector_Helper_Config::LAST_UPDATE_MARKETPLACE);
            if ($updatedAt !== null
                && (time() - (int) $updatedAt) < $this->_cacheTimes[self::SYNC_MARKETPLACE]
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
                $file->streamOpen(self::MARKETPLACE_FILE, 'w+');
                $file->streamlock();
                $file->streamWrite(json_encode($result));
                $file->streamUnlock();
                $file->streamClose();
                $this->_configHelper->set(Lengow_Connector_Helper_Config::LAST_UPDATE_MARKETPLACE, time());
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
        }
        // if the API does not respond, use marketplaces.json if it exists
        if (file_exists($filePath)) {
            $marketplacesData = file_get_contents($filePath);
            if ($marketplacesData) {
                // don't add true, decoded data are used as object
                return json_decode($marketplacesData);
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
        if (!$force) {
            $updatedAt = $this->_configHelper->get(Lengow_Connector_Helper_Config::LAST_UPDATE_PLUGIN_DATA);
            if ($updatedAt !== null && (time() - (int) $updatedAt) < $this->_cacheTimes[self::SYNC_PLUGIN_DATA]) {
                return json_decode($this->_configHelper->get(Lengow_Connector_Helper_Config::PLUGIN_DATA), true);
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
                    $cmsMinVersion = '';
                    $cmsMaxVersion = '';
                    $pluginLinks = array();
                    $currentVersion = $plugin->version;
                    if (!empty($plugin->versions)) {
                        foreach ($plugin->versions as $version) {
                            if ($version->version === $currentVersion) {
                                $cmsMinVersion = $version->cms_min_version;
                                $cmsMaxVersion = $version->cms_max_version;
                                break;
                            }
                        }
                    }
                    if (!empty($plugin->links)) {
                        foreach ($plugin->links as $link) {
                            if (array_key_exists($link->language->iso_a2, $this->_genericIsoCodes)) {
                                $genericIsoCode = $this->_genericIsoCodes[$link->language->iso_a2];
                                $pluginLinks[$genericIsoCode][$link->link_type] = $link->link;
                            }
                        }
                    }
                    $pluginData = array(
                        'version' => $currentVersion,
                        'download_link' => $plugin->archive,
                        'cms_min_version' => $cmsMinVersion,
                        'cms_max_version' => $cmsMaxVersion,
                        'links' => $pluginLinks,
                        'extensions' => $plugin->extensions,
                    );
                    break;
                }
            }
            if ($pluginData) {
                $this->_configHelper->set(
                    Lengow_Connector_Helper_Config::PLUGIN_DATA,
                    Mage::helper('core')->jsonEncode($pluginData)
                );
                $this->_configHelper->set(Lengow_Connector_Helper_Config::LAST_UPDATE_PLUGIN_DATA, time());
                return $pluginData;
            }
        } elseif ($this->_configHelper->get(Lengow_Connector_Helper_Config::PLUGIN_DATA)) {
            return json_decode($this->_configHelper->get(Lengow_Connector_Helper_Config::PLUGIN_DATA), true);
        }
        return false;
    }

    /**
     * Get an array of plugin links for a specific iso code
     *
     * @param string|null $isoCode
     *
     * @return array
     */
    public function getPluginLinks($isoCode = null)
    {
        $pluginData = $this->getPluginData();
        if (!$pluginData) {
            return $this->_defaultPluginLinks;
        }
        // check if the links are available in the locale
        $isoCode = $isoCode ?: Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE;
        $localeLinks = isset($pluginData['links'][$isoCode]) ? $pluginData['links'][$isoCode] : false;
        $defaultLocaleLinks = isset($pluginData['links'][Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE])
            ? $pluginData['links'][Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE]
            : false;
        // for each type of link, we check if the link is translated
        $pluginLinks = array();
        foreach ($this->_defaultPluginLinks as $linkType => $defaultLink) {
            if ($localeLinks && isset($localeLinks[$linkType])) {
                $link = $localeLinks[$linkType];
            } elseif ($defaultLocaleLinks && isset($defaultLocaleLinks[$linkType])) {
                $link = $defaultLocaleLinks[$linkType];
            } else {
                $link = $defaultLink;
            }
            $pluginLinks[$linkType] = $link;
        }
        return $pluginLinks;
    }
}
