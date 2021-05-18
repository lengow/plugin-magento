<?php
/**
 * Copyright 2021 Lengow SAS
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
 * @copyright   2021 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Helper toolbox
 */
class Lengow_Connector_Helper_Toolbox extends Mage_Core_Helper_Abstract
{
    /* Toolbox GET params */
    const PARAM_TOKEN = 'token';
    const PARAM_TOOLBOX_ACTION = 'toolbox_action';
    const PARAM_DATE = 'date';
    const PARAM_TYPE = 'type';

    /* Toolbox Actions */
    const ACTION_DATA = 'data';
    const ACTION_LOG = 'log';

    /* Data type */
    const DATA_TYPE_ALL = 'all';
    const DATA_TYPE_CHECKLIST = 'checklist';
    const DATA_TYPE_CHECKSUM = 'checksum';
    const DATA_TYPE_CMS = 'cms';
    const DATA_TYPE_LOG = 'log';
    const DATA_TYPE_PLUGIN = 'plugin';
    const DATA_TYPE_OPTION = 'option';
    const DATA_TYPE_SHOP = 'shop';
    const DATA_TYPE_SYNCHRONIZATION = 'synchronization';

    /* Toolbox Data  */
    const CHECKLIST = 'checklist';
    const CHECKLIST_CURL_ACTIVATED = 'curl_activated';
    const CHECKLIST_SIMPLE_XML_ACTIVATED = 'simple_xml_activated';
    const CHECKLIST_JSON_ACTIVATED = 'json_activated';
    const CHECKLIST_MD5_SUCCESS = 'md5_success';
    const PLUGIN = 'plugin';
    const PLUGIN_CMS_VERSION = 'cms_version';
    const PLUGIN_VERSION = 'plugin_version';
    const PLUGIN_DEBUG_MODE_DISABLE = 'debug_mode_disable';
    const PLUGIN_WRITE_PERMISSION = 'write_permission';
    const PLUGIN_SERVER_IP = 'server_ip';
    const PLUGIN_AUTHORIZED_IP_ENABLE = 'authorized_ip_enable';
    const PLUGIN_AUTHORIZED_IPS = 'authorized_ips';
    const PLUGIN_TOOLBOX_URL = 'toolbox_url';
    const SYNCHRONIZATION = 'synchronization';
    const SYNCHRONIZATION_CMS_TOKEN = 'cms_token';
    const SYNCHRONIZATION_CRON_URL = 'cron_url';
    const SYNCHRONIZATION_NUMBER_ORDERS_IMPORTED = 'number_orders_imported';
    const SYNCHRONIZATION_NUMBER_ORDERS_WAITING_SHIPMENT = 'number_orders_waiting_shipment';
    const SYNCHRONIZATION_NUMBER_ORDERS_IN_ERROR = 'number_orders_in_error';
    const SYNCHRONIZATION_SYNCHRONIZATION_IN_PROGRESS = 'synchronization_in_progress';
    const SYNCHRONIZATION_LAST_SYNCHRONIZATION = 'last_synchronization';
    const SYNCHRONIZATION_LAST_SYNCHRONIZATION_TYPE = 'last_synchronization_type';
    const CMS_OPTIONS = 'cms_options';
    const SHOPS = 'shops';
    const SHOP_ID = 'shop_id';
    const SHOP_NAME = 'shop_name';
    const SHOP_DOMAIN_URL = 'domain_url';
    const SHOP_TOKEN = 'shop_token';
    const SHOP_FEED_URL = 'feed_url';
    const SHOP_ENABLED = 'enabled';
    const SHOP_CATALOG_IDS = 'catalog_ids';
    const SHOP_NUMBER_PRODUCTS_AVAILABLE = 'number_products_available';
    const SHOP_NUMBER_PRODUCTS_EXPORTED = 'number_products_exported';
    const SHOP_LAST_EXPORT = 'last_export';
    const SHOP_OPTIONS = 'shop_options';
    const CHECKSUM = 'checksum';
    const CHECKSUM_AVAILABLE = 'available';
    const CHECKSUM_SUCCESS = 'success';
    const CHECKSUM_NUMBER_FILES_CHECKED = 'number_files_checked';
    const CHECKSUM_NUMBER_FILES_MODIFIED = 'number_files_modified';
    const CHECKSUM_NUMBER_FILES_DELETED = 'number_files_deleted';
    const CHECKSUM_FILE_MODIFIED = 'file_modified';
    const CHECKSUM_FILE_DELETED = 'file_deleted';
    const LOGS = 'logs';

