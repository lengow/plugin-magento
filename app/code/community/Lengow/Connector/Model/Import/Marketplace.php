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
     * @var array all valid actions
     */
    public static $validActions = array(
        'ship' ,
        'cancel'
    );

    /**
     * @var mixed all markeplaces allowed for an account ID
     */
    public static $marketplaces = array();
    
    /**
     * @var mixed the current marketplace
     */
    public $marketplace;
    
    /**
     * @var string the name of the marketplace
     */
    public $name;

    /**
     * @var string the old code of the markeplace for v2 compatibility
     */
    public $legacyCode;

    /**
     * @var string the name of the marketplace
     */
    public $labelName;

    /**
     * @var integer Store Id
     */
    public $storeId;
    
    /**
     * @var boolean if the marketplace is loaded
     */
    public $isLoaded = false;
    
    /**
     * @var array Lengow states => marketplace states
     */
    public $statesLengow = array();
    
    /**
     * @var array marketplace states => Lengow states
     */
    public $states = array();
    
    /**
     * @var array all possible actions of the marketplace
     */
    public $actions = array();

    /**
     * @var array all possible values for actions of the marketplace
     */
    public $argValues = array();
   
    /**
     * @var array all carriers of the marketplace
     */
    public $carriers = array();

    /**
     * @var Lengow_Connector_Helper_Data
     */
    protected $_helper = null;

    /**
     * @var Lengow_Connector_Helper_Config
     */
    protected $_config = null;

    /**
     * Construct a new Marketplace instance with marketplace API
     *
     * @param array params options
     * integer store_id Store Id for current order
     * string  name     Marketplace name
     */
    public function __construct($params = array())
    {
        $this->_helper = Mage::helper('lengow_connector/data');
        $this->_config = Mage::helper('lengow_connector/config');
        $this->storeId = $params['store_id'];
        $this->loadApiMarketplace();
        $this->name = strtolower($params['name']);
        if (!isset(self::$marketplaces[$this->storeId]->{$this->name})) {
            throw new Lengow_Connector_Model_Exception(
                $this->_helper->setLogMessage(
                    'lengow_log.exception.marketplace_not_present',
                    array('marketplace_name' => $this->name)
                )
            );
        }
        $this->marketplace = self::$marketplaces[$this->storeId]->{$this->name};
        if (!empty($this->marketplace)) {
            $this->legacyCode = $this->marketplace->legacy_code;
            $this->labelName = $this->marketplace->name;
            foreach ($this->marketplace->orders->status as $key => $state) {
                foreach ($state as $value) {
                    $this->statesLengow[(string)$value] = (string)$key;
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
                foreach ($action->args_description as $key => $argDescription) {
                    $validValues = array();
                    if (isset($argDescription->valid_values)) {
                        foreach ($argDescription->valid_values as $code => $validValue) {
                            $validValues[(string)$code] = (string)$validValue->label;
                        }
                    }
                    $this->argValues[(string)$key] = array(
                        'default_value'      => (string)$argDescription->default_value,
                        'accept_free_values' => (bool)$argDescription->accept_free_values,
                        'valid_values'       => $validValues
                    );
                }
            }
            if (isset($this->marketplace->orders->carriers)) {
                foreach ($this->marketplace->orders->carriers as $key => $carrier) {
                    $this->carriers[(string)$key] = (string)$carrier->label;
                }
            }
            $this->isLoaded = true;
        }
    }

    /**
     * Load the json configuration of all marketplaces
     */
    public function loadApiMarketplace()
    {
        if (!array_key_exists($this->storeId, self::$marketplaces)) {
            $connector = Mage::getModel('lengow/connector');
            $result = $connector->queryApi('get', '/v3.0/marketplaces', $this->storeId);
            self::$marketplaces[$this->storeId] = $result;
        }
    }

    /**
    * If marketplace exist in xml configuration file
    *
    * @return boolean
    */
    public function isLoaded()
    {
        return $this->isLoaded;
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
        if (array_key_exists($name, $this->statesLengow)) {
            return $this->statesLengow[$name];
        }
    }

    /**
    * Get the default value for argument
    *
    * @param string $name The argument's name
    *
    * @return mixed
    */
    public function getDefaultValue($name)
    {
        if (array_key_exists($name, $this->argValues)) {
            $defaultValue = $this->argValues[$name]['default_value'];
            if (!empty($defaultValue)) {
                return $defaultValue;
            }
        }
        return false;
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
     * @param string                          $orderLineId
     *
     * @return bool
     */
    public function callAction($action, $order, $shipment = null, $orderLineId = null)
    {
        $helper = Mage::helper('lengow_connector/data');
        $orderLengowId = Mage::getModel('lengow/import_order')->getLengowOrderIdWithOrderId($order->getId());
        if ($orderLengowId) {
            $orderLengow = Mage::getModel('lengow/import_order')->load($orderLengowId);
        } else {
            $orderLengow = false;
        }

        try {
            if (!in_array($action, self::$validActions)) {
                throw new Lengow_Connector_Model_Exception(
                    $helper->setLogMessage('lengow_log.exception.action_not_valid', array('action' => $action))
                );
            }
            if (!isset($this->actions[$action])) {
                throw new Lengow_Connector_Model_Exception(
                    $helper->setLogMessage(
                        'lengow_log.exception.marketplace_action_not_present',
                        array('action' => $action)
                    )
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
                $allArgs = array_merge($actions['args'], $actions['optional_args']);
            } elseif (isset($actions['args'])) {
                $allArgs = $actions['args'];
            } else {
                $allArgs = array();
            }
            // Get all order informations
            foreach ($allArgs as $arg) {
                switch ($arg) {
                    case 'tracking_number':
                        $trackings = $shipment->getAllTracks();
                        if (!empty($trackings)) {
                            $lastTrack = end($trackings);
                        }
                        $params[$arg] = isset($lastTrack) ? $lastTrack->getNumber() : '';
                        break;
                    case 'carrier':
                    case 'shipping_method':
                        if ($orderLengow) {
                            $carrierCode = strlen((string)$orderLengow->getData('carrier')) > 0
                                ? (string)$orderLengow->getData('carrier')
                                : false;
                        }
                        if (!$carrierCode) {
                            if (isset($actions['optional_args']) && in_array('carrier', $actions['optional_args'])) {
                                continue;
                            }
                            $trackings = $shipment->getAllTracks();
                            if (!empty($trackings)) {
                                $lastTrack = end($trackings);
                            }
                            $params[$arg] = isset($lastTrack)
                                ? $this->_matchCarrier($lastTrack->getCarrierCode(), $lastTrack->getTitle())
                                : '';
                        }
                        break;
                    case 'shipping_price':
                        $params[$arg] = $order->getShippingInclTax();
                        break;
                    case 'shipping_date':
                        $params[$arg] = date('c');
                        break;
                    default:
                        $defaultValue = $this->getDefaultValue((string)$arg);
                        $paramValue = $defaultValue ? $defaultValue : $arg.' not available';
                        $params[$arg] = $paramValue;
                        break;
                }
            }
            if (!is_null($orderLineId)) {
                $params['line'] = $orderLineId;
            }
            // Check all required arguments
            if (isset($actions['args'])) {
                foreach ($actions['args'] as $arg) {
                    if (!isset($params[$arg]) || strlen($params[$arg]) == 0) {
                        throw new Lengow_Connector_Model_Exception(
                            $helper->setLogMessage(
                                'lengow_log.exception.arg_is_required',
                                array('arg_name' => $arg)
                            )
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
            $result = $connector->queryApi(
                'get',
                '/v3.0/orders/actions/',
                $order->getStore()->getId(),
                array_merge($params, array("queued" => "True"))
            );
            if (isset($result->error) && isset($result->error->message)) {
                throw new Lengow_Connector_Model_Exception($result->error->message);
            }
            if (isset($result->count) && $result->count > 0) {
                foreach ($result->results as $row) {
                    $orderActionId = Mage::getModel('lengow/import_action')->getActiveActionByActionId($row->id);
                    if ($orderActionId) {
                        $orderAction = Mage::getModel('lengow/import_action')->load($orderActionId);
                        $retry = (int)$orderAction->getData('retry') + 1;
                        $orderAction->updateAction(array('retry' => $retry));
                    } else {
                        // if update doesn't work, create new action
                        $orderAction = Mage::getModel('lengow/import_action');
                        $orderAction->createAction(
                            array(
                                'order_id'       => $order->getId(),
                                'action_type'    => $action,
                                'action_id'      => $row->id,
                                'order_line_sku' => isset($params['line']) ? $params['line'] : null,
                                'parameters'     => Mage::helper('core')->jsonEncode($params)
                            )
                        );
                    }
                }
            } else {
                if (!(bool)Mage::helper('lengow_connector/config')->get('preprod_mode_enable')) {
                    $result = $connector->queryApi(
                        'post',
                        '/v3.0/orders/actions/',
                        $order->getStore()->getId(),
                        $params
                    );
                    if (isset($result->id)) {
                        $orderAction = Mage::getModel('lengow/import_action');
                        $orderAction->createAction(
                            array(
                                'order_id'       => $order->getId(),
                                'action_type'    => $action,
                                'action_id'      => $result->id,
                                'order_line_sku' => isset($params['line']) ? $params['line'] : null,
                                'parameters'     => Mage::helper('core')->jsonEncode($params)
                            )
                        );
                    } else {
                        throw new Lengow_Connector_Model_Exception(
                            $helper->setLogMessage(
                                'lengow_log.exception.action_not_created',
                                array('error_message' => Mage::helper('core')->jsonEncode($result))
                            )
                        );
                    }
                }
                // Create log for call action
                $paramList = false;
                foreach ($params as $param => $value) {
                    $paramList.= !$paramList ? '"'.$param.'": '.$value : ' -- "'.$param.'": '.$value;
                }
                $helper->log(
                    'API-OrderAction',
                    $helper->setLogMessage('log.order_action.call_tracking', array('parameters' => $paramList)),
                    false,
                    $order->getData('order_id_lengow')
                );
            }
            return true;
        } catch (Lengow_Connector_Model_Exception $e) {
            $errorMessage = $e->getMessage();
        } catch (Exception $e) {
            $errorMessage = '[Magento error]: "'.$e->getMessage().'" '.$e->getFile().' line '.$e->getLine();
        }
        if (isset($errorMessage)) {
            if ($orderLengow) {
                $processStateFinish = $orderLengow->getOrderProcessState('closed');
                if ((int)$orderLengow->getData('order_process_state') != $processStateFinish) {
                    $orderLengow->updateOrder(array('is_in_error' => 1));
                    $orderError = Mage::getModel('lengow/import_ordererror');
                    $orderError->createOrderError(
                        array(
                            'order_lengow_id' => $orderLengowId,
                            'message'         => $errorMessage,
                            'type'            => 'send'
                        )
                    );
                }
            }
            $decodedMessage = $helper->decodeLogMessage($errorMessage, 'en_GB');
            $helper->log(
                'API-OrderAction',
                $helper->setLogMessage(
                    'log.order_action.call_action_failed',
                    array('decoded_message' => $decodedMessage)
                ),
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
