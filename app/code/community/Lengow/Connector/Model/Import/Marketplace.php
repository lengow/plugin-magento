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
 * Model import marketplace
 */
class Lengow_Connector_Model_Import_Marketplace extends Varien_Object
{
    /**
     * @var array all valid actions
     */
    public static $validActions = array(
        'ship',
        'cancel',
    );

    /**
     * @var array all marketplaces allowed for an account ID
     */
    public static $marketplaces = false;

    /**
     * @var mixed the current marketplace
     */
    public $marketplace;

    /**
     * @var string the name of the marketplace
     */
    public $name;

    /**
     * @var string the old code of the marketplace for v2 compatibility
     */
    public $legacyCode;

    /**
     * @var string the name of the marketplace
     */
    public $labelName;

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
     * Construct a new Marketplace instance with marketplace API
     *
     * @param array $params options
     * string  name     Marketplace name
     *
     * @throws Lengow_Connector_Model_Exception marketplace not present
     */
    public function __construct($params = array())
    {
        $this->loadApiMarketplace();
        $this->name = strtolower($params['name']);
        if (!isset(self::$marketplaces->{$this->name})) {
            throw new Lengow_Connector_Model_Exception(
                Mage::helper('lengow_connector/data')->setLogMessage(
                    'lengow_log.exception.marketplace_not_present',
                    array('marketplace_name' => $this->name)
                )
            );
        }
        $this->marketplace = self::$marketplaces->{$this->name};
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
                foreach ($action->args_description as $argKey => $argDescription) {
                    $validValues = array();
                    if (isset($argDescription->valid_values)) {
                        foreach ($argDescription->valid_values as $code => $validValue) {
                            $validValues[(string)$code] = isset($validValue->label)
                                ? (string)$validValue->label
                                : (string)$validValue;
                        }
                    }
                    $defaultValue = isset($argDescription->default_value)
                        ? (string)$argDescription->default_value
                        : '';
                    $acceptFreeValue = isset($argDescription->accept_free_values)
                        ? (bool)$argDescription->accept_free_values
                        : true;
                    $this->argValues[(string)$argKey] = array(
                        'default_value' => $defaultValue,
                        'accept_free_values' => $acceptFreeValue,
                        'valid_values' => $validValues,
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
        if (!self::$marketplaces) {
            self::$marketplaces =  Mage::helper('lengow_connector/sync')->getMarketplaces();
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
     * @return string|false
     */
    public function getStateLengow($name)
    {
        if (array_key_exists($name, $this->statesLengow)) {
            return $this->statesLengow[$name];
        }
        return false;
    }

    /**
     * Get the action with parameters
     *
     * @param string $action order action (ship or cancel)
     *
     * @return array|false
     */
    public function getAction($action)
    {
        if (array_key_exists($action, $this->actions)) {
            return $this->actions[$action];
        }
        return false;
    }

    /**
     * Get the default value for argument
     *
     * @param string $name The argument's name
     *
     * @return string|false
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
     * @param string $action order action (ship or cancel)
     *
     * @return boolean
     */
    public function containOrderLine($action)
    {
        if (isset($this->actions[$action])) {
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
        }
        return false;
    }

    /**
     * Call Action with marketplace
     *
     * @param string $action order action (ship or cancel)
     * @param Mage_Sales_Model_Order $order Magento order instance
     * @param Mage_Sales_Model_Order_Shipment $shipment Magento shipment instance
     * @param string $orderLineId Lengow order line id
     *
     * @return boolean
     */
    public function callAction($action, $order, $shipment = null, $orderLineId = null)
    {
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector/data');
        $orderLengowId = Mage::getModel('lengow/import_order')->getLengowOrderIdWithOrderId($order->getId());
        if ($orderLengowId) {
            /** @var Lengow_Connector_Model_Import_Order $orderLengow */
            $orderLengow = Mage::getModel('lengow/import_order')->load($orderLengowId);
        } else {
            $orderLengow = false;
        }
        try {
            // check the action and order data
            $this->_checkAction($action);
            $this->_checkOrderData($order);
            // get all required and optional arguments for a specific marketplace
            $marketplaceArguments = $this->_getMarketplaceArguments($action);
            // get all available values from an order
            $params = $this->_getAllParams($action, $order, $orderLengow, $shipment, $marketplaceArguments);
            // check required arguments and clean value for empty optionals arguments
            $params = $this->_checkAndCleanParams($action, $params);
            // complete the values with the specific values of the account
            if (!is_null($orderLineId)) {
                $params['line'] = $orderLineId;
            }
            $params['marketplace_order_id'] = $order->getData('order_id_lengow');
            $params['marketplace'] = $order->getData('marketplace_lengow');
            $params['action_type'] = $action;
            // checks whether the action is already created to not return an action
            /** @var Lengow_Connector_Model_Import_Action $orderAction */
            $orderAction = Mage::getModel('lengow/import_action');
            $canSendAction = $orderAction->canSendAction($params, $order);
            if ($canSendAction) {
                // send a new action on the order via the Lengow API
                $orderAction->sendAction($params, $order);
            }
        } catch (Lengow_Connector_Model_Exception $e) {
            $errorMessage = $e->getMessage();
        } catch (Exception $e) {
            $errorMessage = '[Magento error]: "' . $e->getMessage() . '" ' . $e->getFile() . ' line ' . $e->getLine();
        }
        if (isset($errorMessage)) {
            if ($orderLengow) {
                $processStateFinish = $orderLengow->getOrderProcessState('closed');
                if ((int)$orderLengow->getData('order_process_state') != $processStateFinish) {
                    $orderLengow->updateOrder(array('is_in_error' => 1));
                    Mage::getModel('lengow/import_ordererror')->createOrderError(
                        array(
                            'order_lengow_id' => $orderLengowId,
                            'message' => $errorMessage,
                            'type' => 'send',
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
        return true;
    }

    /**
     * Check if the action is valid and present on the marketplace
     *
     * @param string $action Lengow order actions type (ship or cancel)
     *
     * @throws Lengow_Connector_Model_Exception action not valid / marketplace action not present
     */
    protected function _checkAction($action)
    {
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector/data');
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
    }

    /**
     * Check if the essential data of the order are present
     *
     * @param Mage_Sales_Model_Order $order Magento order instance
     *
     * @throws Lengow_Connector_Model_Exception marketplace sku is required / marketplace name is required
     */
    protected function _checkOrderData($order)
    {
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector/data');
        if (strlen($order->getData('order_id_lengow')) === 0) {
            throw new Lengow_Connector_Model_Exception(
                $helper->setLogMessage('lengow_log.exception.marketplace_sku_require')
            );
        }
        if (strlen($order->getData('marketplace_lengow')) === 0) {
            throw new Lengow_Connector_Model_Exception(
                $helper->setLogMessage('lengow_log.exception.marketplace_name_require')
            );
        }
    }

    /**
     * Get all marketplace arguments for a specific action
     *
     * @param string $action Lengow order actions type (ship or cancel)
     *
     * @return array
     */
    protected function _getMarketplaceArguments($action)
    {
        $actions = $this->getAction($action);
        if (isset($actions['args']) && isset($actions['optional_args'])) {
            $marketplaceArguments = array_merge($actions['args'], $actions['optional_args']);
        } elseif (!isset($actions['args']) && isset($actions['optional_args'])) {
            $marketplaceArguments = $actions['optional_args'];
        } elseif (isset($actions['args'])) {
            $marketplaceArguments = $actions['args'];
        } else {
            $marketplaceArguments = array();
        }
        return $marketplaceArguments;
    }

    /**
     * Get all available values from an order
     *
     * @param string $action Lengow order actions type (ship or cancel)
     * @param Mage_Sales_Model_Order $order Magento order instance
     * @param Lengow_Connector_Model_Import_Order|false $lengowOrder Lengow order instance
     * @param Mage_Sales_Model_Order_Shipment $shipment Magento shipment instance
     * @param array $marketplaceArguments All marketplace arguments for a specific action
     *
     * @return array
     */
    protected function _getAllParams($action, $order, $lengowOrder, $shipment, $marketplaceArguments)
    {
        $params = [];
        $actions = $this->getAction($action);
        // Get all order informations
        foreach ($marketplaceArguments as $arg) {
            switch ($arg) {
                case 'tracking_number':
                    $trackings = $shipment->getAllTracks();
                    if (!empty($trackings)) {
                        $lastTrack = end($trackings);
                    }
                    $params[$arg] = isset($lastTrack) ? $lastTrack->getNumber() : '';
                    break;
                case 'carrier':
                case 'carrier_name':
                case 'shipping_method':
                case 'custom_carrier':
                    $carrierCode = false;
                    if ($lengowOrder) {
                        $carrierCode = strlen((string)$lengowOrder->getData('carrier')) > 0
                            ? (string)$lengowOrder->getData('carrier')
                            : false;
                    }
                    if (!$carrierCode) {
                        $trackings = $shipment->getAllTracks();
                        if (!empty($trackings)) {
                            $lastTrack = end($trackings);
                        }
                        $carrierCode = isset($lastTrack)
                            ? $this->_matchCarrier($lastTrack->getCarrierCode(), $lastTrack->getTitle())
                            : '';
                    }
                    $params[$arg] = $carrierCode;
                    break;
                case 'shipping_price':
                    $params[$arg] = $order->getShippingInclTax();
                    break;
                case 'shipping_date':
                case 'delivery_date':
                    $params[$arg] = date('c');
                    break;
                default:
                    if (isset($actions['optional_args']) && in_array($arg, $actions['optional_args'])) {
                        continue;
                    }
                    $defaultValue = $this->getDefaultValue((string)$arg);
                    $paramValue = $defaultValue ? $defaultValue : $arg . ' not available';
                    $params[$arg] = $paramValue;
                    break;
            }
        }
        return $params;
    }

    /**
     * Get all available values from an order
     *
     * @param string $action Lengow order actions type (ship or cancel)
     * @param array $params all available values
     *
     * @throws Lengow_Connector_Model_Exception argument is required
     *
     * @return array
     */
    protected function _checkAndCleanParams($action, $params)
    {
        $actions = $this->getAction($action);
        // Check all required arguments
        if (isset($actions['args'])) {
            foreach ($actions['args'] as $arg) {
                if (!isset($params[$arg]) || strlen($params[$arg]) === 0) {
                    throw new Lengow_Connector_Model_Exception(
                        Mage::helper('lengow_connector/data')->setLogMessage(
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
                if (isset($params[$arg]) && strlen($params[$arg]) === 0) {
                    unset($params[$arg]);
                }
            }
        }
        return $params;
    }

    /**
     * Match carrier's name with accepted values
     *
     * @param string $code carrier code
     * @param string $title carrier title
     *
     * @return string
     */
    private function _matchCarrier($code, $title)
    {
        if (count($this->carriers) > 0) {
            $codeCleaned = $this->_cleanString($code);
            $titleCleaned = $this->_cleanString($title);
            foreach ($this->carriers as $key => $label) {
                $keyCleaned = $this->_cleanString($key);
                $labelCleaned = $this->_cleanString($label);
                // search by code
                // search on the carrier key
                $found = $this->_searchValue($keyCleaned, $codeCleaned);
                // search on the carrier label if it is different from the key
                if (!$found && $labelCleaned !== $keyCleaned) {
                    $found = $this->_searchValue($labelCleaned, $codeCleaned);
                }
                // search by title if it is different from the code
                if (!$found && $titleCleaned !== $codeCleaned) {
                    // search on the carrier key
                    $found = $this->_searchValue($keyCleaned, $titleCleaned);
                    // search on the carrier label if it is different from the key
                    if (!$found && $labelCleaned !== $keyCleaned) {
                        $found = $this->_searchValue($labelCleaned, $titleCleaned);
                    }
                }
                if ($found) {
                    return $key;
                }
            }
        }
        // no match
        if ($code === 'custom') {
            return $title;
        }
        return $code;
    }

    /**
     * Cleaning a string before search
     *
     * @param string $string string to clean
     *
     * @return string
     */
    private function _cleanString($string)
    {
        $cleanFilters = array(' ', '-', '_', '.');
        return strtolower(str_replace($cleanFilters, '',  trim($string)));
    }

    /**
     * Strict and then approximate search for a chain
     *
     * @param string $pattern search pattern
     * @param string $subject string to search
     *
     * @return boolean
     */
    private function _searchValue($pattern, $subject)
    {
        $found = false;
        if (preg_match('`' . $pattern . '`i', $subject)) {
            $found = true;
        } elseif (preg_match('`.*?' . $pattern . '.*?`i', $subject)) {
            $found = true;
        }
        return $found;
    }
}
