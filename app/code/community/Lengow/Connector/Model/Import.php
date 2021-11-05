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

use Magento\Store\Model\Store;

/**
 * Model import
 */
class Lengow_Connector_Model_Import extends Varien_Object
{
    /* Import GET params */
    const PARAM_TOKEN = 'token';
    const PARAM_TYPE = 'type';
    const PARAM_STORE_ID = 'store_id';
    const PARAM_MARKETPLACE_SKU = 'marketplace_sku';
    const PARAM_MARKETPLACE_NAME = 'marketplace_name';
    const PARAM_DELIVERY_ADDRESS_ID = 'delivery_address_id';
    const PARAM_DAYS = 'days';
    const PARAM_CREATED_FROM = 'created_from';
    const PARAM_CREATED_TO = 'created_to';
    const PARAM_ORDER_LENGOW_ID = 'order_lengow_id';
    const PARAM_LIMIT = 'limit';
    const PARAM_LOG_OUTPUT = 'log_output';
    const PARAM_DEBUG_MODE = 'debug_mode';
    const PARAM_FORCE = 'force';
    const PARAM_FORCE_SYNC = 'force_sync';
    const PARAM_SYNC = 'sync';
    const PARAM_GET_SYNC = 'get_sync';

    /* Import API arguments */
    const ARG_ACCOUNT_ID = 'account_id';
    const ARG_CATALOG_IDS = 'catalog_ids';
    const ARG_MARKETPLACE = 'marketplace';
    const ARG_MARKETPLACE_ORDER_DATE_FROM = 'marketplace_order_date_from';
    const ARG_MARKETPLACE_ORDER_DATE_TO = 'marketplace_order_date_to';
    const ARG_MARKETPLACE_ORDER_ID = 'marketplace_order_id';
    const ARG_MERCHANT_ORDER_ID = 'merchant_order_id';
    const ARG_NO_CURRENCY_CONVERSION = 'no_currency_conversion';
    const ARG_PAGE = 'page';
    const ARG_UPDATED_FROM = 'updated_from';
    const ARG_UPDATED_TO = 'updated_to';

    /* Import types */
    const TYPE_MANUAL = 'manual';
    const TYPE_CRON = 'cron';
    const TYPE_MAGENTO_CRON = 'magento cron';
    const TYPE_TOOLBOX = 'toolbox';

    /* Import Data */
    const NUMBER_ORDERS_PROCESSED = 'number_orders_processed';
    const NUMBER_ORDERS_CREATED = 'number_orders_created';
    const NUMBER_ORDERS_UPDATED = 'number_orders_updated';
    const NUMBER_ORDERS_FAILED = 'number_orders_failed';
    const NUMBER_ORDERS_IGNORED = 'number_orders_ignored';
    const NUMBER_ORDERS_NOT_FORMATTED = 'number_orders_not_formatted';
    const ORDERS_CREATED = 'orders_created';
    const ORDERS_UPDATED = 'orders_updated';
    const ORDERS_FAILED = 'orders_failed';
    const ORDERS_IGNORED = 'orders_ignored';
    const ORDERS_NOT_FORMATTED = 'orders_not_formatted';
    const ERRORS = 'errors';

    /**
     * @var integer max interval time for order synchronisation old versions (1 day)
     */
    const MIN_INTERVAL_TIME = 86400;

    /**
     * @var integer max import days for old versions (10 days)
     */
    const MAX_INTERVAL_TIME = 864000;

    /**
     * @var integer security interval time for cron synchronisation (2 hours)
     */
    const SECURITY_INTERVAL_TIME = 7200;

    /**
     * @var integer interval of months for cron synchronisation
     */
    const MONTH_INTERVAL_TIME = 3;

    /**
     * @var Lengow_Connector_Helper_Data|null Lengow helper instance
     */
    protected $_helper;

    /**
     * @var Lengow_Connector_Helper_Import|null Lengow import helper instance
     */
    protected $_importHelper;

    /**
     * @var Lengow_Connector_Helper_Config|null Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var Lengow_Connector_Model_Connector Lengow connector
     */
    protected $_connector;

    /**
     * @var integer|null Magento store id
     */
    protected $_storeId;

    /**
     * @var integer|null Lengow order id
     */
    protected $_orderLengowId;

    /**
     * @var string|null marketplace order sku
     */
    protected $_marketplaceSku;

    /**
     * @var string|null marketplace name
     */
    protected $_marketplaceName;

    /**
     * @var integer|null delivery address id
     */
    protected $_deliveryAddressId;

    /**
     * @var integer maximum number of new orders created
     */
    protected $_limit = 0;

    /**
     * @var boolean force import order even if there are errors
     */
    protected $_forceSync;

    /**
     * @var string import type (manual, cron or magento cron)
     */
    protected $_typeImport;

    /**
     * @var boolean import one order
     */
    protected $_importOneOrder = false;

    /**
     * @var boolean use debug mode
     */
    protected $_debugMode = false;

