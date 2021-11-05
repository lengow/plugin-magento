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
        Lengow_Connector_Model_Import_Action::TYPE_SHIP,
        Lengow_Connector_Model_Import_Action::TYPE_CANCEL,
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
                    $this->statesLengow[(string) $value] = (string) $key;
                    $this->states[(string) $key][(string) $value] = (string) $value;
                }
            }
            foreach ($this->marketplace->orders->actions as $key => $action) {
                foreach ($action->status as $state) {
                    $this->actions[(string) $key]['status'][(string) $state] = (string) $state;
                }
                foreach ($action->args as $arg) {
                    $this->actions[(string) $key]['args'][(string) $arg] = (string) $arg;
                }
                foreach ($action->optional_args as $optional_arg) {
                    $this->actions[(string) $key]['optional_args'][(string) $optional_arg] = $optional_arg;
                }
                foreach ($action->args_description as $argKey => $argDescription) {
                    $validValues = array();
                    if (isset($argDescription->valid_values)) {
                        foreach ($argDescription->valid_values as $code => $validValue) {
                            $validValues[(string) $code] = isset($validValue->label)
                                ? (string) $validValue->label
                                : (string) $validValue;
                        }
                    }
                    $defaultValue = isset($argDescription->default_value)
                        ? (string) $argDescription->default_value
                        : '';
                    $acceptFreeValue = !isset($argDescription->accept_free_values)
                        || (bool) $argDescription->accept_free_values;
                    $this->argValues[(string) $argKey] = array(
                        'default_value' => $defaultValue,
                        'accept_free_values' => $acceptFreeValue,
                        'valid_values' => $validValues,
                    );
                }
            }
            if (isset($this->marketplace->orders->carriers)) {
                foreach ($this->marketplace->orders->carriers as $key => $carrier) {
                    $this->carriers[(string) $key] = (string) $carrier->label;
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
            self::$marketplaces = Mage::helper('lengow_connector/sync')->getMarketplaces();
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
            if (isset($actions['args'])
                && is_array($actions['args'])
                && in_array(Lengow_Connector_Model_Import_Action::ARG_LINE, $actions['args'], true)
            ) {
                return true;
            }
            if (isset($actions['optional_args'])
                && is_array($actions['optional_args'])
                && in_array(Lengow_Connector_Model_Import_Action::ARG_LINE, $actions['optional_args'], true)
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Call Action with marketplace
     *
     * @param string $action order action (ship or cancel)
     * @param Mage_Sales_Model_Order $order Magento order instance
     * @param Mage_Sales_Model_Order_Shipment|null $shipment Magento shipment instance
     * @param string|null $orderLineId Lengow order line id
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
            if ($orderLineId  !== null) {
                $params[Lengow_Connector_Model_Import_Action::ARG_LINE] = $orderLineId;
            }
            $params[Lengow_Connector_Model_Import::ARG_MARKETPLACE_ORDER_ID] = $order->getData(
                Lengow_Connector_Model_Import_Order::FIELD_LEGACY_MARKETPLACE_SKU
            );
            $params[Lengow_Connector_Model_Import::ARG_MARKETPLACE] = $order->getData(
                Lengow_Connector_Model_Import_Order::FIELD_LEGACY_MARKETPLACE_NAME
            );
            $params[Lengow_Connector_Model_Import_Action::ARG_ACTION_TYPE] = $action;
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
            $errorMessage = '[Magento error]: "' . $e->getMessage()
                . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
        }
        if (isset($errorMessage)) {
            if ($orderLengow) {
                $processStateFinish = $orderLengow->getOrderProcessState(
                    Lengow_Connector_Model_Import_Order::STATE_CLOSED
                );
                $orderProcessState = (int) $orderLengow->getData(
                    Lengow_Connector_Model_Import_Order::FIELD_ORDER_PROCESS_STATE
                );
                if ($orderProcessState !== $processStateFinish) {
                    $orderLengow->updateOrder(array(Lengow_Connector_Model_Import_Order::FIELD_IS_IN_ERROR => 1));
                    Mage::getModel('lengow/import_ordererror')->createOrderError(
                        array(
                            Lengow_Connector_Model_Import_Ordererror::FIELD_ORDER_LENGOW_ID => $orderLengowId,
                            Lengow_Connector_Model_Import_Ordererror::FIELD_MESSAGE => $errorMessage,
                            Lengow_Connector_Model_Import_Ordererror::FIELD_TYPE =>
                                Lengow_Connector_Model_Import_Ordererror::TYPE_ERROR_SEND,
                        )
                    );
                }
            }
            $decodedMessage = $helper->decodeLogMessage(
                $errorMessage,
                Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE
            );
            $helper->log(
                Lengow_Connector_Helper_Data::CODE_ACTION,
                $helper->setLogMessage(
                    'log.order_action.call_action_failed',
                    array('decoded_message' => $decodedMessage)
                ),
                false,
                $order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_MARKETPLACE_SKU)
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
        if (!in_array($action, self::$validActions, true)) {
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
        if ($order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_MARKETPLACE_SKU) === '') {
            throw new Lengow_Connector_Model_Exception(
                $helper->setLogMessage('lengow_log.exception.marketplace_sku_require')
            );
        }
        if ($order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_MARKETPLACE_NAME) === '') {
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
        if (isset($actions['args'], $actions['optional_args'])) {
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
        // get all order information
        foreach ($marketplaceArguments as $arg) {
            switch ($arg) {
                case Lengow_Connector_Model_Import_Action::ARG_TRACKING_NUMBER:
                    $tracks = $shipment->getAllTracks();
                    if (!empty($tracks)) {
                        $lastTrack = end($tracks);
                    }
                    $params[$arg] = isset($lastTrack) ? $lastTrack->getNumber() : '';
                    break;
                case Lengow_Connector_Model_Import_Action::ARG_CARRIER:
                case Lengow_Connector_Model_Import_Action::ARG_CARRIER_NAME:
                case Lengow_Connector_Model_Import_Action::ARG_SHIPPING_METHOD:
                case Lengow_Connector_Model_Import_Action::ARG_CUSTOM_CARRIER:
                    $carrierCode = false;
                    if ($lengowOrder) {
                        $carrier = (string) $lengowOrder->getData(Lengow_Connector_Model_Import_Order::FIELD_CARRIER);
                        $carrierCode = $carrier !== '' ? $carrier : false;
                    }
                    if (!$carrierCode) {
                        $tracks = $shipment->getAllTracks();
                        if (!empty($tracks)) {
                            $lastTrack = end($tracks);
                        }
                        $carrierCode = isset($lastTrack)
                            ? $this->_matchCarrier($lastTrack->getCarrierCode(), $lastTrack->getTitle())
                            : '';
                    }
                    $params[$arg] = $carrierCode;
                    break;
                case Lengow_Connector_Model_Import_Action::ARG_SHIPPING_PRICE:
                    $params[$arg] = $order->getShippingInclTax();
                    break;
                case Lengow_Connector_Model_Import_Action::ARG_SHIPPING_DATE:
                case Lengow_Connector_Model_Import_Action::ARG_DELIVERY_DATE:
                    $params[$arg] = Mage::getModel('core/date')->date(Lengow_Connector_Helper_Data::DATE_ISO_8601);
                    break;
                default:
                    if (isset($actions['optional_args']) && in_array($arg, $actions['optional_args'], true)) {
                        break;
                    }
                    $defaultValue = $this->getDefaultValue((string) $arg);
                    $paramValue = $defaultValue ?: $arg . ' not available';
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
        // check all required arguments
        if (isset($actions['args'])) {
            foreach ($actions['args'] as $arg) {
                if (!isset($params[$arg]) || $params[$arg] === '') {
                    throw new Lengow_Connector_Model_Exception(
                        Mage::helper('lengow_connector/data')->setLogMessage(
                            'lengow_log.exception.arg_is_required',
                            array('arg_name' => $arg)
                        )
                    );
                }
            }
        }
        // clean empty optional arguments
        if (isset($actions['optional_args'])) {
            foreach ($actions['optional_args'] as $arg) {
                if (isset($params[$arg]) && $params[$arg] === '') {
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
        if (!empty($this->carriers)) {
            $codeCleaned = $this->_cleanString($code);
            $titleCleaned = $this->_cleanString($title);
            // search by Magento carrier code
            // strict search
            $result = $this->_searchCarrierCode($codeCleaned);
            if (!$result) {
                // approximate search
                $result = $this->_searchCarrierCode($codeCleaned, false);
            }
            // search by Magento carrier title if it is different from the Magento carrier code
            if (!$result && $titleCleaned !== $codeCleaned) {
                // strict search
                $result = $this->_searchCarrierCode($titleCleaned);
                if (!$result) {
                    // approximate search
                    $result = $this->_searchCarrierCode($titleCleaned, false);
                }
            }
            if ($result) {
                return $result;
            }
        }
        // no match
        if ($code === Mage_Sales_Model_Order_Shipment_Track::CUSTOM_CARRIER_CODE) {
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
        return strtolower(str_replace($cleanFilters, '', trim($string)));
    }

    /**
     * Search carrier code in a chain
     *
     * @param string $search string cleaned to search
     * @param boolean $strict strict search
     *
     * @return string|false
     */
    private function _searchCarrierCode($search, $strict = true)
    {
        $result = false;
        foreach ($this->carriers as $key => $label) {
            $keyCleaned = $this->_cleanString($key);
            $labelCleaned = $this->_cleanString($label);
            // search on the carrier key
            $found = $this->_searchValue($keyCleaned, $search, $strict);
            // search on the carrier label if it is different from the key
            if (!$found && $labelCleaned !== $keyCleaned) {
                $found = $this->_searchValue($labelCleaned, $search, $strict);
            }
            if ($found) {
                $result = $key;
            }
        }
        return $result;
    }

    /**
     * Strict or approximate search for a chain
     *
     * @param string $pattern search pattern
     * @param string $subject string to search
     * @param boolean $strict strict search
     *
     * @return boolean
     */
    private function _searchValue($pattern, $subject, $strict = true)
    {
        if ($strict) {
            $found = $pattern === $subject;
        } else {
            $found = (bool) preg_match('`.*?' . $pattern . '.*?`i', $subject);
        }
        return $found;
    }
}
