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
 * Model import action
 */
class Lengow_Connector_Model_Import_Action extends Mage_Core_Model_Abstract
{
    /**
     * @var string Lengow action table name
     */
    const TABLE_ACTION = 'lengow_action';

    /* Action fields */
    const FIELD_ID = 'id';
    const FIELD_ORDER_ID = 'order_id';
    const FIELD_ACTION_ID = 'action_id';
    const FIELD_ORDER_LINE_SKU = 'order_line_sku';
    const FIELD_ACTION_TYPE = 'action_type';
    const FIELD_RETRY = 'retry';
    const FIELD_PARAMETERS = 'parameters';
    const FIELD_STATE = 'state';
    const FIELD_CREATED_AT = 'created_at';
    const FIELD_UPDATED_AT = 'updated_at';

    /* Action states */
    const STATE_NEW = 0;
    const STATE_FINISH = 1;

    /* Action types */
    const TYPE_SHIP = 'ship';
    const TYPE_CANCEL = 'cancel';

    /* Action API arguments */
    const ARG_ACTION_TYPE = 'action_type';
    const ARG_LINE = 'line';
    const ARG_CARRIER = 'carrier';
    const ARG_CARRIER_NAME = 'carrier_name';
    const ARG_CUSTOM_CARRIER = 'custom_carrier';
    const ARG_SHIPPING_METHOD = 'shipping_method';
    const ARG_TRACKING_NUMBER = 'tracking_number';
    const ARG_SHIPPING_PRICE = 'shipping_price';
    const ARG_SHIPPING_DATE = 'shipping_date';
    const ARG_DELIVERY_DATE = 'delivery_date';

    /**
     * @var integer max interval time for action synchronisation (3 days)
     */
    const MAX_INTERVAL_TIME = 259200;

    /**
     * @var integer security interval time for action synchronisation (2 hours)
     */
    const SECURITY_INTERVAL_TIME = 7200;

    /**
     * @var array Parameters to delete for Get call
     */
    public static $getParamsToDelete = array(
        self::ARG_SHIPPING_DATE,
        self::ARG_DELIVERY_DATE,
    );