    /**
     * @var boolean display log messages
     */
    protected $_logOutput = false;

    /**
     * @var integer|false imports orders updated since (timestamp)
     */
    protected $_updatedFrom = false;

    /**
     * @var integer|false imports orders updated until (timestamp)
     */
    protected $_updatedTo = false;

    /**
     * @var integer|false imports orders created since (timestamp)
     */
    protected $_createdFrom = false;

    /**
     * @var integer|false imports orders created until (timestamp)
     */
    protected $_createdTo = false;

    /**
     * @var string account ID
     */
    protected $_accountId;

    /**
     * @var string access token
     */
    protected $_accessToken;

    /**
     * @var string secret token
     */
    protected $_secretToken;

    /**
     * @var array store catalog ids for import
     */
    protected $_storeCatalogIds = array();

    /**
     * @var array catalog ids already imported
     */
    protected $_catalogIds = array();

    /**
     * @var array all orders created during the process
     */
    protected $_ordersCreated = [];

    /**
     * @var array all orders updated during the process
     */
    protected $_ordersUpdated = [];

    /**
     * @var array all orders failed during the process
     */
    protected $_ordersFailed = [];

    /**
     * @var array all orders ignored during the process
     */
    protected $_ordersIgnored = [];

    /**
     * @var array all incorrectly formatted orders that cannot be processed
     */
    protected $_ordersNotFormatted = [];

    /**
     * @var array all synchronization error (global or by shop)
     */
    protected $_errors = [];

    /**
     * Construct the import manager
     *
     * @param array params optional options
     * string  marketplace_sku     Lengow marketplace order id to synchronize
     * string  marketplace_name    Lengow marketplace name to synchronize
     * string  type                Type of current synchronization
     * string  created_from        Synchronization of orders since
     * string  created_to          Synchronization of orders until
     * integer delivery_address_id Lengow delivery address id to synchronize
     * integer order_lengow_id     Lengow order id in Magento
     * integer store_id            Store id for current synchronize
     * integer days                Synchronization interval time
     * integer limit               Maximum number of new orders created
     * boolean log_output          See logs (true) or not (false)
     * boolean debug_mode          Activate debug mode
     */
    public function __construct($params = array())
    {
        $this->_helper = Mage::helper('lengow_connector/data');
        $this->_importHelper = Mage::helper('lengow_connector/import');
        $this->_configHelper = Mage::helper('lengow_connector/config');
        // get generic params for synchronisation
        $this->_debugMode = isset($params[self::PARAM_DEBUG_MODE])
            ? (bool) $params[self::PARAM_DEBUG_MODE]
            : $this->_configHelper->debugModeIsActive();
        $this->_typeImport = isset($params[self::PARAM_TYPE]) ? $params[self::PARAM_TYPE] : self::TYPE_MANUAL;
        $this->_forceSync = isset($params[self::PARAM_FORCE_SYNC]) && $params[self::PARAM_FORCE_SYNC];
        $this->_logOutput = isset($params[self::PARAM_LOG_OUTPUT]) && $params[self::PARAM_LOG_OUTPUT];
        $this->_storeId = isset($params[self::PARAM_STORE_ID]) ? (int) $params[self::PARAM_STORE_ID] : null;
        // get params for synchronise one or all orders
        if (array_key_exists(self::PARAM_MARKETPLACE_SKU, $params)
            && array_key_exists(self::PARAM_MARKETPLACE_NAME, $params)
            && array_key_exists(self::PARAM_STORE_ID, $params)
        ) {
            if (isset($params[self::PARAM_ORDER_LENGOW_ID])) {
                $this->_orderLengowId = (int) $params[self::PARAM_ORDER_LENGOW_ID];
                $this->_forceSync = true;
            }
            $this->_marketplaceSku = (string) $params[self::PARAM_MARKETPLACE_SKU];
            $this->_marketplaceName = (string) $params[self::PARAM_MARKETPLACE_NAME];
            $this->_importOneOrder = true;
            $this->_limit = 1;
            if (array_key_exists(self::PARAM_DELIVERY_ADDRESS_ID, $params)
                && $params[self::PARAM_DELIVERY_ADDRESS_ID] !== ''
            ) {
                $this->_deliveryAddressId = (int) $params[self::PARAM_DELIVERY_ADDRESS_ID];
            }
        } else {
            // set the time interval
            $this->_setIntervalTime(
                isset($params[self::PARAM_DAYS]) ? (int) $params[self::PARAM_DAYS] : null,
                isset($params[self::PARAM_CREATED_FROM]) ? $params[self::PARAM_CREATED_FROM] : null,
                isset($params[self::PARAM_CREATED_TO]) ? $params[self::PARAM_CREATED_TO] : null
            );
            $this->_limit = isset($params[self::PARAM_LIMIT]) ? (int) $params[self::PARAM_LIMIT] : 0;
        }
    }