    /* Toolbox files */
    const FILE_CHECKMD5 = 'checkmd5.csv';
    const FILE_TEST = 'test.txt';

    /**
     * @var array valid toolbox actions
     */
    private $toolboxActions = array(
        self::ACTION_DATA,
        self::ACTION_LOG,
    );

    /**
     * @var Lengow_Connector_Helper_Data Lengow helper instance
     */
    protected $_helper;

    /**
     * @var Lengow_Connector_Helper_Security Lengow security helper instance
     */
    protected $_securityHelper;

    /**
     * @var Lengow_Connector_Helper_Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var Lengow_Connector_Helper_Import Lengow import helper instance
     */
    protected $_importHelper;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->_helper = Mage::helper('lengow_connector');
        $this->_securityHelper = Mage::helper('lengow_connector/security');
        $this->_configHelper = Mage::helper('lengow_connector/config');
        $this->_importHelper = Mage::helper('lengow_connector/import');
    }

    /**
     * Get all toolbox data
     *
     * @param string $type Toolbox data type
     *
     * @return array
     */
    public function getData($type = self::DATA_TYPE_CMS)
    {
        switch ($type) {
            case self::DATA_TYPE_ALL:
                return $this->getAllData();
            case self::DATA_TYPE_CHECKLIST:
                return $this->getChecklistData();
            case self::DATA_TYPE_CHECKSUM:
                return $this->getChecksumData();
            case self::DATA_TYPE_LOG:
                return $this->getLogData();
            case self::DATA_TYPE_OPTION:
                return $this->getOptionsData();
            case self::DATA_TYPE_PLUGIN:
                return $this->getPluginData();
            case self::DATA_TYPE_SHOP:
                return $this->getShopData();
            case self::DATA_TYPE_SYNCHRONIZATION:
                return $this->getSynchronizationData();
            default:
            case self::DATA_TYPE_CMS:
                return $this->getCmsData();
        }
    }

    /**
     * Download log file individually or globally
     *
     * @param string|null $date name of file to download
     */
    public function downloadLog($date = null)
    {
        Mage::getModel('lengow/log')->download($date);
    }

    /**
     * Is toolbox action
     *
     * @param string $action toolbox action
     *
     * @return boolean
     */
    public function isToolboxAction($action)
    {
        return in_array($action, $this->toolboxActions, true);
    }

    /**
     * Check if PHP Curl is activated
     *
     * @return boolean
     */
    public static function isCurlActivated()
    {
        return function_exists('curl_version');
    }

    /**
     * Get all data
     *
     * @return array
     */
    private function getAllData()
    {
        return array(
            self::CHECKLIST => $this->getChecklistData(),
            self::PLUGIN => $this->getPluginData(),
            self::SYNCHRONIZATION => $this->getSynchronizationData(),
            self::CMS_OPTIONS => $this->_configHelper->getAllValues(null, true),
            self::SHOPS => $this->getShopData(),
            self::CHECKSUM => $this->getChecksumData(),
            self::LOGS => $this->getLogData(),
        );
    }

    /**
     * Get cms data
     *
     * @return array
     */
    private function getCmsData()
    {
        return array(
            self::CHECKLIST => $this->getChecklistData(),
            self::PLUGIN => $this->getPluginData(),
            self::SYNCHRONIZATION => $this->getSynchronizationData(),
            self::CMS_OPTIONS => $this->_configHelper->getAllValues(null, true),
        );
    }

    /**
     * Get array of requirements
     *
     * @return array
     */
    private function getChecklistData()
    {
        $checksumData = $this->getChecksumData();
        return array(
            self::CHECKLIST_CURL_ACTIVATED => self::isCurlActivated(),
            self::CHECKLIST_SIMPLE_XML_ACTIVATED => $this->isSimpleXMLActivated(),
            self::CHECKLIST_JSON_ACTIVATED => $this->isJsonActivated(),
            self::CHECKLIST_MD5_SUCCESS => $checksumData[self::CHECKSUM_SUCCESS],
        );
    }

    /**
     * Get array of plugin data
     *
     * @return array
     */
    private function getPluginData()
    {
        return array(
            self::PLUGIN_CMS_VERSION => Mage::getVersion(),
            self::PLUGIN_VERSION => $this->_securityHelper->getPluginVersion(),
            self::PLUGIN_DEBUG_MODE_DISABLE => !$this->_configHelper->debugModeIsActive(),
            self::PLUGIN_WRITE_PERMISSION => $this->testWritePermission(),
            self::PLUGIN_SERVER_IP => $_SERVER['SERVER_ADDR'],
            self::PLUGIN_AUTHORIZED_IP_ENABLE => (bool) $this->_configHelper->get(
                Lengow_Connector_Helper_Config::AUTHORIZED_IP_ENABLED
            ),
            self::PLUGIN_AUTHORIZED_IPS => $this->_configHelper->getAuthorizedIps(),
            self::PLUGIN_TOOLBOX_URL => $this->_helper->getToolboxUrl(),
        );
    }

    /**
     * Get array of synchronization data
     *
     * @return array
     */
    private function getSynchronizationData()
    {
        $lengowOrder = Mage::getModel('lengow/import_order');
        $lastImport = $this->_importHelper->getLastImport();
        return array(
            self::SYNCHRONIZATION_CMS_TOKEN => $this->_configHelper->getToken(),
            self::SYNCHRONIZATION_CRON_URL => $this->_helper->getCronUrl(),
            self::SYNCHRONIZATION_NUMBER_ORDERS_IMPORTED => $lengowOrder->countOrderImportedByLengow(),
            self::SYNCHRONIZATION_NUMBER_ORDERS_WAITING_SHIPMENT => $lengowOrder->countOrderToBeSent(),
            self::SYNCHRONIZATION_NUMBER_ORDERS_IN_ERROR => $lengowOrder->countOrderWithError(),
            self::SYNCHRONIZATION_SYNCHRONIZATION_IN_PROGRESS => $this->_importHelper->importIsInProcess(),
            self::SYNCHRONIZATION_LAST_SYNCHRONIZATION => $lastImport['type'] === 'none' ? 0 : $lastImport['timestamp'],
            self::SYNCHRONIZATION_LAST_SYNCHRONIZATION_TYPE => $lastImport['type'],
        );
    }

    /**
     * Get array of export data
     *
     * @return array
     */
    private function getShopData()
    {
        $exportData = array();
        /** @var Mage_Core_Model_Store[] $stores */
        $stores = Mage::getResourceModel('core/store_collection');
        if (empty($stores)) {
            return $exportData;
        }
        foreach ($stores as $store) {
            $storeId = (int) $store->getId();
            /** @var Lengow_Connector_Model_Export $lengowExport */
            $lengowExport = Mage::getModel(
                'lengow/export',
                array(Lengow_Connector_Model_Export::PARAM_STORE_ID => $storeId)
            );
            $lastExport = $this->_configHelper->get(Lengow_Connector_Helper_Config::LAST_UPDATE_EXPORT, $storeId);
            $exportData[] = array(
                self::SHOP_ID => $storeId,
                self::SHOP_NAME => $store->getName(),
                self::SHOP_DOMAIN_URL => $store->getBaseUrl(),
                self::SHOP_TOKEN => $this->_configHelper->getToken($storeId),
                self::SHOP_FEED_URL => $this->_helper->getExportUrl($storeId),
                self::SHOP_ENABLED => $this->_configHelper->storeIsActive($storeId),
                self::SHOP_CATALOG_IDS => $this->_configHelper->getCatalogIds($storeId),
                self::SHOP_NUMBER_PRODUCTS_AVAILABLE => $lengowExport->getTotalProduct(),
                self::SHOP_NUMBER_PRODUCTS_EXPORTED => $lengowExport->getTotalExportProduct(),
                self::SHOP_LAST_EXPORT => empty($lastExport) ? 0 : (int) $lastExport,
                self::SHOP_OPTIONS => $this->_configHelper->getAllValues($storeId, true),
            );
        }
        return $exportData;
    }

    /**
     * Get array of options data
     *
     * @return array
     */
    private function getOptionsData()
    {
        $optionData = array(
            self::CMS_OPTIONS => $this->_configHelper->getAllValues(),
            self::SHOP_OPTIONS => array(),
        );
        $stores = Mage::getResourceModel('core/store_collection');
        foreach ($stores as $store) {
            $optionData[self::SHOP_OPTIONS][] = $this->_configHelper->getAllValues((int) $store->getId());
        }
        return $optionData;
    }

    /**
     * Get files checksum
     *
     * @return array
     */
    private function getChecksumData()
    {
        $fileCounter = 0;
        $fileModified = array();
        $fileDeleted = array();
        $sep = DIRECTORY_SEPARATOR;
        $fileName = Mage::getModuleDir('etc', 'Lengow_Connector') . $sep . self::FILE_CHECKMD5;
        if (file_exists($fileName)) {
            $md5Available = true;
            if (($file = fopen($fileName, 'r')) !== false) {
                while (($data = fgetcsv($file, 1000, '|')) !== false) {
                    $fileCounter++;
                    $shortPath =  $data[0];
                    $filePath = Mage::getBaseDir() . $data[0];
                    if (file_exists($filePath)) {
                        $fileMd = md5_file($filePath);
                        if ($fileMd !== $data[1]) {
                            $fileModified[] = $shortPath;
                        }
                    } else {
                        $fileDeleted[] = $shortPath;
                    }
                }
                fclose($file);
            }
        } else {
            $md5Available = false;
        }
        $fileModifiedCounter = count($fileModified);
        $fileDeletedCounter = count($fileDeleted);
        $md5Success = $md5Available && !($fileModifiedCounter > 0) && !($fileDeletedCounter > 0);
        return array(
            self::CHECKSUM_AVAILABLE => $md5Available,
            self::CHECKSUM_SUCCESS => $md5Success,
            self::CHECKSUM_NUMBER_FILES_CHECKED => $fileCounter,
            self::CHECKSUM_NUMBER_FILES_MODIFIED => $fileModifiedCounter,
            self::CHECKSUM_NUMBER_FILES_DELETED => $fileDeletedCounter,
            self::CHECKSUM_FILE_MODIFIED => $fileModified,
            self::CHECKSUM_FILE_DELETED => $fileDeleted,
        );
    }

    /**
     * Get all log files available
     *
     * @return array
     */
    private function getLogData()
    {
        $logs = array();
        $logDates = Mage::getModel('lengow/log')->getAvailableLogDates();
        if (!empty($logDates)) {
            foreach ($logDates as $date) {
                $logs[] = array(
                    Lengow_Connector_Model_Log::LOG_DATE => $date,
                    Lengow_Connector_Model_Log::LOG_LINK => $this->_helper->getToolboxUrl(
                        array(
                            self::PARAM_TOOLBOX_ACTION => self::ACTION_LOG,
                            self::PARAM_DATE => urlencode($date),
                        )
                    ),
                );
            }
            $logs[] = array(
                Lengow_Connector_Model_Log::LOG_DATE => null,
                Lengow_Connector_Model_Log::LOG_LINK => $this->_helper->getToolboxUrl(
                    array(
                        self::PARAM_TOOLBOX_ACTION => self::ACTION_LOG,
                    )
                ),
            );
        }
        return $logs;
    }

    /**
     * Check if SimpleXML Extension is activated
     *
     * @return boolean
     */
    private function isSimpleXMLActivated()
    {
        return function_exists('simplexml_load_file');
    }

    /**
     * Check if SimpleXML Extension is activated
     *
     * @return boolean
     */
    private function isJsonActivated()
    {
        return function_exists('json_decode');
    }

    /**
     * Test write permission for log and export in file
     *
     * @return boolean
     */
    private function testWritePermission()
    {
        $sep = DIRECTORY_SEPARATOR;
        $filePath = Mage::getBaseDir('media') . $sep . 'lengow' . $sep . self::FILE_TEST;
        try {
            $file = fopen($filePath, 'w+');
            if (!$file) {
                return false;
            }
            unlink($filePath);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