    /**
     * @var array $_fieldList field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    protected $_fieldList = array(
        self::FIELD_ORDER_ID => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => true,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => false,
        ),
        self::FIELD_ACTION_ID => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => true,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => false,
        ),
        self::FIELD_ORDER_LINE_SKU => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => false,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => false,
        ),
        self::FIELD_ACTION_TYPE => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => true,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => false,
        ),
        self::FIELD_RETRY => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => false,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => true,
        ),
        self::FIELD_PARAMETERS => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => true,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => false,
        ),
        self::FIELD_STATE => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => false,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => true,
        ),
    );

    /**
     * Constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('lengow/import_action');
    }

    /**
     * Create Lengow action
     *
     * @param array $params action parameters
     *
     * @return Lengow_Connector_Model_Import_Action|false
     */
    public function createAction($params = array())
    {
        foreach ($this->_fieldList as $key => $value) {
            if (!array_key_exists($key, $params) && $value[Lengow_Connector_Helper_Data::FIELD_REQUIRED]) {
                return false;
            }
        }
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        $this->setData(self::FIELD_STATE, self::STATE_NEW);
        $this->setData(
            self::FIELD_CREATED_AT,
            Mage::getModel('core/date')->gmtDate(Lengow_Connector_Helper_Data::DATE_FULL)
        );
        try {
            return $this->save();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Update Lengow action
     *
     * @param array $params action parameters
     *
     * @return Lengow_Connector_Model_Import_Action|false
     */
    public function updateAction($params = array())
    {
        if (!$this->id) {
            return false;
        }
        if ((int) $this->getData(self::FIELD_STATE) !== self::STATE_NEW) {
            return false;
        }
        $updatedFields = $this->getUpdatedFields();
        foreach ($params as $key => $value) {
            if (in_array($key, $updatedFields, true)) {
                $this->setData($key, $value);
            }
        }
        $this->setData(
            self::FIELD_UPDATED_AT,
            Mage::getModel('core/date')->gmtDate(Lengow_Connector_Helper_Data::DATE_FULL)
        );
        try {
            return $this->save();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get updated fields
     *
     * @return array
     */
    public function getUpdatedFields()
    {
        $updatedFields = array();
        foreach ($this->_fieldList as $key => $value) {
            if ($value[Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED]) {
                $updatedFields[] = $key;
            }
        }
        return $updatedFields;
    }

    /**
     * Get active action by API action ID
     *
     * @param integer $actionId action id from API
     *
     * @return integer|false
     */
    public function getActionByActionId($actionId)
    {
        $results = $this->getCollection()
            ->addFieldToFilter(self::FIELD_ACTION_ID, $actionId)
            ->getData();
        if (!empty($results)) {
            return (int) $results[0][self::FIELD_ID];
        }
        return false;
    }

    /**
     * Find active actions by order id
     *
     * @param integer $orderId Magento order id
     * @param string|null $actionType action type (ship or cancel)
     *
     * @return array|false
     */
    public function getActiveActionByOrderId($orderId, $actionType = null)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter(self::FIELD_ORDER_ID, $orderId)
            ->addFieldToFilter(self::FIELD_STATE, self::STATE_NEW);
        if ($actionType !== null) {
            $collection->addFieldToFilter(self::FIELD_RETRY, $actionType);
        }
        $results = $collection->getData();
        if (!empty($results)) {
            return $results;
        }
        return false;
    }

    /**
     * Get last order action type to re-send action
     *
     * @param integer $orderId Magento order id
     *
     * @return string|false
     */
    public function getLastOrderActionType($orderId)
    {
        $results = $this->getCollection()
            ->addFieldToFilter(self::FIELD_ORDER_ID, $orderId)
            ->addFieldToFilter(self::FIELD_STATE, self::STATE_NEW)
            ->addFieldToSelect(self::FIELD_ACTION_TYPE);
        if (!empty($results)) {
            $lastAction = $results->getLastItem()->getData();
            return (string) $lastAction[self::FIELD_ACTION_TYPE];
        }
        return false;
    }

    /**
     * Get all active actions
     *
     * @return array|false
     */
    public function getActiveActions()
    {
        $results = $this->getCollection()
            ->addFieldToFilter('main_table.state', self::STATE_NEW)
            ->getData();
        if (!empty($results)) {
            return $results;
        }
        return false;
    }

    /**
     * Indicates whether an action can be created if it does not already exist
     *
     * @param array $params all available values
     * @param Mage_Sales_Model_Order $order Magento order instance
     *
     * @throws Lengow_Connector_Model_Exception
     *
     * @return boolean
     */
    public function canSendAction($params, $order)
    {
        $sendAction = true;
        // check if action is already created
        $getParams = array_merge($params, array('queued' => 'True'));
        // array key deletion for GET verification
        foreach (self::$getParamsToDelete as $param) {
            if (isset($getParams[$param])) {
                unset($getParams[$param]);
            }
        }
        $result = Mage::getModel('lengow/connector')->queryApi(
            Lengow_Connector_Model_Connector::GET,
            Lengow_Connector_Model_Connector::API_ORDER_ACTION,
            $getParams
        );
        if (isset($result->error, $result->error->message)) {
            throw new Lengow_Connector_Model_Exception($result->error->message);
        }
        if (isset($result->count) && $result->count > 0) {
            foreach ($result->results as $row) {
                $actionId = $this->getActionByActionId($row->id);
                if ($actionId) {
                    /** @var Lengow_Connector_Model_Import_Action $action */
                    $action = Mage::getModel('lengow/import_action')->load($actionId);
                    if ((int) $action->getData(self::FIELD_STATE) === 0) {
                        $retry = (int) $action->getData(self::FIELD_RETRY) + 1;
                        $action->updateAction(array(self::FIELD_RETRY => $retry));
                        $sendAction = false;
                    }
                    unset($action);
                } else {
                    // if update doesn't work, create new action
                    $this->createAction(
                        array(
                            self::FIELD_ORDER_ID => $order->getId(),
                            self::FIELD_ACTION_TYPE => $params[self::ARG_ACTION_TYPE],
                            self::FIELD_ACTION_ID => $row->id,
                            self::FIELD_ORDER_LINE_SKU => isset($params[self::ARG_LINE])
                                ? $params[self::ARG_LINE]
                                : null,
                            self::FIELD_PARAMETERS => Mage::helper('core')->jsonEncode($params),
                        )
                    );
                    $sendAction = false;
                }
            }
        }
        return $sendAction;
    }

    /**
     * Send a new action on the order via the Lengow API
     *
     * @param array $params all available values
     * @param Mage_Sales_Model_Order $order Magento order instance
     *
     * @throws Lengow_Connector_Model_Exception
     */
    public function sendAction($params, $order)
    {
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector/data');
        if (!Mage::helper('lengow_connector/config')->debugModeIsActive()) {
            $result = Mage::getModel('lengow/connector')->queryApi(
                Lengow_Connector_Model_Connector::POST,
                Lengow_Connector_Model_Connector::API_ORDER_ACTION,
                $params
            );
            if (isset($result->id)) {
                $this->createAction(
                    array(
                        self::FIELD_ORDER_ID => $order->getId(),
                        self::FIELD_ACTION_TYPE => $params[self::ARG_ACTION_TYPE],
                        self::FIELD_ACTION_ID => $result->id,
                        self::FIELD_ORDER_LINE_SKU => isset($params[self::ARG_LINE]) ? $params[self::ARG_LINE] : null,
                        self::FIELD_PARAMETERS => Mage::helper('core')->jsonEncode($params),
                    )
                );
            } else {
                if ($result) {
                    $message = $helper->setLogMessage(
                        'lengow_log.exception.action_not_created',
                        array('error_message' => Mage::helper('core')->jsonEncode($result))
                    );
                } else {
                    // generating a generic error message when the Lengow API is unavailable
                    $message = $helper->setLogMessage('lengow_log.exception.action_not_created_api');
                }
                throw new Lengow_Connector_Model_Exception($message);
            }
        }
        // create log for call action
        $paramList = false;
        foreach ($params as $param => $value) {
            $paramList .= !$paramList ? '"' . $param . '": ' . $value : ' -- "' . $param . '": ' . $value;
        }
        $helper->log(
            Lengow_Connector_Helper_Data::CODE_ACTION,
            $helper->setLogMessage('log.order_action.call_tracking', array('parameters' => $paramList)),
            false,
            $order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_MARKETPLACE_SKU)
        );
    }

    /**
     * Removes all actions for one order Magento
     *
     * @param integer $orderId Magento order id
     * @param string $actionType action type (null, ship or cancel)
     *
     * @return boolean
     */
    public function finishAllActions($orderId, $actionType = null)
    {
        // get all order action
        $collection = $this->getCollection()
            ->addFieldToFilter(self::FIELD_ORDER_ID, $orderId)
            ->addFieldToFilter(self::FIELD_STATE, self::STATE_NEW);
        if ($actionType !== null) {
            $collection->addFieldToFilter(self::FIELD_ACTION_TYPE, $actionType);
        }
        $results = $collection->addFieldToSelect(self::FIELD_ID)->getData();
        if (!empty($results)) {
            foreach ($results as $result) {
                $action = Mage::getModel('lengow/import_action')->load($result[self::FIELD_ID]);
                $action->updateAction(array(self::FIELD_STATE => self::STATE_FINISH));
                unset($action);
            }
            return true;
        }
        return false;
    }

    /**
     * Check if active actions are finished
     *
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public function checkFinishAction($logOutput = false)
    {
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector/data');
        /** @var Lengow_Connector_Helper_Config $configHelper */
        $configHelper = Mage::helper('lengow_connector/config');
        if ($configHelper->debugModeIsActive()) {
            return false;
        }
        $helper->log(
            Lengow_Connector_Helper_Data::CODE_ACTION,
            $helper->setLogMessage('log.order_action.check_completed_action'),
            $logOutput
        );
        // get all active actions
        $activeActions = $this->getActiveActions();
        // if no active action, do nothing
        if (!$activeActions) {
            return true;
        }
        // get all actions with API (max 3 days)
        $page = 1;
        $apiActions = array();
        $coreDate = Mage::getModel('core/date');
        $intervalTime = $this->_getIntervalTime();
        $dateFrom = time() - $intervalTime;
        $dateTo = time();
        $helper->log(
            Lengow_Connector_Helper_Data::CODE_ACTION,
            $helper->setLogMessage(
                'log.order_action.connector_get_all_action',
                array(
                    'date_from' => $coreDate->date(Lengow_Connector_Helper_Data::DATE_FULL, $dateFrom),
                    'date_to' => $coreDate->date(Lengow_Connector_Helper_Data::DATE_FULL, $dateTo),
                )
            ),
            $logOutput
        );
        do {
            $results = Mage::getModel('lengow/connector')->queryApi(
                Lengow_Connector_Model_Connector::GET,
                Lengow_Connector_Model_Connector::API_ORDER_ACTION,
                array(
                    Lengow_Connector_Model_Import::ARG_UPDATED_FROM => Mage::app()->getLocale()
                        ->date($dateFrom)
                        ->toString(Lengow_Connector_Helper_Data::DATE_ISO_8601),
                    Lengow_Connector_Model_Import::ARG_UPDATED_TO => Mage::app()->getLocale()
                        ->date($dateTo)
                        ->toString(Lengow_Connector_Helper_Data::DATE_ISO_8601),
                    Lengow_Connector_Model_Import::ARG_PAGE => $page,
                ),
                '',
                $logOutput
            );
            if (!is_object($results) || isset($results->error)) {
                break;
            }
            // construct array actions
            foreach ($results->results as $action) {
                if (isset($action->id)) {
                    $apiActions[$action->id] = $action;
                }
            }
            $page++;
        } while ($results->next !== null);
        if (empty($apiActions)) {
            return false;
        }
        // check foreach action if is complete
        foreach ($activeActions as $action) {
            if (!isset($apiActions[$action[self::FIELD_ACTION_ID]])) {
                continue;
            }
            $apiAction = $apiActions[$action[self::FIELD_ACTION_ID]];
            if (isset($apiAction->queued, $apiAction->processed, $apiAction->errors) && $apiAction->queued == false) {
                // order action is waiting to return from the marketplace
                if ($apiAction->processed == false && empty($apiAction->errors)) {
                    continue;
                }
                // finish action in lengow_action table
                /** @var Lengow_Connector_Model_Import_Action $lengowAction */
                $lengowAction = Mage::getModel('lengow/import_action')->load($action[self::FIELD_ID]);
                $lengowAction->updateAction(array(self::FIELD_STATE => self::STATE_FINISH));
                $orderLengowId = Mage::getModel('lengow/import_order')
                    ->getLengowOrderIdWithOrderId($action[self::FIELD_ORDER_ID]);
                // if lengow order not exist do nothing (compatibility v2)
                if ($orderLengowId) {
                    /** @var Lengow_Connector_Model_Import_Ordererror $orderError */
                    $orderError = Mage::getModel('lengow/import_ordererror');
                    $orderError->finishOrderErrors(
                        $orderLengowId,
                        Lengow_Connector_Model_Import_Ordererror::TYPE_ERROR_SEND
                    );
                    /** @var Lengow_Connector_Model_Import_Order $orderLengow */
                    $orderLengow = Mage::getModel('lengow/import_order')->load($orderLengowId);
                    if ((bool) $orderLengow->getData(Lengow_Connector_Model_Import_Order::FIELD_IS_IN_ERROR)) {
                        $orderLengow->updateOrder(array(Lengow_Connector_Model_Import_Order::FIELD_IS_IN_ERROR => 0));
                    }
                    $processStateFinish = $orderLengow->getOrderProcessState(
                        Lengow_Connector_Model_Import_Order::STATE_CLOSED
                    );
                    $orderProcessState = (int) $orderLengow->getData(
                        Lengow_Connector_Model_Import_Order::FIELD_ORDER_PROCESS_STATE
                    );
                    if ($orderProcessState !== $processStateFinish) {
                        // if action is accepted -> close order and finish all order actions
                        if ($apiAction->processed == true && empty($apiAction->errors)) {
                            $orderLengow->updateOrder(
                                array(
                                    Lengow_Connector_Model_Import_Order::FIELD_ORDER_PROCESS_STATE =>
                                        $processStateFinish,
                                )
                            );
                            $this->finishAllActions($action[self::FIELD_ORDER_ID]);
                        } else {
                            // if action is denied -> create order error
                            $orderError->createOrderError(
                                array(
                                    Lengow_Connector_Model_Import_Ordererror::FIELD_ORDER_LENGOW_ID => $orderLengowId,
                                    Lengow_Connector_Model_Import_Ordererror::FIELD_MESSAGE => $apiAction->errors,
                                    Lengow_Connector_Model_Import_Ordererror::FIELD_TYPE =>
                                        Lengow_Connector_Model_Import_Ordererror::TYPE_ERROR_SEND,
                                )
                            );
                            $orderLengow->updateOrder(
                                array(Lengow_Connector_Model_Import_Order::FIELD_IS_IN_ERROR => 1)
                            );
                            $helper->log(
                                Lengow_Connector_Helper_Data::CODE_ACTION,
                                $helper->setLogMessage(
                                    'log.order_action.call_action_failed',
                                    array('decoded_message' => $apiAction->errors)
                                ),
                                $logOutput,
                                $orderLengow->getData('marketplace_sku')
                            );
                            unset($orderError);
                        }
                    }
                    unset($orderLengow);
                }
                unset($lengowAction);
            }
        }
        $configHelper->set(Lengow_Connector_Helper_Config::LAST_UPDATE_ACTION_SYNCHRONIZATION, time());
        return true;
    }

    /**
     * Remove old actions > 3 days
     *
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public function checkOldAction($logOutput = false)
    {
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector/data');
        if (Mage::helper('lengow_connector/config')->debugModeIsActive()) {
            return false;
        }
        $helper->log(
            Lengow_Connector_Helper_Data::CODE_ACTION,
            $helper->setLogMessage('log.order_action.check_old_action'),
            $logOutput
        );
        // get all old order action (+ 3 days)
        $actions = $this->getOldActions();
        if ($actions) {
            foreach ($actions as $action) {
                /** @var Lengow_Connector_Model_Import_Action $action */
                $action = Mage::getModel('lengow/import_action')->load($action[self::FIELD_ID]);
                $action->updateAction(array(self::FIELD_STATE => self::STATE_FINISH));
                $orderLengowId = Mage::getModel('lengow/import_order')
                    ->getLengowOrderIdWithOrderId($action[self::FIELD_ORDER_ID]);
                if ($orderLengowId) {
                    /** @var Lengow_Connector_Model_Import_Order $orderLengow */
                    $orderLengow = Mage::getModel('lengow/import_order')->load($orderLengowId);
                    $processStateFinish = $orderLengow->getOrderProcessState(
                        Lengow_Connector_Model_Import_Order::STATE_CLOSED
                    );
                    $orderProcessState = (int) $orderLengow->getData(
                        Lengow_Connector_Model_Import_Order::FIELD_ORDER_PROCESS_STATE
                    );
                    if ($orderProcessState !== $processStateFinish
                        && !(bool) $orderLengow->getData(Lengow_Connector_Model_Import_Order::FIELD_IS_IN_ERROR)
                    ) {
                        // if action is denied -> create order error
                        $errorMessage = $helper->setLogMessage('lengow_log.exception.action_is_too_old');
                        /** @var Lengow_Connector_Model_Import_Ordererror $orderError */
                        $orderError = Mage::getModel('lengow/import_ordererror');
                        $orderError->createOrderError(
                            array(
                                Lengow_Connector_Model_Import_Ordererror::FIELD_ORDER_LENGOW_ID => $orderLengowId,
                                Lengow_Connector_Model_Import_Ordererror::FIELD_MESSAGE => $errorMessage,
                                Lengow_Connector_Model_Import_Ordererror::FIELD_TYPE =>
                                    Lengow_Connector_Model_Import_Ordererror::TYPE_ERROR_SEND,
                            )
                        );
                        $orderLengow->updateOrder(array(Lengow_Connector_Model_Import_Order::FIELD_IS_IN_ERROR => 1));
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
                            $logOutput,
                            $orderLengow->getData('marketplace_sku')
                        );
                        unset($orderError);
                    }
                    unset($orderLengow);
                }
                unset($action);
            }
            return true;
        }
        return false;
    }

    /**
     * Get old untreated actions of more than 3 days
     *
     * @return array|false
     */
    public function getOldActions()
    {
        $collection = $this->getCollection()
            ->addFieldToFilter(self::FIELD_STATE, self::STATE_NEW)
            ->addFieldToFilter(
                self::FIELD_CREATED_AT,
                array(
                    'to' => time() - self::MAX_INTERVAL_TIME,
                    'datetime' => true,
                )
            );
        $results = $collection->getData();
        return !empty($results) ? $results : false;
    }

    /**
     * Check if actions are not sent
     *
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public function checkActionNotSent($logOutput = false)
    {
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector/data');
        if (Mage::helper('lengow_connector/config')->debugModeIsActive()) {
            return false;
        }
        $helper->log(
            Lengow_Connector_Helper_Data::CODE_ACTION,
            $helper->setLogMessage('log.order_action.check_action_not_sent'),
            $logOutput
        );
        // get unsent orders
        $unsentOrders = Mage::getModel('lengow/import_order')->getUnsentOrders();
        if ($unsentOrders) {
            foreach ($unsentOrders as $unsentOrder) {
                $order = Mage::getModel('sales/order')->load($unsentOrder['order_id']);
                $shipment = $unsentOrder['action'] === self::TYPE_SHIP
                    ? $order->getShipmentsCollection()->getFirstItem()
                    : null;
                Mage::getModel('lengow/import_order')->callAction($unsentOrder['action'], $order, $shipment);
            }
        }
        return true;
    }

    /**
     * Get interval time for action synchronisation
     *
     * @return integer
     */
    protected function _getIntervalTime()
    {
        $intervalTime = self::MAX_INTERVAL_TIME;
        $lastActionSynchronisation = Mage::helper('lengow_connector/config')->get(
            Lengow_Connector_Helper_Config::LAST_UPDATE_ACTION_SYNCHRONIZATION
        );
        if ($lastActionSynchronisation) {
            $lastIntervalTime = time() - (int) $lastActionSynchronisation;
            $lastIntervalTime += self::SECURITY_INTERVAL_TIME;
            $intervalTime = $lastIntervalTime > $intervalTime ? $intervalTime : $lastIntervalTime;
        }
        return $intervalTime;
    }
}
