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

/**
 * Model import
 */
class Lengow_Connector_Model_Import extends Varien_Object
{
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
     * @var string manual import type
     */
    const TYPE_MANUAL = 'manual';

    /**
     * @var string cron import type
     */
    const TYPE_CRON = 'cron';

    /**
     * @var string Magento cron import type
     */
    const TYPE_MAGENTO_CRON = 'magento cron';

    /**
     * @var Lengow_Connector_Helper_Data|null Lengow helper instance
     */
    protected $_helper = null;

    /**
     * @var Lengow_Connector_Helper_Import|null Lengow import helper instance
     */
    protected $_importHelper = null;

    /**
     * @var Lengow_Connector_Helper_Config|null Lengow config helper instance
     */
    protected $_configHelper = null;

    /**
     * @var integer|null Magento store id
     */
    protected $_storeId = null;

    /**
     * @var integer|null Lengow order id
     */
    protected $_orderLengowId = null;

    /**
     * @var string|null marketplace order sku
     */
    protected $_marketplaceSku = null;

    /**
     * @var string|null marketplace name
     */
    protected $_marketplaceName = null;

    /**
     * @var integer|null delivery address id
     */
    protected $_deliveryAddressId = null;

    /**
     * @var integer number of orders to import
     */
    protected $_limit = 0;

    /**
     * @var string import type (manual, cron or magento cron)
     */
    protected $_typeImport;

    /**
     * @var boolean import one order
     */
    protected $_importOneOrder = false;

    /**
     * @var boolean use preprod mode
     */
    protected $_preprodMode = false;

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
     * @var Lengow_Connector_Model_Connector Lengow connector
     */
    protected $_connector;

    /**
     * Construct the import manager
     *
     * @param array params optional options
     * string  marketplace_sku     lengow marketplace order id to import
     * string  marketplace_name    lengow marketplace name to import
     * string  type                type of current import
     * string  created_from        import of orders since
     * string  created_to          import of orders until
     * integer delivery_address_id Lengow delivery address id to import
     * integer order_lengow_id     Lengow order id in Magento
     * integer store_id            store id for current import
     * integer days                import period
     * integer limit               number of orders to import
     * boolean log_output          display log messages
     * boolean preprod_mode        preprod mode
     */
    public function __construct($params = array())
    {
        $this->_helper = Mage::helper('lengow_connector/data');
        $this->_importHelper = Mage::helper('lengow_connector/import');
        $this->_configHelper = Mage::helper('lengow_connector/config');
        // get generic params for synchronisation
        $this->_preprodMode = isset($params['preprod_mode'])
            ? (bool)$params['preprod_mode']
            : (bool)$this->_configHelper->get('preprod_mode_enable');
        $this->_typeImport = isset($params['type']) ? $params['type'] : self::TYPE_MANUAL;
        $this->_logOutput = isset($params['log_output']) ? (bool)$params['log_output'] : false;
        $this->_storeId = isset($params['store_id']) ? (int)$params['store_id'] : null;
        // get params for synchronise one or all orders
        if (array_key_exists('marketplace_sku', $params)
            && array_key_exists('marketplace_name', $params)
            && array_key_exists('store_id', $params)
        ) {
            if (isset($params['order_lengow_id'])) {
                $this->_orderLengowId = (int)$params['order_lengow_id'];
            }
            $this->_importOneOrder = true;
            $this->_limit = 1;
            $this->_marketplaceSku = (string)$params['marketplace_sku'];
            $this->_marketplaceName = (string)$params['marketplace_name'];
            if (array_key_exists('delivery_address_id', $params) && $params['delivery_address_id'] != '') {
                $this->_deliveryAddressId = (int)$params['delivery_address_id'];
            }
        } else {
            // set the time interval
            $this->_setIntervalTime(
                isset($params['days']) ? (int)$params['days'] : false,
                isset($params['created_from']) ? $params['created_from'] : false,
                isset($params['created_to']) ? $params['created_to'] : false
            );
            $this->_limit = isset($params['limit']) ? (int)$params['limit'] : 0;
        }
    }