    /**
     * Execute import: fetch orders and import them
     *
     * @return array
     */
    public function exec()
    {
        $syncOk = true;
        // checks if a synchronization is not already in progress
        if (!$this->_canExecuteSynchronization()) {
            return $this->_getResult();
        }
        // starts some processes necessary for synchronization
        $this->_setupSynchronization();
        // get all active store in Lengow for order synchronization
        $activeStore = $this->_configHelper->getLengowActiveStores($this->_storeId);
        foreach ($activeStore as $store) {
            // synchronize all orders for a specific store
            if (!$this->_synchronizeOrdersByStore($store)) {
                $syncOk = false;
            }
        }
        // get order synchronization result
        $result = $this->_getResult();
        $this->_helper->log(
            Lengow_Connector_Helper_Data::CODE_IMPORT,
            $this->_helper->setLogMessage(
                'log.import.sync_result',
                array(
                    'number_orders_processed' => $result[self::NUMBER_ORDERS_PROCESSED],
                    'number_orders_created' => $result[self::NUMBER_ORDERS_CREATED],
                    'number_orders_updated' => $result[self::NUMBER_ORDERS_UPDATED],
                    'number_orders_failed' => $result[self::NUMBER_ORDERS_FAILED],
                    'number_orders_ignored' => $result[self::NUMBER_ORDERS_IGNORED],
                    'number_orders_not_formatted' => $result[self::NUMBER_ORDERS_NOT_FORMATTED],
                )
            ),
            $this->_logOutput
        );
        // update last synchronization date only if importation succeeded
        if (!$this->_importOneOrder && $syncOk) {
            $this->_importHelper->updateDateImport($this->_typeImport);
        }
        // complete synchronization and start all necessary processes
        $this->_finishSynchronization();
        return $result;
    }

    /**
     * Set interval time for order synchronisation
     *
     * @param integer|null $days Import period
     * @param string|null $createdFrom Import of orders since
     * @param string|null $createdTo Import of orders until
     */
    private function _setIntervalTime($days = null, $createdFrom = null, $createdTo = null)
    {
        if ($createdFrom && $createdTo) {
            // retrieval of orders created from ... until ...
            $createdFromTimestamp = Mage::getModel('core/date')->gmtTimestamp($createdFrom);
            $createdToTimestamp = Mage::getModel('core/date')->gmtTimestamp($createdTo) + 86399;
            $intervalTime = (int) ($createdToTimestamp - $createdFromTimestamp);
            $this->_createdFrom = $createdFromTimestamp;
            $this->_createdTo = $intervalTime > self::MAX_INTERVAL_TIME
                ? $createdFromTimestamp + self::MAX_INTERVAL_TIME
                : $createdToTimestamp;
            return;
        }
        if ($days) {
            $intervalTime = $days * 86400;
            $intervalTime = $intervalTime > self::MAX_INTERVAL_TIME ? self::MAX_INTERVAL_TIME : $intervalTime;
        } else {
            // order recovery updated since ... days
            $importDays = (int) $this->_configHelper->get(
                Lengow_Connector_Helper_Config::SYNCHRONIZATION_DAY_INTERVAL
            );
            $intervalTime = $importDays * 86400;
            // add security for older versions of the plugin
            $intervalTime = $intervalTime < self::MIN_INTERVAL_TIME ? self::MIN_INTERVAL_TIME : $intervalTime;
            $intervalTime = $intervalTime > self::MAX_INTERVAL_TIME ? self::MAX_INTERVAL_TIME : $intervalTime;
            // get dynamic interval time for cron synchronisation
            $lastImport = $this->_importHelper->getLastImport();
            $lastSettingUpdate = (int) $this->_configHelper->get(
                Lengow_Connector_Helper_Config::LAST_UPDATE_SETTING
            );
            if (($this->_typeImport === self::TYPE_CRON || $this->_typeImport === self::TYPE_MAGENTO_CRON)
                && $lastImport['timestamp'] !== 'none'
                && $lastImport['timestamp'] > $lastSettingUpdate
            ) {
                $lastIntervalTime = (time() - $lastImport['timestamp']) + self::SECURITY_INTERVAL_TIME;
                $intervalTime = $lastIntervalTime > $intervalTime ? $intervalTime : $lastIntervalTime;
            }
        }
        $this->_updatedFrom = time() - $intervalTime;
        $this->_updatedTo = time();
    }

