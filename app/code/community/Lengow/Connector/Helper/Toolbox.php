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
    const PARAM_CREATED_FROM = 'created_from';
    const PARAM_CREATED_TO = 'created_to';
    const PARAM_DATE = 'date';
    const PARAM_DAYS = 'days';
    const PARAM_FORCE = 'force';
    const PARAM_MARKETPLACE_NAME = 'marketplace_name';
    const PARAM_MARKETPLACE_SKU = 'marketplace_sku';
    const PARAM_PROCESS = 'process';
    const PARAM_SHOP_ID = 'shop_id';
    const PARAM_TOKEN = 'token';
    const PARAM_TOOLBOX_ACTION = 'toolbox_action';
    const PARAM_TYPE = 'type';

    /* Toolbox Actions */
    const ACTION_DATA = 'data';
    const ACTION_LOG = 'log';
    const ACTION_ORDER = 'order';

    /* Data type */
    const DATA_TYPE_ACTION = 'action';
    const DATA_TYPE_ALL = 'all';
    const DATA_TYPE_CHECKLIST = 'checklist';
    const DATA_TYPE_CHECKSUM = 'checksum';
    const DATA_TYPE_CMS = 'cms';
    const DATA_TYPE_ERROR = 'error';
    const DATA_TYPE_EXTRA = 'extra';
    const DATA_TYPE_LOG = 'log';
    const DATA_TYPE_PLUGIN = 'plugin';
    const DATA_TYPE_OPTION = 'option';
    const DATA_TYPE_ORDER = 'order';
    const DATA_TYPE_ORDER_STATUS = 'order_status';
    const DATA_TYPE_SHOP = 'shop';
    const DATA_TYPE_SYNCHRONIZATION = 'synchronization';

    /* Process type */
    const PROCESS_TYPE_GET_DATA = 'get_data';
    const PROCESS_TYPE_SYNC = 'sync';

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

    /* Toolbox order data  */
    const ID = 'id';
    const ORDERS = 'orders';
    const ORDER_MARKETPLACE_SKU = 'marketplace_sku';
    const ORDER_MARKETPLACE_NAME = 'marketplace_name';
    const ORDER_MARKETPLACE_LABEL = 'marketplace_label';
    const ORDER_MERCHANT_ORDER_ID = 'merchant_order_id';
    const ORDER_MERCHANT_ORDER_REFERENCE = 'merchant_order_reference';
    const ORDER_DELIVERY_ADDRESS_ID = 'delivery_address_id';
    const ORDER_DELIVERY_COUNTRY_ISO = 'delivery_country_iso';
    const ORDER_PROCESS_STATE = 'order_process_state';
    const ORDER_STATUSES = 'order_statuses';
    const ORDER_STATUS = 'order_status';
    const ORDER_MERCHANT_ORDER_STATUS = 'merchant_order_status';
    const ORDER_TOTAL_PAID = 'total_paid';
    const ORDER_MERCHANT_TOTAL_PAID = 'merchant_total_paid';
    const ORDER_COMMISSION= 'commission';
    const ORDER_CURRENCY = 'currency';
    const ORDER_DATE = 'order_date';
    const ORDER_ITEMS = 'order_items';
    const ORDER_IS_REIMPORTED = 'is_reimported';
    const ORDER_IS_IN_ERROR = 'is_in_error';
    const ORDER_ACTION_IN_PROGRESS = 'action_in_progress';
    const CUSTOMER = 'customer';
    const CUSTOMER_NAME = 'name';
    const CUSTOMER_EMAIL = 'email';
    const CUSTOMER_VAT_NUMBER = 'vat_number';
    const ORDER_TYPES = 'order_types';
    const ORDER_TYPE_EXPRESS = 'is_express';
    const ORDER_TYPE_PRIME = 'is_prime';
    const ORDER_TYPE_BUSINESS = 'is_business';
    const ORDER_TYPE_DELIVERED_BY_MARKETPLACE = 'is_delivered_by_marketplace';
    const TRACKING = 'tracking';
    const TRACKING_CARRIER = 'carrier';
    const TRACKING_METHOD = 'method';
    const TRACKING_NUMBER = 'tracking_number';
    const TRACKING_RELAY_ID = 'relay_id';
    const TRACKING_MERCHANT_CARRIER = 'merchant_carrier';
    const TRACKING_MERCHANT_TRACKING_NUMBER = 'merchant_tracking_number';
    const TRACKING_MERCHANT_TRACKING_URL = 'merchant_tracking_url';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const IMPORTED_AT = 'imported_at';
    const ERRORS = 'errors';
    const ERROR_TYPE = 'type';
    const ERROR_MESSAGE = 'message';
    const ERROR_CODE = 'code';
    const ERROR_FINISHED = 'is_finished';
    const ERROR_REPORTED = 'is_reported';
    const ACTIONS = 'actions';
    const ACTION_ID = 'action_id';
    const ACTION_PARAMETERS = 'parameters';
    const ACTION_RETRY = 'retry';
    const ACTION_FINISH = 'is_finished';

    /* Process state labels */
    const PROCESS_STATE_NEW = 'new';
    const PROCESS_STATE_IMPORT = 'import';
    const PROCESS_STATE_FINISH = 'finish';

    /* Error type labels */
    const TYPE_ERROR_IMPORT = 'import';
    const TYPE_ERROR_SEND = 'send';

    /* PHP extensions */
    const PHP_EXTENSION_CURL = 'curl_version';
    const PHP_EXTENSION_SIMPLEXML = 'simplexml_load_file';
    const PHP_EXTENSION_JSON = 'json_decode';

    /* Toolbox files */
    const FILE_CHECKMD5 = 'checkmd5.csv';
    const FILE_TEST = 'test.txt';

    /**
     * @var array valid toolbox actions
     */
    private $toolboxActions = array(
        self::ACTION_DATA,
        self::ACTION_LOG,
        self::ACTION_ORDER,
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
     * Start order synchronization based on specific parameters
     *
     * @param array $params synchronization parameters
     *
     * @return array
     */
    public function syncOrders($params = array())
    {
        // get all params for order synchronization
        $params = $this->filterParamsForSync($params);
        /** @var Lengow_Connector_Model_Import $import */
        $import = Mage::getModel('lengow/import', $params);
        $result = $import->exec();
        // if global error return error message and request http code
        if (isset($result[Lengow_Connector_Model_Import::ERRORS][0])) {
            return $this->generateErrorReturn(
                Lengow_Connector_Model_Connector::CODE_403,
                $result[Lengow_Connector_Model_Import::ERRORS][0]
            );
        }
        unset($result[Lengow_Connector_Model_Import::ERRORS]);
        return $result;
    }

    /**
     * Get all order data from a marketplace reference
     *
     * @param string|null $marketplaceSku marketplace order reference
     * @param string|null $marketplaceName marketplace code
     * @param string $type Toolbox order data type
     *
     * @return array
     */
    public function getOrderData($marketplaceSku = null, $marketplaceName = null, $type = self::DATA_TYPE_ORDER)
    {
        $lengowOrders = $marketplaceSku && $marketplaceName
            ? Mage::getModel('lengow/import_order')->getAllLengowOrders($marketplaceSku, $marketplaceName)
            : array();
        // if no reference is found, process is blocked
        if (empty($lengowOrders)) {
            return $this->generateErrorReturn(
                Lengow_Connector_Model_Connector::CODE_404,
                $this->_helper->setLogMessage('log.import.unable_find_order')
            );
        }
        $orders = array();
        foreach ($lengowOrders as $data) {
            if ($type === self::DATA_TYPE_EXTRA) {
                return $this->getOrderExtraData($data);
            }
            $marketplaceLabel = $data[Lengow_Connector_Model_Import_Order::FIELD_MARKETPLACE_LABEL];
            $orders[] = $this->getOrderDataByType($data, $type);
        }
        return array(
            self::ORDER_MARKETPLACE_SKU => $marketplaceSku,
            self::ORDER_MARKETPLACE_NAME => $marketplaceName,
            self::ORDER_MARKETPLACE_LABEL => isset($marketplaceLabel) ? $marketplaceLabel : null,
            self::ORDERS => $orders,
        );
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
        return function_exists(self::PHP_EXTENSION_CURL);
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
        return function_exists(self::PHP_EXTENSION_SIMPLEXML);
    }

    /**
     * Check if SimpleXML Extension is activated
     *
     * @return boolean
     */
    private function isJsonActivated()
    {
        return function_exists(self::PHP_EXTENSION_JSON);
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

    /**
     * Filter parameters for order synchronization
     *
     * @param array $params synchronization params
     *
     * @return array
     */
    private function filterParamsForSync($params = array())
    {
        $paramsFiltered = array(
            Lengow_Connector_Model_Import::PARAM_TYPE => Lengow_Connector_Model_Import::TYPE_TOOLBOX,
        );
        if (isset(
            $params[self::PARAM_MARKETPLACE_SKU],
            $params[self::PARAM_MARKETPLACE_NAME],
            $params[self::PARAM_SHOP_ID]
        )) {
            // get all parameters to synchronize a specific order
            $paramsFiltered[Lengow_Connector_Model_Import::PARAM_MARKETPLACE_SKU] = $params[
                self::PARAM_MARKETPLACE_SKU
            ];
            $paramsFiltered[Lengow_Connector_Model_Import::PARAM_MARKETPLACE_NAME] = $params[
                self::PARAM_MARKETPLACE_NAME
            ];
            $paramsFiltered[Lengow_Connector_Model_Import::PARAM_STORE_ID] = (int) $params[self::PARAM_SHOP_ID];
        } elseif (isset($params[self::PARAM_CREATED_FROM], $params[self::PARAM_CREATED_TO])) {
            // get all parameters to synchronize over a fixed period
            $paramsFiltered[Lengow_Connector_Model_Import::PARAM_CREATED_FROM] = $params[self::PARAM_CREATED_FROM];
            $paramsFiltered[Lengow_Connector_Model_Import::PARAM_CREATED_TO] = $params[self::PARAM_CREATED_TO];
        } elseif (isset($params[self::PARAM_DAYS])) {
            // get all parameters to synchronize over a time interval
            $paramsFiltered[Lengow_Connector_Model_Import::PARAM_DAYS] = (int) $params[self::PARAM_DAYS];
        }
        // force order synchronization by removing pending errors
        if (isset($params[self::PARAM_FORCE])) {
            $paramsFiltered[Lengow_Connector_Model_Import::PARAM_FORCE_SYNC] = (bool) $params[self::PARAM_FORCE];
        }
        return $paramsFiltered;
    }

    /**
     * Get array of all the data of the order
     *
     * @param array $data All Lengow order data
     * @param string $type Toolbox order data type
     *
     * @return array
     */
    private function getOrderDataByType($data, $type)
    {
        $order = $data[Lengow_Connector_Model_Import_Order::FIELD_ORDER_ID]
            ? Mage::getModel('sales/order')->load((int) $data[Lengow_Connector_Model_Import_Order::FIELD_ORDER_ID])
            : null;
        $orderReferences = array(
            self::ID => (int) $data[Lengow_Connector_Model_Import_Order::FIELD_ID],
            self::ORDER_MERCHANT_ORDER_ID  => $order ? (int) $order->getId() : null,
            self::ORDER_MERCHANT_ORDER_REFERENCE  => $order ? $order->getIncrementId() : null,
            self::ORDER_DELIVERY_ADDRESS_ID =>
                (int) $data[Lengow_Connector_Model_Import_Order::FIELD_DELIVERY_ADDRESS_ID],
        );
        switch ($type) {
            case self::DATA_TYPE_ACTION:
                $orderData = array(
                    self::ACTIONS => $order ? $this->getOrderActionData((int) $order->getId()) : array(),
                );
                break;
            case self::DATA_TYPE_ERROR:
                $orderData = array(
                    self::ERRORS => $this->getOrderErrorsData(
                        (int) $data[Lengow_Connector_Model_Import_Order::FIELD_ID]
                    ),
                );
                break;
            case self::DATA_TYPE_ORDER_STATUS:
                $orderData = array(
                    self::ORDER_STATUSES => $order ? $this->getOrderStatusesData($order) : array(),
                );
                break;
            case self::DATA_TYPE_ORDER:
            default:
                $orderData = $this->getAllOrderData($data, $order);
        }
        return array_merge($orderReferences, $orderData);
    }

    /**
     * Get array of all the data of the order
     *
     * @param array $data All Lengow order data
     * @param Mage_Sales_Model_Order|null $order Magento order instance
     *
     * @return array
     */
    private function getAllOrderData(array $data, $order = null)
    {
        $importedAt = 0;
        $lastTrack = null;
        $hasActionInProgress = false;
        $orderTypes = json_decode($data[Lengow_Connector_Model_Import_Order::FIELD_ORDER_TYPES], true);
        if ($order) {
            $tracks = $order->getShipmentsCollection()->getLastItem()->getAllTracks();
            $lastTrack = !empty($tracks) ? end($tracks) : null;
            $hasActionInProgress = (bool) Mage::getModel('lengow/import_action')->getActionsByOrderId(
                (int) $order->getId(),
                true
            );
            $importedAt = strtotime($order->getStatusHistoryCollection()->getFirstItem()->getCreatedAt());
        }
        return array(
            self::ORDER_DELIVERY_COUNTRY_ISO => $data[Lengow_Connector_Model_Import_Order::FIELD_DELIVERY_COUNTRY_ISO],
            self::ORDER_PROCESS_STATE => $this->getOrderProcessLabel(
                (int) $data[Lengow_Connector_Model_Import_Order::FIELD_ORDER_PROCESS_STATE]
            ),
            self::ORDER_STATUS => $data[Lengow_Connector_Model_Import_Order::FIELD_ORDER_LENGOW_STATE],
            self::ORDER_MERCHANT_ORDER_STATUS => $order ? $order->getState() : null,
            self::ORDER_STATUSES => $order ? $this->getOrderStatusesData($order) : array(),
            self::ORDER_TOTAL_PAID => (float) $data[Lengow_Connector_Model_Import_Order::FIELD_TOTAL_PAID],
            self::ORDER_MERCHANT_TOTAL_PAID => $order ? (float) $order->getTotalPaid() : null,
            self::ORDER_COMMISSION => (float) $data[Lengow_Connector_Model_Import_Order::FIELD_COMMISSION],
            self::ORDER_CURRENCY => $data[Lengow_Connector_Model_Import_Order::FIELD_CURRENCY],
            self::CUSTOMER => array(
                self::CUSTOMER_NAME => !empty($data[Lengow_Connector_Model_Import_Order::FIELD_CUSTOMER_NAME])
                    ? $data[Lengow_Connector_Model_Import_Order::FIELD_CUSTOMER_NAME]
                    : null,
                self::CUSTOMER_EMAIL => !empty($data[Lengow_Connector_Model_Import_Order::FIELD_CUSTOMER_EMAIL])
                    ? $data[Lengow_Connector_Model_Import_Order::FIELD_CUSTOMER_EMAIL]
                    : null,
                self::CUSTOMER_VAT_NUMBER => !empty(
                    $data[Lengow_Connector_Model_Import_Order::FIELD_CUSTOMER_VAT_NUMBER]
                )
                    ? $data[Lengow_Connector_Model_Import_Order::FIELD_CUSTOMER_VAT_NUMBER]
                    : null,
            ),
            self::ORDER_DATE => strtotime($data[Lengow_Connector_Model_Import_Order::FIELD_ORDER_DATE]),
            self::ORDER_TYPES => array(
                self::ORDER_TYPE_EXPRESS => isset($orderTypes[Lengow_Connector_Model_Import_Order::TYPE_EXPRESS]),
                self::ORDER_TYPE_PRIME => isset($orderTypes[Lengow_Connector_Model_Import_Order::TYPE_PRIME]),
                self::ORDER_TYPE_BUSINESS => isset($orderTypes[Lengow_Connector_Model_Import_Order::TYPE_BUSINESS]),
                self::ORDER_TYPE_DELIVERED_BY_MARKETPLACE => isset(
                    $orderTypes[Lengow_Connector_Model_Import_Order::TYPE_DELIVERED_BY_MARKETPLACE]
                ),
            ),
            self::ORDER_ITEMS => (int) $data[Lengow_Connector_Model_Import_Order::FIELD_ORDER_ITEM],
            self::TRACKING => array(
                self::TRACKING_CARRIER => !empty($data[Lengow_Connector_Model_Import_Order::FIELD_CARRIER])
                    ? $data[Lengow_Connector_Model_Import_Order::FIELD_CARRIER]
                    : null,
                self::TRACKING_METHOD => !empty($data[Lengow_Connector_Model_Import_Order::FIELD_CARRIER_METHOD])
                    ? $data[Lengow_Connector_Model_Import_Order::FIELD_CARRIER_METHOD]
                    : null,
                self::TRACKING_NUMBER => !empty($data[Lengow_Connector_Model_Import_Order::FIELD_CARRIER_TRACKING])
                    ? $data[Lengow_Connector_Model_Import_Order::FIELD_CARRIER_TRACKING]
                    : null,
                self::TRACKING_RELAY_ID => !empty($data[Lengow_Connector_Model_Import_Order::FIELD_CARRIER_RELAY_ID])
                    ? $data[Lengow_Connector_Model_Import_Order::FIELD_CARRIER_RELAY_ID]
                    : null,
                self::TRACKING_MERCHANT_CARRIER => $lastTrack ? $lastTrack->getTitle() : null,
                self::TRACKING_MERCHANT_TRACKING_NUMBER => $lastTrack ? $lastTrack->getNumber() : null,
                self::TRACKING_MERCHANT_TRACKING_URL => null,
            ),
            self::ORDER_IS_REIMPORTED => $order
                && $order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_IS_REIMPORTED),
            self::ORDER_IS_IN_ERROR => (bool) $data[Lengow_Connector_Model_Import_Order::FIELD_IS_IN_ERROR],
            self::ERRORS => $this->getOrderErrorsData((int) $data[Lengow_Connector_Model_Import_Order::FIELD_ID]),
            self::ORDER_ACTION_IN_PROGRESS => $hasActionInProgress,
            self::ACTIONS => $order ? $this->getOrderActionData((int) $order->getId()) : array(),
            self::CREATED_AT => strtotime($data[Lengow_Connector_Model_Import_Order::FIELD_CREATED_AT]),
            self::UPDATED_AT => strtotime($data[Lengow_Connector_Model_Import_Order::FIELD_UPDATED_AT]),
            self::IMPORTED_AT => $importedAt,
        );
    }

    /**
     * Get array of all the errors of a Lengow order
     *
     * @param integer $lengowOrderId Lengow order id
     *
     * @return array
     */
    private function getOrderErrorsData($lengowOrderId)
    {
        $orderErrors = array();
        $errors = Mage::getModel('lengow/import_ordererror')->getOrderErrors($lengowOrderId);
        if ($errors) {
            foreach ($errors as $error) {
                $type = (int) $error[Lengow_Connector_Model_Import_Ordererror::FIELD_TYPE];
                $orderErrors[] = array(
                    self::ID => (int) $error[Lengow_Connector_Model_Import_Ordererror::FIELD_ID],
                    self::ERROR_TYPE => $type === Lengow_Connector_Model_Import_Ordererror::TYPE_ERROR_IMPORT
                        ? self::TYPE_ERROR_IMPORT
                        : self::TYPE_ERROR_SEND,
                    self::ERROR_MESSAGE => $this->_helper->decodeLogMessage(
                        $error[Lengow_Connector_Model_Import_Ordererror::FIELD_MESSAGE],
                        Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE
                    ),
                    self::ERROR_FINISHED => (bool) $error[Lengow_Connector_Model_Import_Ordererror::FIELD_IS_FINISHED],
                    self::ERROR_REPORTED => (bool) $error[Lengow_Connector_Model_Import_Ordererror::FIELD_MAIL],
                    self::CREATED_AT => strtotime($error[Lengow_Connector_Model_Import_Ordererror::FIELD_CREATED_AT]),
                    self::UPDATED_AT => $error[Lengow_Connector_Model_Import_Ordererror::FIELD_UPDATED_AT]
                        ? strtotime($error[Lengow_Connector_Model_Import_Ordererror::FIELD_UPDATED_AT])
                        : 0,
                );
            }
        }
        return $orderErrors;
    }

    /**
     * Get array of all the actions of a Lengow order
     *
     * @param integer $orderId Magento order id
     *
     * @return array
     */
    private function getOrderActionData($orderId)
    {
        $orderActions = array();
        $actions = Mage::getModel('lengow/import_action')->getActionsByOrderId($orderId);
        if ($actions) {
            foreach ($actions as $action) {
                $orderActions[] = array(
                    self::ID => (int) $action[Lengow_Connector_Model_Import_Action::FIELD_ID],
                    self::ACTION_ID => (int) $action[Lengow_Connector_Model_Import_Action::FIELD_ACTION_ID],
                    self::ACTION_PARAMETERS => json_decode(
                        $action[Lengow_Connector_Model_Import_Action::FIELD_PARAMETERS],
                        true
                    ),
                    self::ACTION_RETRY => (int) $action[Lengow_Connector_Model_Import_Action::FIELD_RETRY],
                    self::ACTION_FINISH => $action[Lengow_Connector_Model_Import_Action::FIELD_STATE] ===
                        Lengow_Connector_Model_Import_Action::STATE_FINISH,
                    self::CREATED_AT => strtotime($action[Lengow_Connector_Model_Import_Action::FIELD_CREATED_AT]),
                    self::UPDATED_AT => $action[Lengow_Connector_Model_Import_Action::FIELD_UPDATED_AT]
                        ? strtotime($action[Lengow_Connector_Model_Import_Action::FIELD_UPDATED_AT])
                        : 0,
                );
            }
        }
        return $orderActions;
    }

    /**
     * Get array of all the statuses of an order
     *
     * @param Mage_Sales_Model_Order $order Magento order instance
     *
     * @return array
     */
    private function getOrderStatusesData($order)
    {
        $orderStatuses = array();
        $pendingStatusHistoryCreatedAt = $order->getStatusHistoryCollection()->getFirstItem()->getCreatedAt();
        $invoiceCreatedAt = $order->getInvoiceCollection()->getFirstItem()->getCreatedAt();
        $shipmentCreatedAt = $order->getShipmentsCollection()->getFirstItem()->getCreatedAt();
        if ($pendingStatusHistoryCreatedAt) {
            $orderStatuses[] = array(
                self::ORDER_MERCHANT_ORDER_STATUS => Mage_Sales_Model_Order::STATE_NEW,
                self::ORDER_STATUS => null,
                self::CREATED_AT => strtotime($pendingStatusHistoryCreatedAt),
            );
        }
        if ($invoiceCreatedAt) {
            $orderStatuses[] = array(
                self::ORDER_MERCHANT_ORDER_STATUS => Mage_Sales_Model_Order::STATE_PROCESSING,
                self::ORDER_STATUS => Lengow_Connector_Model_Import_Order::STATE_WAITING_SHIPMENT,
                self::CREATED_AT => strtotime($invoiceCreatedAt),
            );
        }
        if ($shipmentCreatedAt) {
            $orderStatuses[] = array(
                self::ORDER_MERCHANT_ORDER_STATUS => Mage_Sales_Model_Order::STATE_COMPLETE,
                self::ORDER_STATUS => Lengow_Connector_Model_Import_Order::STATE_SHIPPED,
                self::CREATED_AT => strtotime($shipmentCreatedAt),
            );
        }
        if ($order->getState() === Mage_Sales_Model_Order::STATE_CANCELED) {
            $orderStatuses[] = array(
                self::ORDER_MERCHANT_ORDER_STATUS => Mage_Sales_Model_Order::STATE_CANCELED,
                self::ORDER_STATUS => Lengow_Connector_Model_Import_Order::STATE_CANCELED,
                self::CREATED_AT => strtotime($order->getUpdatedAt()),
            );
        }
        return $orderStatuses;
    }

    /**
     * Get all the data of the order at the time of import
     *
     * @param array $data All Lengow order data
     *
     * @return array
     */
    private function getOrderExtraData($data)
    {
        return json_decode($data[Lengow_Connector_Model_Import_Order::FIELD_EXTRA], true);
    }

    /**
     * Get order process label
     *
     * @param integer $orderProcess Lengow order process (new, import or finish)
     *
     * @return string
     */
    private function getOrderProcessLabel($orderProcess)
    {
        switch ($orderProcess) {
            case Lengow_Connector_Model_Import_Order::PROCESS_STATE_NEW:
                return self::PROCESS_STATE_NEW;
            case Lengow_Connector_Model_Import_Order::PROCESS_STATE_IMPORT:
                return self::PROCESS_STATE_IMPORT;
            case Lengow_Connector_Model_Import_Order::PROCESS_STATE_FINISH:
            default:
                return self::PROCESS_STATE_FINISH;
        }
    }

    /**
     * Generates an error return for the Toolbox webservice
     *
     * @param integer $httpCode request http code
     * @param string $error error message
     *
     * @return array
     */
    private function generateErrorReturn($httpCode, $error)
    {
        return array(
            self::ERRORS => array(
                self::ERROR_MESSAGE => $this->_helper->decodeLogMessage(
                    $error,
                    Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE
                ),
                self::ERROR_CODE => $httpCode,
            ),
        );
    }
}