    /**
     * Execute import: fetch orders and import them
     *
     * @return array
     */
    public function exec()
    {
        $orderNew = 0;
        $orderUpdate = 0;
        $orderError = 0;
        $errors = array();
        $globalError = false;
        $syncOk = true;
        // clean logs > 20 days
        $this->_helper->cleanLog();
        if ($this->_importHelper->importIsInProcess() && !$this->_preprodMode && !$this->_importOneOrder) {
            $globalError = $this->_helper->setLogMessage(
                'lengow_log.error.rest_time_to_import',
                array('rest_time' => $this->_importHelper->restTimeToImport())
            );
            $this->_helper->log(Lengow_Connector_Helper_Data::CODE_IMPORT, $globalError, $this->_logOutput);
        } elseif (!$this->_checkCredentials()) {
            $globalError = $this->_helper->setLogMessage('lengow_log.error.credentials_not_valid');
            $this->_helper->log(Lengow_Connector_Helper_Data::CODE_IMPORT, $globalError, $this->_logOutput);
        } else {
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
            if ($this->_preprodMode) {
                $this->_helper->log(
                    Lengow_Connector_Helper_Data::CODE_IMPORT,
                    $this->_helper->setLogMessage('log.import.preprod_mode_active'),
                    $this->_logOutput
                );
            }
            // get all store for import
            $storeCollection = Mage::getResourceModel('core/store_collection')->addFieldToFilter('is_active', 1);
            foreach ($storeCollection as $store) {
                if (!is_null($this->_storeId) && (int)$store->getId() !== $this->_storeId) {
                    continue;
                }
                if ($this->_configHelper->storeIsActive((int)$store->getId())) {
                    $this->_helper->log(
                        Lengow_Connector_Helper_Data::CODE_IMPORT,
                        $this->_helper->setLogMessage(
                            'log.import.start_for_store',
                            array(
                                'store_name' => $store->getName(),
                                'store_id' => (int)$store->getId(),
                            )
                        ),
                        $this->_logOutput
                    );
                    try {
                        // check store catalog ids
                        if (!$this->_checkCatalogIds($store)) {
                            $errorCatalogIds = $this->_helper->setLogMessage(
                                'lengow_log.error.no_catalog_for_store',
                                array(
                                    'store_name' => $store->getName(),
                                    'store_id' => (int)$store->getId(),
                                )
                            );
                            $this->_helper->log(
                                Lengow_Connector_Helper_Data::CODE_IMPORT,
                                $errorCatalogIds,
                                $this->_logOutput
                            );
                            $errors[(int)$store->getId()] = $errorCatalogIds;
                            continue;
                        }
                        // get orders from Lengow API
                        $orders = $this->_getOrdersFromApi($store);
                        $totalOrders = count($orders);
                        if ($this->_importOneOrder) {
                            $this->_helper->log(
                                Lengow_Connector_Helper_Data::CODE_IMPORT,
                                $this->_helper->setLogMessage(
                                    'log.import.find_one_order',
                                    array(
                                        'nb_order' => $totalOrders,
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
                                        'nb_order' => $totalOrders,
                                        'account_id' => $this->_accountId,
                                    )
                                ),
                                $this->_logOutput
                            );
                        }
                        if ($totalOrders <= 0 && $this->_importOneOrder) {
                            throw new Lengow_Connector_Model_Exception('lengow_log.error.order_not_found');
                        } elseif ($totalOrders <= 0) {
                            continue;
                        }
                        if (!is_null($this->_orderLengowId)) {
                            Mage::getModel('lengow/import_ordererror')->finishOrderErrors($this->_orderLengowId);
                        }
                        // import orders in Magento
                        $result = $this->_importOrders($orders, (int)$store->getId());
                        if (!$this->_importOneOrder) {
                            $orderNew += $result['order_new'];
                            $orderUpdate += $result['order_update'];
                            $orderError += $result['order_error'];
                        }
                    } catch (Lengow_Connector_Model_Exception $e) {
                        $errorMessage = $e->getMessage();
                    } catch (Exception $e) {
                        $errorMessage = '[Magento error] "' . $e->getMessage()
                            . '" ' . $e->getFile() . ' line ' . $e->getLine();
                    }
                    if (isset($errorMessage)) {
                        $syncOk = false;
                        if (!is_null($this->_orderLengowId)) {
                            /** @var Lengow_Connector_Model_Import_Ordererror $lengowOrderError */
                            $lengowOrderError = Mage::getModel('lengow/import_ordererror');
                            $lengowOrderError->finishOrderErrors($this->_orderLengowId);
                            $lengowOrderError->createOrderError(
                                array(
                                    'order_lengow_id' => $this->_orderLengowId,
                                    'message' => $errorMessage,
                                    'type' => 'import',
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
                        $errors[(int)$store->getId()] = $errorMessage;
                        unset($errorMessage);
                        continue;
                    }
                }
                unset($store);
            }
            if (!$this->_importOneOrder) {
                $this->_helper->log(
                    Lengow_Connector_Helper_Data::CODE_IMPORT,
                    $this->_helper->setLogMessage(
                        'lengow_log.error.nb_order_imported',
                        array('nb_order' => $orderNew)
                    ),
                    $this->_logOutput
                );
                $this->_helper->log(
                    Lengow_Connector_Helper_Data::CODE_IMPORT,
                    $this->_helper->setLogMessage(
                        'lengow_log.error.nb_order_updated',
                        array('nb_order' => $orderUpdate)
                    ),
                    $this->_logOutput
                );
                $this->_helper->log(
                    Lengow_Connector_Helper_Data::CODE_IMPORT,
                    $this->_helper->setLogMessage(
                        'lengow_log.error.nb_order_with_error',
                        array('nb_order' => $orderError)
                    ),
                    $this->_logOutput
                );
            }
            // update last import date
            if (!$this->_importOneOrder && $syncOk) {
                $this->_importHelper->updateDateImport($this->_typeImport);
            }
            // finish import process
            $this->_importHelper->setImportEnd();
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_IMPORT,
                $this->_helper->setLogMessage('log.import.end', array('type' => $this->_typeImport)),
                $this->_logOutput
            );
            // sending email in error for orders
            if ($this->_configHelper->get('report_mail_enable') && !$this->_preprodMode && !$this->_importOneOrder) {
                $this->_importHelper->sendMailAlert($this->_logOutput);
            }
            if (!$this->_preprodMode && !$this->_importOneOrder && $this->_typeImport === self::TYPE_MANUAL) {
                /** @var Lengow_Connector_Model_Import_Action $action */
                $action = Mage::getModel('lengow/import_action');
                $action->checkFinishAction($this->_logOutput);
                $action->checkOldAction($this->_logOutput);
                $action->checkActionNotSent($this->_logOutput);
                unset($action);
            }
        }
        // clear session
        Mage::getSingleton('core/session')->setIsFromlengow(0);
        // save global error
        if ($globalError) {
            $errors[0] = $globalError;
            if (isset($this->_orderLengowId) && $this->_orderLengowId) {
                /** @var Lengow_Connector_Model_Import_Ordererror $lengowOrderError */
                $lengowOrderError = Mage::getModel('lengow/import_ordererror');
                $lengowOrderError->finishOrderErrors($this->_orderLengowId);
                $lengowOrderError->createOrderError(
                    array(
                        'order_lengow_id' => $this->_orderLengowId,
                        'message' => $globalError,
                        'type' => 'import',
                    )
                );
                unset($lengowOrderError);
            }
        }
        if ($this->_importOneOrder) {
            $result['error'] = $errors;
            return $result;
        } else {
            return array(
                'order_new' => $orderNew,
                'order_update' => $orderUpdate,
                'order_error' => $orderError,
                'error' => $errors,
            );
        }
    }

    /**
     * Check credentials and get Lengow connector
     *
     * @return boolean
     */
    protected function _checkCredentials()
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
     * Check catalog ids for a store
     *
     * @param Mage_Core_Model_Store $store Magento store instance
     *
     * @return boolean
     */
    protected function _checkCatalogIds($store)
    {
        if ($this->_importOneOrder) {
            return true;
        }
        $storeCatalogIds = array();
        $catalogIds = $this->_configHelper->getCatalogIds((int)$store->getId());
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
                $this->_catalogIds[$catalogId] = array('store_id' => (int)$store->getId(), 'name' => $store->getName());
                $storeCatalogIds[] = $catalogId;
            }
        }
        if (count($storeCatalogIds) > 0) {
            $this->_storeCatalogIds = $storeCatalogIds;
            return true;
        }
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
    protected function _getOrdersFromApi($store)
    {
        $page = 1;
        $orders = array();
        $coreDate = Mage::getModel('core/date');
        // convert order amount or not
        $noCurrencyConversion = !(bool)$this->_configHelper->get('currency_conversion_enabled', $store->getId());
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
            $dateFrom = $this->_createdFrom ? $this->_createdFrom : $this->_updatedFrom;
            $dateTo = $this->_createdTo ? $this->_createdTo : $this->_updatedTo;
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_IMPORT,
                $this->_helper->setLogMessage(
                    'log.import.connector_get_all_order',
                    array(
                        'date_from' => $coreDate->date('Y-m-d H:i:s', $dateFrom),
                        'date_to' => $coreDate->date('Y-m-d H:i:s', $dateTo),
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
                            'marketplace_order_id' => $this->_marketplaceSku,
                            'marketplace' => $this->_marketplaceName,
                            'no_currency_conversion' => $noCurrencyConversion,
                            'account_id' => $this->_accountId,
                            'page' => $page,
                        ),
                        Lengow_Connector_Model_Connector::FORMAT_STREAM,
                        '',
                        $this->_logOutput
                    );
                } else {
                    if ($this->_createdFrom && $this->_createdTo) {
                        $timeParams = [
                            'marketplace_order_date_from' => $coreDate->date('c', $this->_createdFrom),
                            'marketplace_order_date_to' => $coreDate->date('c', $this->_createdTo),
                        ];
                    } else {
                        $timeParams = [
                            'updated_from' =>  $coreDate->date('c', $this->_updatedFrom),
                            'updated_to' => $coreDate->date('c', $this->_updatedTo),
                        ];
                    }
                    $results = $this->_connector->get(
                        Lengow_Connector_Model_Connector::API_ORDER,
                        array_merge(
                            $timeParams,
                            array(
                                'catalog_ids' => implode(',', $this->_storeCatalogIds),
                                'no_currency_conversion' => $noCurrencyConversion,
                                'account_id' => $this->_accountId,
                                'page' => $page,
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
            $finish = (is_null($results->next) || $this->_importOneOrder) ? true : false;
        } while ($finish != true);
        return $orders;
    }

    /**
     * Create or update order in Magento
     *
     * @param mixed $orders API orders
     * @param integer $storeId Magento store Id
     *
     * @return array|false
     */
    protected function _importOrders($orders, $storeId)
    {
        $orderNew = 0;
        $orderUpdate = 0;
        $orderError = 0;
        $importFinished = false;
        foreach ($orders as $orderData) {
            if (!$this->_importOneOrder) {
                $this->_importHelper->setImportInProcess();
            }
            $nbPackage = 0;
            $marketplaceSku = (string)$orderData->marketplace_order_id;
            if ($this->_preprodMode) {
                $marketplaceSku .= '--' . time();
            }
            // set current order to cancel hook updateOrderStatus
            Mage::getSingleton('core/session')->setCurrentOrderLengow($marketplaceSku);
            // if order contains no package
            if (empty($orderData->packages)) {
                $this->_helper->log(
                    Lengow_Connector_Helper_Data::CODE_IMPORT,
                    $this->_helper->setLogMessage('log.import.error_no_package'),
                    $this->_logOutput,
                    $marketplaceSku
                );
                continue;
            }
            // start import
            foreach ($orderData->packages as $packageData) {
                $nbPackage++;
                // check whether the package contains a shipping address
                if (!isset($packageData->delivery->id)) {
                    $this->_helper->log(
                        Lengow_Connector_Helper_Data::CODE_IMPORT,
                        $this->_helper->setLogMessage('log.import.error_no_delivery_address'),
                        $this->_logOutput,
                        $marketplaceSku
                    );
                    continue;
                }
                $packageDeliveryAddressId = (int)$packageData->delivery->id;
                $firstPackage = $nbPackage > 1 ? false : true;
                // check the package for re-import order
                if ($this->_importOneOrder) {
                    if (!is_null($this->_deliveryAddressId)
                        && $this->_deliveryAddressId !== $packageDeliveryAddressId
                    ) {
                        $this->_helper->log(
                            Lengow_Connector_Helper_Data::CODE_IMPORT,
                            $this->_helper->setLogMessage('log.import.error_wrong_package_number'),
                            $this->_logOutput,
                            $marketplaceSku
                        );
                        continue;
                    }
                }
                try {
                    // try to import or update order
                    /** @var Lengow_Connector_Model_Import_Importorder $importOrder */
                    $importOrder = Mage::getModel(
                        'lengow/import_importorder',
                        array(
                            'store_id' => $storeId,
                            'preprod_mode' => $this->_preprodMode,
                            'log_output' => $this->_logOutput,
                            'marketplace_sku' => $marketplaceSku,
                            'delivery_address_id' => $packageDeliveryAddressId,
                            'order_data' => $orderData,
                            'package_data' => $packageData,
                            'first_package' => $firstPackage,
                            'import_helper' => $this->_importHelper,
                        )
                    );
                    $order = $importOrder->importOrder();
                } catch (Lengow_Connector_Model_Exception $e) {
                    $errorMessage = $e->getMessage();
                } catch (Exception $e) {
                    $errorMessage = '[Magento error]: "' . $e->getMessage()
                        . '" ' . $e->getFile() . ' line ' . $e->getLine();
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
                if (isset($order)) {
                    // sync to lengow if no preprod_mode
                    if (!$this->_preprodMode && isset($order['order_new']) && $order['order_new']) {
                        $magentoOrder = Mage::getModel('sales/order')->load($order['order_id']);
                        $synchro = Mage::getModel('lengow/import_order')->synchronizeOrder(
                            $magentoOrder,
                            $this->_connector,
                            $this->_logOutput
                        );
                        if ($synchro) {
                            $synchroMessage = $this->_helper->setLogMessage(
                                'log.import.order_synchronized_with_lengow',
                                array('order_id' => $magentoOrder->getIncrementId())
                            );
                        } else {
                            $synchroMessage = $this->_helper->setLogMessage(
                                'log.import.order_not_synchronized_with_lengow',
                                array('order_id' => $magentoOrder->getIncrementId())
                            );
                        }
                        $this->_helper->log(
                            Lengow_Connector_Helper_Data::CODE_IMPORT,
                            $synchroMessage,
                            $this->_logOutput,
                            $marketplaceSku
                        );
                        unset($magentoOrder);
                    }
                    // clean current order in session
                    Mage::getSingleton('core/session')->setCurrentOrderLengow(false);
                    // if re-import order -> return order informations
                    if ($this->_importOneOrder) {
                        return $order;
                    }
                    if (isset($order['order_new']) && $order['order_new']) {
                        $orderNew++;
                    } elseif (isset($order['order_update']) && $order['order_update']) {
                        $orderUpdate++;
                    } elseif (isset($order['order_error']) && $order['order_error']) {
                        $orderError++;
                    }
                }
                // clean process
                unset($importOrder, $order);
                // if limit is set
                if ($this->_limit > 0 && $orderNew === $this->_limit) {
                    $importFinished = true;
                    break;
                }
            }
            if ($importFinished) {
                break;
            }
        }
        return array(
            'order_new' => $orderNew,
            'order_update' => $orderUpdate,
            'order_error' => $orderError,
        );
    }

    /**
     * Set interval time for order synchronisation
     *
     * @param integer|false $days Import period
     * @param string|false $createdFrom Import of orders since
     * @param string|false $createdTo Import of orders until
     */
    protected function _setIntervalTime($days, $createdFrom, $createdTo)
    {
        if ($createdFrom && $createdTo) {
            // retrieval of orders created from ... until ...
            $createdFromTimestamp = Mage::getModel('core/date')->gmtTimestamp($createdFrom);
            $createdToTimestamp = Mage::getModel('core/date')->gmtTimestamp($createdTo) + 86399;
            $intervalTime = (int)($createdToTimestamp - $createdFromTimestamp);
            $this->_createdFrom = $createdFromTimestamp;
            $this->_createdTo = $intervalTime > self::MAX_INTERVAL_TIME
                ? $createdFromTimestamp + self::MAX_INTERVAL_TIME
                : $createdToTimestamp;
        } else {
            if ($days) {
                $intervalTime = $days * 86400;
                $intervalTime = $intervalTime > self::MAX_INTERVAL_TIME ? self::MAX_INTERVAL_TIME : $intervalTime;
            } else {
                // order recovery updated since ... days
                $importDays = (int)$this->_configHelper->get('days');
                $intervalTime = $importDays * 86400;
                // add security for older versions of the plugin
                $intervalTime = $intervalTime < self::MIN_INTERVAL_TIME ? self::MIN_INTERVAL_TIME : $intervalTime;
                $intervalTime = $intervalTime > self::MAX_INTERVAL_TIME ? self::MAX_INTERVAL_TIME : $intervalTime;
                // get dynamic interval time for cron synchronisation
                $lastImport = $this->_importHelper->getLastImport();
                $lastSettingUpdate = (int)$this->_configHelper->get('last_setting_update');
                if ($this->_typeImport !== self::TYPE_MANUAL
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
    }
}