    /**
     * Checks if a synchronization is not already in progress
     *
     * @return boolean
     */
    private function _canExecuteSynchronization()
    {
        $globalError = false;
        // checks if the process can start
        if (!$this->_debugMode && !$this->_importOneOrder && $this->_importHelper->importIsInProcess()) {
            $globalError = $this->_helper->setLogMessage(
                'lengow_log.error.rest_time_to_import',
                array('rest_time' => $this->_importHelper->restTimeToImport())
            );
            $this->_helper->log(Lengow_Connector_Helper_Data::CODE_IMPORT, $globalError, $this->_logOutput);
        } elseif (!$this->_checkCredentials()) {
            $globalError = $this->_helper->setLogMessage('lengow_log.error.credentials_not_valid');
            $this->_helper->log(Lengow_Connector_Helper_Data::CODE_IMPORT, $globalError, $this->_logOutput);
        }
        // if we have a global error, we stop the process directly
        if ($globalError) {
            $this->_errors[0] = $globalError;
            if (isset($this->_orderLengowId) && $this->_orderLengowId) {
                /** @var Lengow_Connector_Model_Import_Ordererror $lengowOrderError */
                $lengowOrderError = Mage::getModel('lengow/import_ordererror');
                $lengowOrderError->finishOrderErrors($this->_orderLengowId);
                $lengowOrderError->createOrderError(
                    array(
                        Lengow_Connector_Model_Import_Ordererror::FIELD_ORDER_LENGOW_ID => $this->_orderLengowId,
                        Lengow_Connector_Model_Import_Ordererror::FIELD_MESSAGE => $globalError,
                        Lengow_Connector_Model_Import_Ordererror::FIELD_TYPE =>
                            Lengow_Connector_Model_Import_Ordererror::TYPE_ERROR_IMPORT,
                    )
                );
            }
            return false;
        }
        return true;
    }

