<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Model_Import extends Varien_Object
{
    /**
     * @var Lengow_Connector_Helper_Data
     */
    protected $_helper = null;

    /**
     * @var Lengow_Connector_Helper_Import
     */
    protected $_importHelper = null;

    /**
     * @var Lengow_Connector_Helper_Config
     */
    protected $_config = null;

    /**
     * @var integer store id
     */
    protected $_storeId = null;

    /**
     * @var integer order Lengow id
     */
    protected $_orderLengowId = null;

    /**
     * @var string marketplace order sku
     */
    protected $_marketplaceSku = null;

    /**
     * @var string markeplace name
     */
    protected $_marketplaceName = null;

    /**
     * @var integer delivery address id
     */
    protected $_deliveryAddressId = null;

    /**
     * @var integer number of orders to import
     */
    protected $_limit = 0;

    /**
     * @var string type import (manual, cron or magento cron)
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
     * @var array account ids already imported
     */
    protected $_accountIds = array();

    /**
     * @var LengowConnector Lengow connector
     */
    protected $_connector;

    /**
     * Construct the import manager
     *
     * @param array params optional options
     * string  marketplace_sku     lengow marketplace order id to import
     * string  marketplace_name    lengow marketplace name to import
     * string  type                type of current import
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
        $this->_config = Mage::helper('lengow_connector/config');
        // params for re-import order
        if (array_key_exists('marketplace_sku', $params)
            && array_key_exists('marketplace_name', $params)
            && array_key_exists('store_id', $params)
        ) {
            if (isset($params['order_lengow_id'])) {
                $this->_orderLengowId  = (int)$params['order_lengow_id'];
            }
            $this->_importOneOrder = true;
            $this->_limit            = 1;
            $this->_marketplaceSku  = (string)$params['marketplace_sku'];
            $this->_marketplaceName = (string)$params['marketplace_name'];
            if (array_key_exists('delivery_address_id', $params) && $params['delivery_address_id'] != '') {
                $this->_deliveryAddressId = $params['delivery_address_id'];
            }
        } else {
            // recovering the time interval
            $this->_days = (isset($params['days']) ? (int)$params['days'] : null);
            $this->_limit = (isset($params['limit']) ? (int)$params['limit'] : 0);
        }
        // get other params
        $this->_preprodMode = (
            isset($params['preprod_mode'])
                ? (bool)$params['preprod_mode']
                : (bool)$this->_config->get('preprod_mode_enable')
        );
        $this->_typeImport = (isset($params['type']) ? $params['type'] : 'manual');
        $this->_logOutput = (isset($params['log_output']) ? (bool)$params['log_output'] : false);
        $this->_storeId = (isset($params['store_id']) ? (int)$params['store_id'] : null);
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
        // clean logs > 20 days
        $this->_helper->cleanLog();
        if ($this->_importHelper->importIsInProcess() && !$this->_preprodMode && !$this->_importOneOrder) {
            $globalError = $this->_helper->setLogMessage(
                'lengow_log.error.rest_time_to_import',
                array('rest_time' => $this->_importHelper->restTimeToImport())
            );
            $this->_helper->log('Import', $globalError, $this->_logOutput);
            $errors[0] = $globalError;
            if (!is_null($this->_orderLengowId)) {
                $lengowOrderError = Mage::getModel('lengow/import_ordererror');
                $lengowOrderError->finishOrderErrors($this->_orderLengowId);
                $lengowOrderError->createOrderError(
                    array(
                        'order_lengow_id' => $this->_orderLengowId,
                        'message'         => $globalError,
                        'type'            => 'import'
                    )
                );
                unset($lengowOrderError);
            }
        } else {
            // to activate lengow shipping method
            Mage::getSingleton('core/session')->setIsFromlengow(1);
            $this->_helper->log(
                'Import',
                $this->_helper->setLogMessage('log.import.start', array('type' => $this->_typeImport)),
                $this->_logOutput
            );
            if ($this->_preprodMode) {
                $this->_helper->log(
                    'Import',
                    $this->_helper->setLogMessage('log.import.preprod_mode_active'),
                    $this->_logOutput
                );
            }
            if (!$this->_importOneOrder) {
                $this->_importHelper->setImportInProcess();
                // udpate last import date
                $this->_importHelper->updateDateImport($this->_typeImport);
            }
            // get all store for import
            $storeCollection = Mage::getResourceModel('core/store_collection')->addFieldToFilter('is_active', 1);
            foreach ($storeCollection as $store) {
                if (!is_null($this->_storeId) && (int)$store->getId() != $this->_storeId) {
                    continue;
                }
                if ($this->_config->get('store_enable', (int)$store->getId())) {
                    $this->_helper->log(
                        'Import',
                        $this->_helper->setLogMessage(
                            'log.import.start_for_store',
                            array(
                                'store_name' => $store->getName(),
                                'store_id'   => (int)$store->getId()
                            )
                        ),
                        $this->_logOutput
                    );
                    try {
                        // check account ID, Access Token and Secret
                        $errorCredential = $this->_checkCredentials((int)$store->getId(), $store->getName());
                        if ($errorCredential !== true) {
                            $this->_helper->log('Import', $errorCredential, $this->_logOutput);
                            $errors[(int)$store->getId()] = $errorCredential;
                            continue;
                        }
                        // get orders from Lengow API
                        $orders = $this->_getOrdersFromApi($store);
                        $totalOrders = count($orders);
                        if ($this->_importOneOrder) {
                            $this->_helper->log(
                                'Import',
                                $this->_helper->setLogMessage(
                                    'log.import.find_one_order',
                                    array(
                                        'nb_order'         => $totalOrders,
                                        'marketplace_sku'  => $this->_marketplaceSku,
                                        'marketplace_name' => $this->_marketplaceName,
                                        'account_id'       => $this->_accountId
                                    )
                                ),
                                $this->_logOutput
                            );
                        } else {
                            $this->_helper->log(
                                'Import',
                                $this->_helper->setLogMessage(
                                    'log.import.find_all_orders',
                                    array(
                                        'nb_order'   => $totalOrders,
                                        'account_id' => $this->_accountId
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
                            $lengowOrderError = Mage::getModel('lengow/import_ordererror');
                            $lengowOrderError->finishOrderErrors($this->_orderLengowId);
                        }
                        // import orders in Magento
                        $result = $this->_importOrders($orders, (int)$store->getId());
                        if (!$this->_importOneOrder) {
                            $orderNew    += $result['order_new'];
                            $orderUpdate += $result['order_update'];
                            $orderError  += $result['order_error'];
                        }
                    } catch (Lengow_Connector_Model_Exception $e) {
                        $errorMessage = $e->getMessage();
                    } catch (Exception $e) {
                        $errorMessage = '[Magento error] "'.$e->getMessage().'" '.$e->getFile().' line '.$e->getLine();
                    }
                    if (isset($errorMessage)) {
                        if (!is_null($this->_orderLengowId)) {
                            $lengowOrderError = Mage::getModel('lengow/import_ordererror');
                            $lengowOrderError->finishOrderErrors($this->_orderLengowId);
                            $lengowOrderError->createOrderError(
                                array(
                                    'order_lengow_id' => $this->_orderLengowId,
                                    'message'         => $errorMessage,
                                    'type'            => 'import'
                                )
                            );
                            unset($lengowOrderError);
                        }
                        $decodedMessage = $this->_helper->decodeLogMessage($errorMessage, 'en_GB');
                        $this->_helper->log(
                            'Import',
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
                    'Import',
                    $this->_helper->setLogMessage(
                        'lengow_log.error.nb_order_imported',
                        array('nb_order' => $orderNew)
                    ),
                    $this->_logOutput
                );
                $this->_helper->log(
                    'Import',
                    $this->_helper->setLogMessage(
                        'lengow_log.error.nb_order_updated',
                        array('nb_order' => $orderUpdate)
                    ),
                    $this->_logOutput
                );
                $this->_helper->log(
                    'Import',
                    $this->_helper->setLogMessage(
                        'lengow_log.error.nb_order_with_error',
                        array('nb_order' => $orderError)
                    ),
                    $this->_logOutput
                );
            }
            // finish import process
            $this->_importHelper->setImportEnd();
            $this->_helper->log(
                'Import',
                $this->_helper->setLogMessage('log.import.end', array('type' => $this->_typeImport)),
                $this->_logOutput
            );
            // sending email in error for orders
            if ($this->_config->get('report_mail_enable') && !$this->_preprodMode && !$this->_importOneOrder) {
                $this->_importHelper->sendMailAlert($this->_logOutput);
            }
            if (!$this->_preprodMode && !$this->_importOneOrder && $this->_typeImport == 'manual') {
                $action = Mage::getModel('lengow/import_action');
                $action->checkFinishAction();
                $action->checkActionNotSent();
                unset($action);
            }
        }
        // Clear session
        Mage::getSingleton('core/session')->setIsFromlengow(0);
        if ($this->_importOneOrder) {
            $result['error'] = $errors;
            return $result;
        } else {
            return array(
                'order_new'    => $orderNew,
                'order_update' => $orderUpdate,
                'order_error'  => $orderError,
                'error'        => $errors
            );
        }
    }

    /**
     * Check credentials for a store
     *
     * @param integer $storeId   Store Id
     * @param string  $storeName Store name
     *
     * @return boolean
     */
    protected function _checkCredentials($storeId, $storeName)
    {
        $this->_accountId = (int)$this->_config->get('account_id', $storeId);
        $this->_accessToken = $this->_config->get('access_token', $storeId);
        $this->_secretToken = $this->_config->get('secret_token', $storeId);
        if (!$this->_accountId || !$this->_accessToken || !$this->_secretToken) {
            $message = $this->_helper->setLogMessage(
                'lengow_log.error.account_id_empty',
                array(
                    'store_name' => $storeName,
                    'store_id'   => $storeId
                )
            );
            return $message;
        }
        if (array_key_exists($this->_accountId, $this->_accountIds)) {
            $message = $this->_helper->setLogMessage(
                'lengow_log.error.account_id_already_used',
                array(
                    'account_id' => $this->_accountId,
                    'store_name' => $this->_accountIds[$this->_accountId]['store_name'],
                    'store_id'   => $this->_accountIds[$this->_accountId]['store_id'],
                )
            );
            return $message;
        }
        $this->_accountIds[$this->_accountId] = array('store_id' => $storeId, 'store_name' => $storeName);
        return true;
    }

    /**
     * Call Lengow order API
     *
     * @param $store
     *
     * @return mixed
     */
    protected function _getOrdersFromApi($store)
    {
        $page = 1;
        $orders = array();
        // get connector
        $this->_connector = Mage::getModel('lengow/connector');
        $connectorIsValid = $this->_connector->getConnectorByStore($store->getId());
        // get import period
        $days = (!is_null($this->_days) ? $this->_days : $this->_config->get('days', $store->getId()));
        $dateFrom = date('c', strtotime(date('Y-m-d').' -'.$days.'days'));
        $dateTo = date('c');
        if ($connectorIsValid) {
            if ($this->_importOneOrder) {
                $this->_helper->log(
                    'Import',
                    $this->_helper->setLogMessage(
                        'log.import.connector_get_order',
                        array(
                            'marketplace_sku'  => $this->_marketplaceSku,
                            'marketplace_name' => $this->_marketplaceName
                        )
                    ),
                    $this->_logOutput
                );
            } else {
                $this->_helper->log(
                    'Import',
                    $this->_helper->setLogMessage(
                        'log.import.connector_get_all_order',
                        array(
                            'date_from'  => date('Y-m-d', strtotime((string)$dateFrom)),
                            'date_to'    => date('Y-m-d', strtotime((string)$dateTo)),
                            'account_id' => $this->_accountId
                        )
                    ),
                    $this->_logOutput
                );
            }
            do {
                if ($this->_importOneOrder) {
                    $results = $this->_connector->get(
                        '/v3.0/orders',
                        array(
                            'marketplace_order_id' => $this->_marketplaceSku,
                            'marketplace'          => $this->_marketplaceName,
                            'account_id'           => $this->_accountId,
                            'page'                 => $page
                        ),
                        'stream'
                    );
                } else {
                    $results = $this->_connector->get(
                        '/v3.0/orders',
                        array(
                            'updated_from' => $dateFrom,
                            'updated_to'   => $dateTo,
                            'account_id'   => $this->_accountId,
                            'page'         => $page
                        ),
                        'stream'
                    );
                }
                if (is_null($results)) {
                    throw new Lengow_Connector_Model_Exception(
                        $this->_helper->setLogMessage(
                            'lengow_log.exception.no_connection_webservice',
                            array(
                                'store_name' => $store->getName(),
                                'store_id'   => $store->getId()
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
                                'store_id'   => $store->getId()
                            )
                        )
                    );
                }
                if (isset($results->error)) {
                    throw new Lengow_Connector_Model_Exception(
                        $this->_helper->setLogMessage(
                            'lengow_log.exception.error_lengow_webservice',
                            array(
                                'error_code'    => $results->error->code,
                                'error_message' => $results->error->message,
                                'store_name'    => $store->getName(),
                                'store_id'      => $store->getId()
                            )
                        )
                    );
                }
                // Construct array orders
                foreach ($results->results as $order) {
                    $orders[] = $order;
                }
                $page++;
            } while ($results->next != null);
        } else {
            throw new Lengow_Connector_Model_Exception(
                $this->_helper->setLogMessage(
                    'lengow_log.exception.crendentials_not_valid',
                    array(
                        'store_name' => $store->getName(),
                        'store_id'   => $store->getId()
                    )
                )
            );
        }
        return $orders;
    }

    /**
     * Create or update order in Magento
     *
     * @param mixed   $orders   API orders
     * @param integer $storeId Store Id
     *
     * @return mixed
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
                $marketplaceSku .= '--'.time();
            }
            // set current order to cancel hook updateOrderStatus
            Mage::getSingleton('core/session')->setCurrentOrderLengow($marketplaceSku);
            // if order contains no package
            if (count($orderData->packages) == 0) {
                $this->_helper->log(
                    'Import',
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
                        'Import',
                        $this->_helper->setLogMessage('log.import.error_no_delivery_address'),
                        $this->_logOutput,
                        $marketplaceSku
                    );
                    continue;
                }
                $packageDeliveryAddressId = (int)$packageData->delivery->id;
                $firstPackage = ($nbPackage > 1 ? false : true);
                // check the package for re-import order
                if ($this->_importOneOrder) {
                    if (!is_null($this->_deliveryAddressId)
                        && $this->_deliveryAddressId != $packageDeliveryAddressId
                    ) {
                        $this->_helper->log(
                            'Import',
                            $this->_helper->setLogMessage('log.import.error_wrong_package_number'),
                            $this->_logOutput,
                            $marketplaceSku
                        );
                        continue;
                    }
                }
                try {
                    // try to import or update order
                    $importOrder = Mage::getModel(
                        'lengow/import_importorder',
                        array(
                            'store_id'            => $storeId,
                            'preprod_mode'        => $this->_preprodMode,
                            'log_output'          => $this->_logOutput,
                            'marketplace_sku'     => $marketplaceSku,
                            'delivery_address_id' => $packageDeliveryAddressId,
                            'order_data'          => $orderData,
                            'package_data'        => $packageData,
                            'first_package'       => $firstPackage,
                            'import_helper'       => $this->_importHelper
                        )
                    );
                    $order = $importOrder->importOrder();
                } catch (Lengow_Connector_Model_Exception $e) {
                    $errorMessage = $e->getMessage();
                } catch (Exception $e) {
                    $errorMessage = '[Magento error]: "'.$e->getMessage().'" '.$e->getFile().' line '.$e->getLine();
                }
                if (isset($errorMessage)) {
                    $decodedMessage =  $this->_helper->decodeLogMessage($errorMessage, 'en_GB');
                    $this->_helper->log(
                        'Import',
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
                // Sync to lengow if no preprod_mode
                if (!$this->_preprodMode && $order['order_new'] == true) {
                    $magentoOrder = Mage::getModel('sales/order')->load($order['order_id']);
                    $synchro = Mage::getModel('lengow/import_order')->synchronizeOrder(
                        $magentoOrder,
                        $this->_connector
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
                    $this->_helper->log('Import', $synchroMessage, $this->_logOutput, $marketplaceSku);
                    unset($magentoOrder);
                }
                // Clean current order in session
                Mage::getSingleton('core/session')->setCurrentOrderLengow(false);
                // if re-import order -> return order informations
                if ($this->_importOneOrder) {
                    return $order;
                }
                if ($order) {
                    if ($order['order_new'] == true) {
                        $orderNew++;
                    } elseif ($order['order_update'] == true) {
                        $orderUpdate++;
                    } elseif ($order['order_error'] == true) {
                        $orderError++;
                    }
                }
                // clean process
                unset($importOrder, $order);
                // if limit is set
                if ($this->_limit > 0 && $orderNew == $this->_limit) {
                    $importFinished = true;
                    break;
                }
            }
            if ($importFinished) {
                break;
            }
        }
        return array(
            'order_new'    => $orderNew,
            'order_update' => $orderUpdate,
            'order_error'  => $orderError
        );
    }
}
