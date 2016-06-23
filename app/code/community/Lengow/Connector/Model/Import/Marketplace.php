<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Model_Import_Marketplace extends Varien_Object
{
     /**
     * @var Lengow_Connector_Helper_Data
     */
    protected $_helper = null;

    /**
     * @var Lengow_Connector_Helper_Config
     */
    protected $_config = null;

    /**
     * @var array all valid actions
     */
    public static $VALID_ACTIONS = array(
        'ship' ,
        'cancel'
    );

    /**
     * @var mixed all markeplaces allowed for an account ID
     */
    public static $MARKETPLACES = array();
    
    /**
     * @var mixed the current marketplace
     */
    public $marketplace;
    
    /**
     * @var string the name of the marketplace
     */
    public $name;

    /**
     * @var integer Store Id
     */
    public $store_id;
    
    /**
     * @var boolean if the marketplace is loaded
     */
    public $is_loaded = false;
    
    /**
     * @var array Lengow states => marketplace states
     */
    public $states_lengow = array();
    
    /**
     * @var array marketplace states => Lengow states
     */
    public $states = array();
    
    /**
     * @var array all possible actions of the marketplace
     */
    public $actions = array();
   
    /**
     * @var array all carriers of the marketplace
     */
    public $carriers = array();

    /**
     * Construct a new Markerplace instance
     *
     * @param array params options
     * integer store_id Store Id for current order
     * string  name     Marketplace name
     */
    public function __construct($params = array())
    {
        $this->_helper = Mage::helper('lengow_connector/data');
        $this->_config = Mage::helper('lengow_connector/config');
        $this->store_id = $params['store_id'];
        $this->loadApiMarketplace();
        $this->name = strtolower($params['name']);
        if (!isset(self::$MARKETPLACES[$this->store_id]->{$this->name})) {
            throw new Lengow_Connector_Model_Exception(
                $this->_helper->setLogMessage('lengow_log.exception.marketplace_not_present', array(
                    'markeplace_name' => $this->name
                ))
            );
        }
        $this->marketplace = self::$MARKETPLACES[$this->store_id]->{$this->name};
        if (!empty($this->marketplace)) {
            $this->label_name = $this->marketplace->name;
            foreach ($this->marketplace->orders->status as $key => $state) {
                foreach ($state as $value) {
                    $this->states_lengow[(string)$value] = (string)$key;
                    $this->states[(string)$key][(string)$value] = (string)$value;
                }
            }
            foreach ($this->marketplace->orders->actions as $key => $action) {
                foreach ($action->status as $state) {
                    $this->actions[(string)$key]['status'][(string)$state] = (string)$state;
                }
                foreach ($action->args as $arg) {
                    $this->actions[(string)$key]['args'][(string)$arg] = (string)$arg;
                }
                foreach ($action->optional_args as $optional_arg) {
                    $this->actions[(string)$key]['optional_args'][(string)$optional_arg] = $optional_arg;
                }
            }
            if (isset($this->marketplace->orders->carriers)) {
                foreach ($this->marketplace->orders->carriers as $key => $carrier) {
                    $this->carriers[(string)$key] = (string)$carrier->label;
                }
            }
            $this->is_loaded = true;
        }
    }

    /**
     * Load the json configuration of all marketplaces
     */
    public function loadApiMarketplace()
    {
        if (!array_key_exists($this->store_id, self::$MARKETPLACES)) {
            $connector = Mage::getModel('lengow/connector');
            $result = $connector->queryApi('get', '/v3.0/marketplaces', $this->store_id);
            self::$MARKETPLACES[$this->store_id] = $result;
        }
    }

    /**
    * If marketplace exist in xml configuration file
    *
    * @return boolean
    */
    public function isLoaded()
    {
        return $this->is_loaded;
    }

    /**
    * Get the real lengow's state
    *
    * @param string $name The marketplace state
    *
    * @return string The lengow state
    */
    public function getStateLengow($name)
    {
        if (array_key_exists($name, $this->states_lengow)) {
            return $this->states_lengow[$name];
        }
    }

    /**
     * Is marketplace contain order Line
     *
     * @param string $action (ship, cancel or refund)
     *
     * @return bool
     */
    public function containOrderLine($action)
    {
        $actions = $this->actions[$action];
        if (isset($actions['args']) && is_array($actions['args'])) {
            if (in_array('line', $actions['args'])) {
                return true;
            }
        }
        if (isset($actions['optional_args']) && is_array($actions['optional_args'])) {
            if (in_array('line', $actions['optional_args'])) {
                return true;
            }
        }
        return false;
    }

     /**
     * Call Action with marketplace
     *
     * @param string                          $action
     * @param Mage_Sales_Model_Order          $order
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @param string                          $order_line_id
     *
     * @return bool
     */
    public function callAction($action, $order, $shipment = null, $order_line_id = null)
    {
        $helper = Mage::helper('lengow_connector/data');
        $order_lengow_id = Mage::getModel('lengow/import_order')->getLengowOrderIdWithOrderId($order->getId());
        if ($order_lengow_id) {
            $order_lengow = Mage::getModel('lengow/import_order')->load($order_lengow_id);
        } else {
            $order_lengow = false;
        }

        try {
            if (!in_array($action, self::$VALID_ACTIONS)) {
                throw new Lengow_Connector_Model_Exception(
                    $helper->setLogMessage('lengow_log.exception.action_not_valid', array('action' => $action))
                );
            }
            if (!isset($this->actions[$action])) {
                throw new Lengow_Connector_Model_Exception(
                    $helper->setLogMessage('lengow_log.exception.marketplace_action_not_present', array(
                        'action' => $action
                    ))
                );
            }
            if ((int)$order->getStoreId() == 0) {
                throw new Lengow_Connector_Model_Exception(
                    $helper->setLogMessage('lengow_log.exception.store_id_require')
                );
            }
            if (strlen($order->getData('marketplace_lengow')) == 0) {
                throw new Lengow_Connector_Model_Exception(
                    $helper->setLogMessage('lengow_log.exception.marketplace_name_require')
                );
            }
            // Get all arguments from API
            $params = array();
            $actions = $this->actions[$action];
            if (isset($actions['args']) && isset($actions['optional_args'])) {
                $all_args = array_merge($actions['args'], $actions['optional_args']);
            } elseif (isset($actions['args'])) {
                $all_args = $actions['args'];
            } else {
                $all_args = array();
            }
            // Get all order informations
            foreach ($all_args as $arg) {
                switch ($arg) {
                    case 'tracking_number':
                        $trackings = $shipment->getAllTracks();
                        if (!empty($trackings)) {
                            $last_track = end($trackings);
                        }
                        $params['tracking_number'] = isset($last_track) ? $last_track->getNumber() : '';
                        break;
                    case 'carrier':
                        if ($order_lengow) {
                            $carrier_code = strlen((string)$order_lengow->getData('carrier')) > 0
                                ? (string)$order_lengow->getData('carrier')
                                : false;
                        }
                        if (!$carrier_code) {
                            if (isset($actions['optional_args']) && in_array('carrier', $actions['optional_args'])) {
                                continue;
                            }
                            $trackings = $shipment->getAllTracks();
                            if (!empty($trackings)) {
                                $last_track = end($trackings);
                            }
                            $params['carrier'] = isset($last_track)
                                ? $this->_matchCarrier($last_track->getCarrierCode(), $last_track->getTitle())
                                : '';
                        }
                        break;
                    case 'tracking_url':
                        $params['tracking_url'] = '';
                        break;
                    case 'shipping_price':
                        $params['shipping_price'] = $order->getShippingInclTax();
                        break;
                    default:
                        break;
                }
            }
            if (!is_null($order_line_id)) {
                $params['line'] = $order_line_id;
            }
            // Check all required arguments
            if (isset($actions['args'])) {
                foreach ($actions['args'] as $arg) {
                    if (!isset($params[$arg]) || strlen($params[$arg]) == 0) {
                        throw new Lengow_Connector_Model_Exception(
                            $helper->setLogMessage('lengow_log.exception.arg_is_required', array(
                                'arg_name' => $arg
                            ))
                        );
                    }
                }
            }
            // Clean empty optional arguments
            if (isset($actions['optional_args'])) {
                foreach ($actions['optional_args'] as $arg) {
                    if (isset($params[$arg]) && strlen($params[$arg]) == 0) {
                        unset($params[$arg]);
                    }
                }
            }
            // Set identification parameters
            $params['marketplace_order_id'] = $order->getData('order_id_lengow');
            $params['marketplace'] = $order->getData('marketplace_lengow');
            $params['action_type'] = $action;

            $connector = Mage::getModel('lengow/connector');
            $results = $connector->queryApi(
                'get',
                '/v3.0/orders/actions/',
                $order->getStore()->getId(),
                array_merge($params, array("queued" => "True"))
            );
            if (isset($results->error) && isset($results->error->message)) {
                throw new Lengow_Connector_Model_Exception($results->error->message);
            }
            if (isset($results->count) && $results->count > 0) {
                foreach ($results->results as $row) {
                    $order_action_id = Mage::getModel('lengow/import_action')->getIdByActionId($row->id);
                    if ($order_action_id) {
                        $order_action = Mage::getModel('lengow/import_action')->load($order_action_id);
                        $retry = (int)$order_action->getData('retry') + 1;
                        $order_action->updateAction(array('retry' => $retry));
                    }
                }
            } else {
                if (!(bool)Mage::helper('lengow_connector/config')->get('preprod_mode_enable')) {
                    $results = $connector->queryApi(
                        'post',
                        '/v3.0/orders/actions/',
                        $order->getStore()->getId(),
                        $params
                    );
                    if (isset($results->id)) {
                        $order_action = Mage::getModel('lengow/import_action');
                        $order_action->createAction(array(
                            'order_id'       => $order->getId(),
                            'action_type'    => $action,
                            'action_id'      => $results->id,
                            'order_line_sku' => isset($params['line']) ? $params['line'] : null,
                            'parameters'     => Mage::helper('core')->jsonEncode($params)
                        ));
                    }
                }
                // Create log for call action
                $param_list = false;
                foreach ($params as $param => $value) {
                    $param_list.= !$param_list ? '"'.$param.'": '.$value : ' -- "'.$param.'": '.$value;
                }
                $helper->log(
                    'API-OrderAction',
                    $helper->setLogMessage('log.order_action.call_tracking', array('parameters' => $param_list)),
                    false,
                    $order->getData('order_id_lengow')
                );
            }
            return true;
        } catch (Lengow_Connector_Model_Exception $e) {
            $error_message = $e->getMessage();
        } catch (Exception $e) {
            $error_message = '[Magento error]: "'.$e->getMessage().'" '.$e->getFile().' line '.$e->getLine();
        }
        if (isset($error_message)) {
            if ($order_lengow) {
                $process_state_finish = $order_lengow->getOrderProcessState('closed');
                if ((int)$order_lengow->getData('order_process_state') != $process_state_finish) {
                    $order_lengow->updateOrder(array('is_in_error' => 1));
                    $order_error = Mage::getModel('lengow/import_ordererror');
                    $order_error->createOrderError(array(
                        'order_lengow_id' => $order_lengow_id,
                        'message'         => $error_message,
                        'type'            => 'send'
                    ));
                }
            }
            $decoded_message = $helper->decodeLogMessage($error_message, 'en_GB');
            $helper->log(
                'API-OrderAction',
                $helper->setLogMessage('log.order_action.call_action_failed', array(
                    'decoded_message' => $decoded_message
                )),
                false,
                $order->getData('order_id_lengow')
            );
            return false;
        }
    }

    /**
     * Match carrier's name with accepted values
     *
     * @param string $code
     * @param string $title
     *
     * @return string The matching carrier name
     */
    private function _matchCarrier($code, $title)
    {
        if (count($this->carriers) > 0) {
            // search by code
            foreach ($this->carriers as $key => $carrier) {
                if (preg_match('`'.$key.'`i', trim($code))) {
                    return $value;
                } elseif (preg_match('`.*?'.$key.'.*?`i', $code)) {
                    return $value;
                }
            }
            // search by title
            foreach ($this->carriers as $key => $carrier) {
                if (preg_match('`'.$key.'`i', trim($title))) {
                    return $key;
                } elseif (preg_match('`.*?'.$key.'.*?`i', $title)) {
                    return $key;
                }
            }
        }
        // no match
        if ($code == 'custom') {
            return $title;
        }
        return $code;
    }
}