    /**
     * Starts some necessary processes for synchronization
     */
    private function _setupSynchronization()
    {
        // suppress log files when too old
        $this->_helper->cleanLog();
        if (!$this->_importOneOrder) {
            $this->_importHelper->setImportInProcess();
        }
        // to activate lengow shipping method
        Mage::getSingleton('core/session')->setIsFromlengow(1);
        // check Lengow catalogs for order synchronisation
        if (!$this->_importOneOrder && $this->_typeImport === self::TYPE_MANUAL) {
            Mage::helper('lengow_connector/sync')->syncCatalog();
        }
        $this->_helper->log(
            Lengow_Connector_Helper_Data::CODE_IMPORT,
            $this->_helper->setLogMessage('log.import.start', array('type' => $this->_typeImport)),
            $this->_logOutput
        );
        if ($this->_debugMode) {
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_IMPORT,
                $this->_helper->setLogMessage('log.import.debug_mode_active'),
                $this->_logOutput
            );
        }
    }

    /**
     * Check credentials and get Lengow connector
     *
     * @return boolean
     */
    private function _checkCredentials()
    {
        /** @var Lengow_Connector_Model_Connector $connector */
        $connector = Mage::getModel('lengow/connector');
        if ($connector->isValidAuth($this->_logOutput)) {
            list($this->_accountId, $this->_accessToken, $this->_secretToken) = $this->_configHelper->getAccessIds();
            $this->_connector = $connector;
            $this->_connector->init($this->_accessToken, $this->_secretToken);
            return true;
        }
        return false;
    }

    /**
     * Return the synchronization result
     *
     * @return array
     */
    private function _getResult()
    {
        $nbOrdersCreated = count($this->_ordersCreated);
        $nbOrdersUpdated = count($this->_ordersUpdated);
        $nbOrdersFailed = count($this->_ordersFailed);
        $nbOrdersIgnored = count($this->_ordersIgnored);
        $nbOrdersNotFormatted = count($this->_ordersNotFormatted);
        $nbOrdersProcessed = $nbOrdersCreated
            + $nbOrdersUpdated
            + $nbOrdersFailed
            + $nbOrdersIgnored
            + $nbOrdersNotFormatted;
        return array(
            self::NUMBER_ORDERS_PROCESSED => $nbOrdersProcessed,
            self::NUMBER_ORDERS_CREATED => $nbOrdersCreated,
            self::NUMBER_ORDERS_UPDATED => $nbOrdersUpdated,
            self::NUMBER_ORDERS_FAILED => $nbOrdersFailed,
            self::NUMBER_ORDERS_IGNORED => $nbOrdersIgnored,
            self::NUMBER_ORDERS_NOT_FORMATTED => $nbOrdersNotFormatted,
            self::ORDERS_CREATED => $this->_ordersCreated,
            self::ORDERS_UPDATED => $this->_ordersUpdated,
            self::ORDERS_FAILED => $this->_ordersFailed,
            self::ORDERS_IGNORED => $this->_ordersIgnored,
            self::ORDERS_NOT_FORMATTED => $this->_ordersNotFormatted,
            self::ERRORS => $this->_errors,
        );
    }

    /**
     * Synchronize all orders for a specific store
     *
     * @param Mage_Core_Model_Store $store Magento store instance
     *
     * @return boolean
     */
    private function _synchronizeOrdersByStore($store)
    {
        $this->_helper->log(
            Lengow_Connector_Helper_Data::CODE_IMPORT,
            $this->_helper->setLogMessage(
                'log.import.start_for_store',
                array(
                    'store_name' => $store->getName(),
                    'store_id' => (int) $store->getId(),
                )
            ),
            $this->_logOutput
        );
        // check shop catalog ids
        if (!$this->_checkCatalogIds($store)) {
            return true;
        }
        try {
            // get orders from Lengow API
            $orders = $this->_getOrdersFromApi($store);
            $numberOrdersFound = count($orders);
            if ($this->_importOneOrder) {
                $this->_helper->log(
                    Lengow_Connector_Helper_Data::CODE_IMPORT,
                    $this->_helper->setLogMessage(
                        'log.import.find_one_order',
                        array(
                            'nb_order' => $numberOrdersFound,
                            'marketplace_sku' => $this->_marketplaceSku,
                            'marketplace_name' => $this->_marketplaceName,
                            'account_id' => $this->_accountId,
                        )
                    ),
                    $this->_logOutput
                );
            } else {
                $this->_helper->log(
                    Lengow_Connector_Helper_Data::CODE_IMPORT,
                    $this->_helper->setLogMessage(
                        'log.import.find_all_orders',
                        array(
                            'nb_order' => $numberOrdersFound,
                            'account_id' => $this->_accountId,
                        )
                    ),
                    $this->_logOutput
                );
            }
            if ($numberOrdersFound <= 0 && $this->_importOneOrder) {
                throw new Lengow_Connector_Model_Exception('lengow_log.error.order_not_found');
            }
            if ($numberOrdersFound > 0) {
                // import orders in Magento
                $this->_importOrders($orders, (int)$store->getId());
            }
        } catch (Lengow_Connector_Model_Exception $e) {
            $errorMessage = $e->getMessage();
        } catch (Exception $e) {
            $errorMessage = '[Magento error]: "' . $e->getMessage()
                . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
        }
        if (isset($errorMessage)) {
            if (isset($this->_orderLengowId) && $this->_orderLengowId) {
                /** @var Lengow_Connector_Model_Import_Ordererror $lengowOrderError */
                $lengowOrderError = Mage::getModel('lengow/import_ordererror');
                $lengowOrderError->finishOrderErrors($this->_orderLengowId);
                $lengowOrderError->createOrderError(
                    array(
                        Lengow_Connector_Model_Import_Ordererror::FIELD_ORDER_LENGOW_ID => $this->_orderLengowId,
                        Lengow_Connector_Model_Import_Ordererror::FIELD_MESSAGE => $errorMessage,
                        Lengow_Connector_Model_Import_Ordererror::FIELD_TYPE =>
                            Lengow_Connector_Model_Import_Ordererror::TYPE_ERROR_IMPORT,
                    )
                );
                unset($lengowOrderError);
            }
            $decodedMessage = $this->_helper->decodeLogMessage(
                $errorMessage,
                Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE
            );
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_IMPORT,
                $this->_helper->setLogMessage(
                    'log.import.import_failed',
                    array('decoded_message' => $decodedMessage)
                ),
                $this->_logOutput
            );
            $this->_errors[(int) $store->getId()] = $errorMessage;
            return false;
        }
        return true;
    }

    /**
     * Check catalog ids for a store
     *
     * @param Mage_Core_Model_Store $store Magento store instance
     *
     * @return boolean
     */
    private function _checkCatalogIds($store)
    {
        if ($this->_importOneOrder) {
            return true;
        }
        $storeCatalogIds = array();
        $catalogIds = $this->_configHelper->getCatalogIds((int) $store->getId());
        foreach ($catalogIds as $catalogId) {
            if (array_key_exists($catalogId, $this->_catalogIds)) {
                $this->_helper->log(
                    Lengow_Connector_Helper_Data::CODE_IMPORT,
                    $this->_helper->setLogMessage(
                        'log.import.catalog_id_already_used',
                        array(
                            'catalog_id' => $catalogId,
                            'store_name' => $this->_catalogIds[$catalogId]['name'],
                            'store_id' => $this->_catalogIds[$catalogId]['store_id'],
                        )
                    ),
                    $this->_logOutput
                );
            } else {
                $this->_catalogIds[$catalogId] = array(
                    'store_id' => (int) $store->getId(),
                    'name' => $store->getName(),
                );
                $storeCatalogIds[] = $catalogId;
            }
        }
        if (!empty($storeCatalogIds)) {
            $this->_storeCatalogIds = $storeCatalogIds;
            return true;
        }
        $message = $this->_helper->setLogMessage(
            'lengow_log.error.no_catalog_for_store',
            array(
                'store_name' => $store->getName(),
                'store_id' => (int) $store->getId(),
            )
        );
        $this->_helper->log(Lengow_Connector_Helper_Data::CODE_IMPORT, $message, $this->_logOutput);
        $this->_errors[(int) $store->getId()] = $message;
        return false;
    }

    /**
     * Call Lengow order API
     *
     * @param Mage_Core_Model_Store $store Magento store instance
     *
     * @throws Lengow_Connector_Model_Exception no connection with webservices / error with lengow webservices
     *
     * @return array
     */
    private function _getOrdersFromApi($store)
    {
        $page = 1;
        $orders = array();
        $coreDate = Mage::getModel('core/date');
        // convert order amount or not
        $noCurrencyConversion = !(bool) $this->_configHelper->get(
            Lengow_Connector_Helper_Config::CURRENCY_CONVERSION_ENABLED,
            $store->getId()
        );
        if ($this->_importOneOrder) {
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_IMPORT,
                $this->_helper->setLogMessage(
                    'log.import.connector_get_order',
                    array(
                        'marketplace_sku' => $this->_marketplaceSku,
                        'marketplace_name' => $this->_marketplaceName,
                    )
                ),
                $this->_logOutput
            );
        } else {
            $dateFrom = $this->_createdFrom ?: $this->_updatedFrom;
            $dateTo = $this->_createdTo ?: $this->_updatedTo;
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_IMPORT,
                $this->_helper->setLogMessage(
                    'log.import.connector_get_all_order',
                    array(
                        'date_from' => $coreDate->date(Lengow_Connector_Helper_Data::DATE_FULL, $dateFrom),
                        'date_to' => $coreDate->date(Lengow_Connector_Helper_Data::DATE_FULL, $dateTo),
                        'catalog_id' => implode(', ', $this->_storeCatalogIds),
                    )
                ),
                $this->_logOutput
            );
        }
        do {
            try {
                if ($this->_importOneOrder) {
                    $results = $this->_connector->get(
                        Lengow_Connector_Model_Connector::API_ORDER,
                        array(
                            self::ARG_MARKETPLACE_ORDER_ID => $this->_marketplaceSku,
                            self::ARG_MARKETPLACE => $this->_marketplaceName,
                            self::ARG_NO_CURRENCY_CONVERSION => $noCurrencyConversion,
                            self::ARG_ACCOUNT_ID => $this->_accountId,
                            self::ARG_PAGE => $page,
                        ),
                        Lengow_Connector_Model_Connector::FORMAT_STREAM,
                        '',
                        $this->_logOutput
                    );
                } else {
                    if ($this->_createdFrom && $this->_createdTo) {
                        $timeParams = array(
                            self::ARG_MARKETPLACE_ORDER_DATE_FROM => $coreDate->date(
                                Lengow_Connector_Helper_Data::DATE_ISO_8601,
                                $this->_createdFrom
                            ),
                            self::ARG_MARKETPLACE_ORDER_DATE_TO => $coreDate->date(
                                Lengow_Connector_Helper_Data::DATE_ISO_8601,
                                $this->_createdTo
                            ),
                        );
                    } else {
                        $timeParams = array(
                            self::ARG_UPDATED_FROM => Mage::app()->getLocale()
                                ->date($this->_updatedFrom)
                                ->toString(Lengow_Connector_Helper_Data::DATE_ISO_8601),
                            self::ARG_UPDATED_TO => Mage::app()->getLocale()
                                ->date($this->_updatedTo)
                                ->toString(Lengow_Connector_Helper_Data::DATE_ISO_8601),
                        );
                    }
                    $results = $this->_connector->get(
                        Lengow_Connector_Model_Connector::API_ORDER,
                        array_merge(
                            $timeParams,
                            array(
                                self::ARG_CATALOG_IDS => implode(',', $this->_storeCatalogIds),
                                self::ARG_NO_CURRENCY_CONVERSION => $noCurrencyConversion,
                                self::ARG_ACCOUNT_ID => $this->_accountId,
                                self::ARG_PAGE => $page,
                            )
                        ),
                        Lengow_Connector_Model_Connector::FORMAT_STREAM,
                        '',
                        $this->_logOutput
                    );
                }
            } catch (Exception $e) {
                throw new Lengow_Connector_Model_Exception(
                    $this->_helper->setLogMessage(
                        'lengow_log.exception.error_lengow_webservice',
                        array(
                            'error_code' => $e->getCode(),
                            'error_message' => $this->_helper->decodeLogMessage(
                                $e->getMessage(),
                                Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE
                            ),
                            'store_name' => $store->getName(),
                            'store_id' => $store->getId(),
                        )
                    )
                );
            }
            if ($results === null) {
                throw new Lengow_Connector_Model_Exception(
                    $this->_helper->setLogMessage(
                        'lengow_log.exception.no_connection_webservice',
                        array(
                            'store_name' => $store->getName(),
                            'store_id' => $store->getId(),
                        )
                    )
                );
            }
            $results = json_decode($results);
            if (!is_object($results)) {
                throw new Lengow_Connector_Model_Exception(
                    $this->_helper->setLogMessage(
                        'lengow_log.exception.no_connection_webservice',
                        array(
                            'store_name' => $store->getName(),
                            'store_id' => $store->getId(),
                        )
                    )
                );
            }
            // construct array orders
            foreach ($results->results as $order) {
                $orders[] = $order;
            }
            $page++;
            $finish = $results->next === null || $this->_importOneOrder;
        } while ($finish !== true);
        return $orders;
    }

    /**
     * Create or update order in Magento
     *
     * @param mixed $orders API orders
     * @param integer $storeId Magento store Id
     */
    private function _importOrders($orders, $storeId)
    {
        $importFinished = false;
        foreach ($orders as $orderData) {
            if (!$this->_importOneOrder) {
                $this->_importHelper->setImportInProcess();
            }
            $nbPackage = 0;
            $marketplaceSku = (string) $orderData->marketplace_order_id;
            if ($this->_debugMode) {
                $marketplaceSku .= '--' . time();
            }
            // set current order to cancel hook updateOrderStatus
            Mage::getSingleton('core/session')->setCurrentOrderLengow($marketplaceSku);
            // if order contains no package
            if (empty($orderData->packages)) {
                $message = $this->_helper->setLogMessage('log.import.error_no_package');
                $this->_helper->log(
                    Lengow_Connector_Helper_Data::CODE_IMPORT,
                    $message,
                    $this->_logOutput,
                    $marketplaceSku
                );
                $this->_addOrderNotFormatted($marketplaceSku, $message, $orderData);
                continue;
            }
            // start import
            foreach ($orderData->packages as $packageData) {
                $nbPackage++;
                // check whether the package contains a shipping address
                if (!isset($packageData->delivery->id)) {
                    $message = $this->_helper->setLogMessage('log.import.error_no_delivery_address');
                    $this->_helper->log(
                        Lengow_Connector_Helper_Data::CODE_IMPORT,
                        $message,
                        $this->_logOutput,
                        $marketplaceSku
                    );
                    $this->_addOrderNotFormatted($marketplaceSku, $message, $orderData);
                    continue;
                }
                $packageDeliveryAddressId = (int) $packageData->delivery->id;
                $firstPackage = !($nbPackage > 1);
                // check the package for re-import order
                if ($this->_importOneOrder
                    && $this->_deliveryAddressId !== null
                    && $this->_deliveryAddressId !== $packageDeliveryAddressId
                ) {
                    $message = $this->_helper->setLogMessage('log.import.error_wrong_package_number');
                    $this->_helper->log(
                        Lengow_Connector_Helper_Data::CODE_IMPORT,
                        $message,
                        $this->_logOutput,
                        $marketplaceSku
                    );
                    $this->_addOrderNotFormatted($marketplaceSku, $message, $orderData);
                    continue;
                }
                try {
                    // try to import or update order
                    /** @var Lengow_Connector_Model_Import_Importorder $importOrder */
                    $importOrder = Mage::getModel(
                        'lengow/import_importorder',
                        array(
                            Lengow_Connector_Model_Import_Importorder::PARAM_STORE_ID => $storeId,
                            Lengow_Connector_Model_Import_Importorder::PARAM_FORCE_SYNC => $this->_forceSync,
                            Lengow_Connector_Model_Import_Importorder::PARAM_DEBUG_MODE => $this->_debugMode,
                            Lengow_Connector_Model_Import_Importorder::PARAM_LOG_OUTPUT => $this->_logOutput,
                            Lengow_Connector_Model_Import_Importorder::PARAM_MARKETPLACE_SKU => $marketplaceSku,
                            Lengow_Connector_Model_Import_Importorder::PARAM_DELIVERY_ADDRESS_ID =>
                                $packageDeliveryAddressId,
                            Lengow_Connector_Model_Import_Importorder::PARAM_ORDER_DATA => $orderData,
                            Lengow_Connector_Model_Import_Importorder::PARAM_PACKAGE_DATA => $packageData,
                            Lengow_Connector_Model_Import_Importorder::PARAM_FIRST_PACKAGE => $firstPackage,
                            Lengow_Connector_Model_Import_Importorder::PARAM_IMPORT_ONE_ORDER => $this->_importOneOrder,
                            Lengow_Connector_Model_Import_Importorder::PARAM_IMPORT_HELPER => $this->_importHelper,
                        )
                    );
                    $result = $importOrder->importOrder();
                    // synchronize the merchant order id with Lengow
                    $this->_synchronizeMerchantOrderId($result);
                    // save the result of the order synchronization by type
                    $this->_saveSynchronizationResult($result);
                    // clean import order process
                    unset($importOrder, $result);
                } catch (Exception $e) {
                    $errorMessage = '[Magento error]: "' . $e->getMessage()
                        . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
                }
                if (isset($errorMessage)) {
                    $decodedMessage = $this->_helper->decodeLogMessage(
                        $errorMessage,
                        Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE
                    );
                    $this->_helper->log(
                        Lengow_Connector_Helper_Data::CODE_IMPORT,
                        $this->_helper->setLogMessage(
                            'log.import.order_import_failed',
                            array('decoded_message' => $decodedMessage)
                        ),
                        $this->_logOutput,
                        $marketplaceSku
                    );
                    unset($errorMessage);
                    continue;
                }
                // if limit is set
                if ($this->_limit > 0 && count($this->_ordersCreated) === $this->_limit) {
                    $importFinished = true;
                    break;
                }
            }
            // reset backend session b2b attribute
            Mage::getSingleton('core/session')->setIsLengowB2b(0);
            // clean current order in session
            Mage::getSingleton('core/session')->setCurrentOrderLengow(false);
            if ($importFinished) {
                break;
            }
        }
    }

    /**
     * Return an array of result for order not formatted
     *
     * @param string $marketplaceSku id lengow of current order
     * @param string $errorMessage Error message
     * @param mixed $orderData API order data
     */
    private function _addOrderNotFormatted($marketplaceSku, $errorMessage, $orderData)
    {
        $messageDecoded = $this->_helper->decodeLogMessage(
            $errorMessage,
            Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE
        );
        $this->_ordersNotFormatted[] = [
            Lengow_Connector_Model_Import_Importorder::MERCHANT_ORDER_ID => null,
            Lengow_Connector_Model_Import_Importorder::MERCHANT_ORDER_REFERENCE => null,
            Lengow_Connector_Model_Import_Importorder::LENGOW_ORDER_ID => $this->_orderLengowId,
            Lengow_Connector_Model_Import_Importorder::MARKETPLACE_SKU => $marketplaceSku,
            Lengow_Connector_Model_Import_Importorder::MARKETPLACE_NAME => (string) $orderData->marketplace,
            Lengow_Connector_Model_Import_Importorder::DELIVERY_ADDRESS_ID => null,
            Lengow_Connector_Model_Import_Importorder::SHOP_ID => $this->_storeId,
            Lengow_Connector_Model_Import_Importorder::CURRENT_ORDER_STATUS => (string) $orderData->lengow_status,
            Lengow_Connector_Model_Import_Importorder::PREVIOUS_ORDER_STATUS => (string) $orderData->lengow_status,
            Lengow_Connector_Model_Import_Importorder::ERRORS => [$messageDecoded],
        ];
    }

    /**
     * Synchronize the merchant order id with Lengow
     *
     * @param array $result synchronization order result
     */
    private function _synchronizeMerchantOrderId($result)
    {
        $resultType = $result[Lengow_Connector_Model_Import_Importorder::RESULT_TYPE];
        if (!$this->_debugMode && $resultType === Lengow_Connector_Model_Import_Importorder::RESULT_CREATED) {
            $orderId = $result[Lengow_Connector_Model_Import_Importorder::MERCHANT_ORDER_ID];
            $order = Mage::getModel('sales/order')->load($orderId);
            $success = Mage::getModel('lengow/import_order')->synchronizeOrder(
                $order,
                $this->_connector,
                $this->_logOutput
            );
            $messageKey = $success
                ? 'log.import.order_synchronized_with_lengow'
                : 'log.import.order_not_synchronized_with_lengow';
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_IMPORT,
                $this->_helper->setLogMessage($messageKey, array('order_id' => $orderId)),
                $this->_logOutput,
                $result[Lengow_Connector_Model_Import_Importorder::MARKETPLACE_SKU]
            );
        }
    }

    /**
     * Save the result of the order synchronization by type
     *
     * @param array $result synchronization order result
     */
    private function _saveSynchronizationResult($result)
    {
        $resultType = $result[Lengow_Connector_Model_Import_Importorder::RESULT_TYPE];
        unset($result[Lengow_Connector_Model_Import_Importorder::RESULT_TYPE]);
        switch ($resultType) {
            case Lengow_Connector_Model_Import_Importorder::RESULT_CREATED:
                $this->_ordersCreated[] = $result;
                break;
            case Lengow_Connector_Model_Import_Importorder::RESULT_UPDATED:
                $this->_ordersUpdated[] = $result;
                break;
            case Lengow_Connector_Model_Import_Importorder::RESULT_FAILED:
                $this->_ordersFailed[] = $result;
                break;
            case Lengow_Connector_Model_Import_Importorder::RESULT_IGNORED:
                $this->_ordersIgnored[] = $result;
                break;
        }
    }

    /**
     * Complete synchronization and start all necessary processes
     */
    private function _finishSynchronization()
    {
        // finish import process
        $this->_importHelper->setImportEnd();
        $this->_helper->log(
            Lengow_Connector_Helper_Data::CODE_IMPORT,
            $this->_helper->setLogMessage('log.import.end', array('type' => $this->_typeImport)),
            $this->_logOutput
        );
        // check if order action is finish (ship or cancel)
        if (!$this->_debugMode && !$this->_importOneOrder && $this->_typeImport === self::TYPE_MANUAL) {
            /** @var Lengow_Connector_Model_Import_Action $action */
            $action = Mage::getModel('lengow/import_action');
            $action->checkFinishAction($this->_logOutput);
            $action->checkOldAction($this->_logOutput);
            $action->checkActionNotSent($this->_logOutput);
        }
        // sending email in error for orders
        if (!$this->_debugMode
            && !$this->_importOneOrder
            && $this->_configHelper->get(Lengow_Connector_Helper_Config::REPORT_MAIL_ENABLED)
        ) {
            $this->_importHelper->sendMailAlert($this->_logOutput);
        }
        // clear session
        Mage::getSingleton('core/session')->setIsFromlengow(0);
    }
}
