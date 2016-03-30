<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Team Connector <team-connector@lengow.com>
 * @copyright   2015 Lengow SAS
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
    protected $_import_helper = null;

    /**
     * @var Lengow_Connector_Helper_Config
     */
    protected $_config = null;

    /**
     * @var integer store id
     */
    protected $_id_store = null;

    /**
     * @var string marketplace order sku
     */
    protected $_marketplace_sku = null;

    /**
     * @var string markeplace name
     */
    protected $_marketplace_name = null;

    /**
     * @var integer delivery address id
     */
    protected $_delivery_address_id = null;

    /**
     * @var integer number of orders to import
     */
    protected $_limit = 0;

    /**
     * @var string type import (manual or cron)
     */
    protected $_type_import;

    /**
     * @var boolean import one order
     */
    protected $_import_one_order = false;

    /**
     * @var boolean use preprod mode
     */
    protected $_preprod_mode = false;

    /**
     * @var boolean display log messages
     */
    protected $_log_output = false;

    /**
     * @var string account ID
     */
    protected $_account_id;

    /**
     * @var string access token
     */
    protected $_access_token;

    /**
     * @var string secret token
     */
    protected $_secret_token;

    /**
     * @var array account ids already imported
     */
    protected $_account_ids = array();

    /**
     * @var LengowConnector Lengow connector
     */
    protected $_connector;

    /**
     * Construct the import manager
     *
     * @param array params optional options
     * string    marketplace_sku  lengow marketplace order id to import
     * string    marketplace_name lengow marketplace name to import
     * integer   id_store         id store for current import
     * integer   days             import period
     * integer   limit            number of orders to import
     * boolean   log_output       display log messages
     * boolean   preprod_mode     preprod mode
     */
    public function __construct($params = array())
    {
        $this->_helper = Mage::helper('lengow_connector/data');
        $this->_import_helper = Mage::helper('lengow_connector/import');
        $this->_config = Mage::helper('lengow_connector/config');
        // params for re-import order
        if (array_key_exists('marketplace_sku', $params)
            && array_key_exists('marketplace_name', $params)
            && array_key_exists('store_id', $params)
        ) {
            if (isset($params['id_order_lengow'])) {
                $this->_id_order_lengow  = (int)$params['id_order_lengow'];
            }
            $this->_import_one_order = true;
            $this->_limit            = 1;
            $this->_marketplace_sku  = (string)$params['marketplace_sku'];
            $this->_marketplace_name = (string)$params['marketplace_name'];
            if (array_key_exists('delivery_address_id', $params) && $params['delivery_address_id'] != '') {
                $this->_delivery_address_id = $params['delivery_address_id'];
            }
        } else {
            // recovering the time interval
            $this->_days = (isset($params['days']) ? (int)$params['days'] : null);
            $this->_limit = (isset($params['limit']) ? (int)$params['limit'] : 0);
        }
        // get other params
        $this->_preprod_mode = (
            isset($params['preprod_mode'])
                ? (bool)$params['preprod_mode']
                : (bool)$this->_config->get('preprod_mode_enable')
        );
        $this->_type_import = (isset($params['type']) ? $params['type'] : 'manual');
        $this->_log_output = (isset($params['log_output']) ? (bool)$params['log_output'] : false);
        $this->_id_store = (isset($params['store_id']) ? (int)$params['store_id'] : null);
    }

    /**
     * Excute import: fetch orders and import them
     *
     * @return array
     */
    public function exec()
    {
        $order_new    = 0;
        $order_update = 0;
        $order_error  = 0;
        $errors       = array();
        $global_error = false;
        // clean logs
        $this->_helper->cleanLog();
        if ($this->_import_helper->importIsInProcess() && !$this->_preprod_mode && !$this->_import_one_order) {
            $global_error = $this->_helper->setLogMessage('lengow_log.error.import_in_progress');
            $this->_helper->log('Import', $global_error, $this->_log_output);
            $this->_helper->log(
                'Import',
                $this->_helper->setLogMessage('lengow_log.error.rest_time_to_import', array(
                    'rest_time' => $this->_helper->restTimeToImport()
                )),
                $this->_log_output
            );
            $errors[0] = $global_error;
            // TODO
            // if (isset($this->id_order_lengow) && $this->id_order_lengow) {
            //     LengowOrder::finishOrderLogs($this->id_order_lengow, $this->re_import_type);
            //     LengowOrder::addOrderLog($this->id_order_lengow, $global_error, $this->re_import_type);
            // }
        } else {
            $this->_helper->log(
                'Import',
                $this->_helper->setLogMessage('log.import.start', array('type' => $this->_type_import)),
                $this->_log_output
            );
            if ($this->_preprod_mode) {
                $this->_helper->log(
                    'Import',
                    $this->_helper->setLogMessage('log.import.preprod_mode_active'),
                    $this->_log_output
                );
            }
            if (!$this->_import_one_order) {
                // $this->_import_helper->setImportInProcess();
            }
            // udpate last import date
            $this->_import_helper->updateDateImport($this->_type_import);
            // get all shops for import
            $store_collection = Mage::getResourceModel('core/store_collection')->addFieldToFilter('is_active', 1);
            foreach ($store_collection as $store) {
                if (!is_null($this->_id_store) && (int)$store->getId() != $this->_id_store) {
                    continue;
                }
                if ($this->_config->get('store_enable', (int)$store->getId())) {
                    $this->_helper->log(
                        'Import',
                        $this->_helper->setLogMessage('log.import.start_for_shop', array(
                            'name_shop' => $store->getName(),
                            'id_shop'   => (int)$store->getId()
                        )),
                        $this->_log_output
                    );
                    try {
                        // check account ID, Access Token and Secret
                        $error_credential = $this->_checkCredentials((int)$store->getId(), $store->getName());
                        if ($error_credential !== true) {
                            $this->_helper->log('Import', $error_credential, $this->log_output);
                            $errors[(int)$store->getId()] = $error_credential;
                            continue;
                        }
                        // get orders from Lengow API
                        $orders = $this->_getOrdersFromApi($store);
                        $total_orders = count($orders);
                        if ($this->_import_one_order) {
                            $this->_helper->log(
                                'Import',
                                $this->_helper->setLogMessage('log.import.find_one_order', array(
                                    'nb_order'          => $total_orders,
                                    'marketplace_sku'   => $this->_marketplace_sku,
                                    'markeplace_name'   => $this->_marketplace_name,
                                    'account_id'        => $this->_account_id
                                )),
                                $this->_log_output
                            );
                        } else {
                            $this->_helper->log(
                                'Import',
                                $this->_helper->setLogMessage('log.import.find_all_orders', array(
                                    'nb_order'   => $total_orders,
                                    'account_id' => $this->_account_id
                                )),
                                $this->_log_output
                            );
                        }
                        if ($total_orders <= 0 && $this->_import_one_order) {
                            throw new Lengow_Connector_Model_Exception('lengow_log.error.order_not_found');
                        } elseif ($total_orders <= 0) {
                            continue;
                        }
                        // TODO
                        // if (isset($this->id_order_lengow) && $this->id_order_lengow) {
                        //     LengowOrder::finishOrderLogs($this->id_order_lengow, $this->re_import_type);
                        // }
                        // import orders in prestashop
                        $result = $this->_importOrders($orders, (int)$store->id);
                        if (!$this->_import_one_order) {
                            $order_new    += $result['order_new'];
                            $order_update += $result['order_update'];
                            $order_error  += $result['order_error'];
                        }
                    } catch (Lengow_Connector_Model_Exception $e) {
                        $error_message = $e->getMessage();
                    } catch (Exception $e) {
                        $error_message = '[Magento error] "'.$e->getMessage().'" '.$e->getFile().' | '.$e->getLine();
                    }
                    if (isset($error_message)) {
                        // TODO
                        // if (isset($this->_id_order_lengow) && $this->_id_order_lengow) {
                        //     LengowOrder::finishOrderLogs($this->id_order_lengow, $this->re_import_type);
                        //     LengowOrder::addOrderLog($this->id_order_lengow, $error_message, $this->re_import_type);
                        // }
                        $decoded_message = $this->_helper->decodeLogMessage($error_message, 'en_GB');
                        $this->_helper->log(
                            'Import',
                            $this->_helper->setLogMessage('log.import.import_failed', array(
                                'decoded_message' => $decoded_message
                            )),
                            $this->_log_output
                        );
                        $error[(int)$store->getId()] = $e->getMessage();
                        unset($error_message);
                        continue;
                    }
                }
                unset($store);
            }
            if (!$this->_import_one_order) {
                $this->_helper->log(
                    'Import',
                    $this->_helper->setLogMessage('lengow_log.error.nb_order_imported', array(
                        'nb_order' => $order_new
                    )),
                    $this->_log_output
                );
                $this->_helper->log(
                    'Import',
                    $this->_helper->setLogMessage('lengow_log.error.nb_order_updated', array(
                        'nb_order' => $order_update
                    )),
                    $this->_log_output
                );
                $this->_helper->log(
                    'Import',
                    $this->_helper->setLogMessage('lengow_log.error.nb_order_with_error', array(
                        'nb_order' => $order_error
                    )),
                    $this->_log_output
                );
            }
            // finish import process
            // $this->_import_helper->setImportEnd();
            $this->_helper->log(
                'Import',
                $this->_helper->setLogMessage('log.import.end', array('type' => $this->_type_import)),
                $this->_log_output
            );
            // sending email in error for orders
            if ($this->_config->get('report_mail_enable') && !$this->_preprod_mode && !$this->_import_one_order) {
                // TODO
                // $this->_import_helper->sendMailAlert($this->log_output);
            }
        }
        if ($this->_import_one_order) {
            $result['error'] = $errors;
            return $result;
        } else {
            return array(
                'order_new'    => $order_new,
                'order_update' => $order_update,
                'order_error'  => $order_error,
                'error'        => $errors
            );
        }
    }

    /**
     * Check credentials for a store
     *
     * @param integer   $id_store     Store Id
     * @param string    $name_store   Store name
     *
     * @return boolean
     */
    protected function _checkCredentials($id_store, $name_store)
    {
        $this->_account_id = (int)$this->_config->get('account_id', $id_store);
        $this->_access_token = $this->_config->get('access_token', $id_store);
        $this->_secret_token = $this->_config->get('secret_token', $id_store);
        if (!$this->_account_id || !$this->_access_token || !$this->_secret_token) {
            $message = $this->_helper->setLogMessage('lengow_log.error.account_id_empty', array(
                'name_shop' => $name_store,
                'id_shop'   => $id_store
            ));
            return $message;
        }
        if (array_key_exists($this->_account_id, $this->_account_ids)) {
            $message = $this->_helper->setLogMessage('lengow_log.error.account_id_already_used', array(
                'account_id' => $this->_account_id,
                'name_shop'  => $this->_account_ids[$this->_account_id]['name'],
                'id_shop'    => $this->_account_ids[$this->_account_id]['id_shop'],
            ));
            return $message;
        }
        $this->_account_ids[$this->_account_id] = array('id_shop' => $id_store, 'name' => $name_store);
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
        $this->_connector->init($this->_access_token, $this->_secret_token);
        // get import period
        $days = (!is_null($this->_days) ? $this->_days : $this->_config->get('days', $store->getId()));
        $date_from = date('c', strtotime(date('Y-m-d').' -'.$days.'days'));
        $date_to = date('c');
        if ($this->_connector->isValidAuth($this->_account_id)) {
            if ($this->_import_one_order) {
                $this->_helper->log(
                    'Import',
                    $this->_helper->setLogMessage('log.import.connector_get_order', array(
                        'marketplace_sku' => $this->_marketplace_sku,
                        'markeplace_name' => $this->_marketplace_name
                    )),
                    $this->_log_output
                );
            } else {
                $this->_helper->log(
                    'Import',
                    $this->_helper->setLogMessage('log.import.connector_get_all_order', array(
                        'date_from'  => date('Y-m-d', strtotime((string)$date_from)),
                        'date_to'    => date('Y-m-d', strtotime((string)$date_to)),
                        'account_id' => $this->_account_id
                    )),
                    $this->_log_output
                );
            }
            do {
                if ($this->_import_one_order) {
                    $results = $this->_connector->get(
                        '/v3.0/orders',
                        array(
                            'marketplace_order_id' => $this->_marketplace_sku,
                            'marketplace'          => $this->_marketplace_name,
                            'account_id'           => $this->_account_id,
                            'page'                 => $page
                        ),
                        'stream'
                    );
                } else {
                    $results = $this->_connector->get(
                        '/v3.0/orders',
                        array(
                            'updated_from' => $date_from,
                            'updated_to'   => $date_to,
                            'account_id'   => $this->_account_id,
                            'page'         => $page
                        ),
                        'stream'
                    );
                }
                if (is_null($results)) {
                    throw new Lengow_Connector_Model_Exception(
                        $this->_helper->setLogMessage('lengow_log.exception.no_connection_webservice', array(
                            'name_shop' => $store->getName(),
                            'id_shop'   => $store->getId()
                        ))
                    );
                }
                $results = json_decode($results);
                if (!is_object($results)) {
                    throw new Lengow_Connector_Model_Exception(
                        $this->_helper->setLogMessage('lengow_log.exception.no_connection_webservice', array(
                            'name_shop' => $store->getName(),
                            'id_shop'   => $store->getId()
                        ))
                    );
                }
                if (isset($results->error)) {
                    throw new Lengow_Connector_Model_Exception(
                        $this->_helper->setLogMessage('lengow_log.exception.error_lengow_webservice', array(
                            'error_code'    => $results->error->code,
                            'error_message' => $results->error->message,
                            'name_shop'     => $store->getName(),
                            'id_shop'       => $store->getId()
                        ))
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
                $this->_helper->setLogMessage('lengow_log.exception.crendentials_not_valid', array(
                    'name_shop' => $store->getName(),
                    'id_shop'   => $store->getId()
                ))
            );
        }
        return $orders;
    }

    /**
     * Create or update order in Magento
     *
     * @param mixed     $orders     API orders
     * @param integer   $id_store   Store Id
     *
     * @return mixed
     */
    protected function _importOrders($orders, $id_store)
    {
        $order_new       = 0;
        $order_update    = 0;
        $order_error     = 0;
        $import_finished = false;
        foreach ($orders as $order_data) {
            if (!$this->_import_one_order) {
                // $this->_import_helper->setImportInProcess();
            }
            $nb_package = 0;
            $marketplace_sku = (string)$order_data->marketplace_order_id;
            if ($this->_preprod_mode) {
                $marketplace_sku .= '--'.time();
            }
            // set current order to cancel hook updateOrderStatus
            // TODO
            // $this->_import_helper::$current_order = $marketplace_sku;
            // if order contains no package
            if (count($order_data->packages) == 0) {
                $this->_helper->log(
                    'Import',
                    $this->_helper->setLogMessage('log.import.error_no_package'),
                    $this->_log_output,
                    $marketplace_sku
                );
                continue;
            }
            // start import
            foreach ($order_data->packages as $package_data) {
                $nb_package++;
                // check whether the package contains a shipping address
                if (!isset($package_data->delivery->id)) {
                    $this->_helper->log(
                        'Import',
                        $this->_helper->setLogMessage('log.import.error_no_delivery_address'),
                        $this->log_output,
                        $marketplace_sku
                    );
                    continue;
                }
                $package_delivery_address_id = (int)$package_data->delivery->id;
                $first_package = ($nb_package > 1 ? false : true);
                // check the package for re-import order
                if ($this->_import_one_order) {
                    if (!is_null($this->_delivery_address_id)
                        && $this->_delivery_address_id != $package_delivery_address_id
                    ) {
                        $this->_helper->log(
                            'Import',
                            $this->_helper->setLogMessage('log.import.error_wrong_package_number'),
                            $this->log_output,
                            $marketplace_sku
                        );
                        continue;
                    }
                }
                try {
                    // try to import or update order
                    $import_order = Mage::getModel(
                        'lengow/import_importorder',
                        array(
                            'id_store'            => $id_store,
                            'preprod_mode'        => $this->_preprod_mode,
                            'log_output'          => $this->_log_output,
                            'marketplace_sku'     => $marketplace_sku,
                            'delivery_address_id' => $package_delivery_address_id,
                            'order_data'          => $order_data,
                            'package_data'        => $package_data,
                            'first_package'       => $first_package,
                            'import_helper'       => $this->_import_helper
                        )
                    );
                    $order = $import_order->importOrder();
                } catch (Lengow_Connector_Model_Exception $e) {
                    $error_message = $e->getMessage();
                } catch (Exception $e) {
                    $error_message = '[Magento error]: "'.$e->getMessage().'" '.$e->getFile().' | '.$e->getLine();
                }
                if (isset($error_message)) {
                    $decoded_message = $this->_helper->decodeLogMessage($error_message, 'en_GB');
                    $this->_helper->log(
                        'Import',
                        $this->_helper->setLogMessage('log.import.order_import_failed', array(
                            'decoded_message' => $decoded_message
                        )),
                        $this->_log_output,
                        $marketplace_sku
                    );
                    unset($error_message);
                    continue;
                }
                // Sync to lengow if no preprod_mode
                if (!$this->_preprod_mode && $order['order_new'] == true) {
                    // TODO
                    // $lengow_order = new LengowOrder((int)$order['order_id']);
                    // $lengow_order->synchronizeOrder($this->connector, $this->log_output);
                    unset($lengow_order);
                }
                // if re-import order -> return order informations
                if ($this->_import_one_order) {
                    return $order;
                }
                if ($order) {
                    if ($order['order_new'] == true) {
                        $order_new++;
                    } elseif ($order['order_update'] == true) {
                        $order_update++;
                    } elseif ($order['order_error'] == true) {
                        $order_error++;
                    }
                }
                // clean process
                // TODO
                // $this->_import_helper::$current_order = -1;
                unset($import_order);
                unset($order);
                // if limit is set
                if ($this->_limit > 0 && $order_new == $this->_limit) {
                    $import_finished = true;
                    break;
                }

                //check if order action is finish (Ship / Cancel)
                // TODO
                // LengowMarketplace::checkFinishAction();

            }
            if ($import_finished) {
                break;
            }
        }
        return array(
            'order_new'     => $order_new,
            'order_update'  => $order_update,
            'order_error'   => $order_error
        );
    }
}
