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
     * @var integer action state for new action
     */
    const STATE_NEW = 0;

    /**
     * @var integer action state for action finished
     */
    const STATE_FINISH = 1;

    /**
     * @var string action type ship
     */
    const TYPE_SHIP = 'ship';

    /**
     * @var string action type cancel
     */
    const TYPE_CANCEL = 'cancel';

    /**
     * @var string action argument action type
     */
    const ARG_ACTION_TYPE = 'action_type';

    /**
     * @var string action argument line
     */
    const ARG_LINE = 'line';

    /**
     * @var string action argument carrier
     */
    const ARG_CARRIER = 'carrier';

    /**
     * @var string action argument carrier name
     */
    const ARG_CARRIER_NAME = 'carrier_name';

    /**
     * @var string action argument custom carrier
     */
    const ARG_CUSTOM_CARRIER = 'custom_carrier';

    /**
     * @var string action argument shipping method
     */
    const ARG_SHIPPING_METHOD = 'shipping_method';

    /**
     * @var string action argument tracking number
     */
    const ARG_TRACKING_NUMBER = 'tracking_number';

    /**
     * @var string action argument shipping price
     */
    const ARG_SHIPPING_PRICE = 'shipping_price';

    /**
     * @var string action argument shipping date
     */
    const ARG_SHIPPING_DATE = 'shipping_date';

    /**
     * @var string action argument delivery date
     */
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
        'order_id' => array('required' => true, 'updated' => false),
        'action_id' => array('required' => true, 'updated' => false),
        'order_line_sku' => array('required' => false, 'updated' => false),
        'action_type' => array('required' => true, 'updated' => false),
        'retry' => array('required' => false, 'updated' => true),
        'parameters' => array('required' => true, 'updated' => false),
        'state' => array('required' => false, 'updated' => true),
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
            if (!array_key_exists($key, $params) && $value['required']) {
                return false;
            }
        }
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        $this->setData('state', self::STATE_NEW);
        $this->setData('created_at', Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s'));
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
        if ((int)$this->getData('state') !== self::STATE_NEW) {
            return false;
        }
        $updatedFields = $this->getUpdatedFields();
        foreach ($params as $key => $value) {
            if (in_array($key, $updatedFields)) {
                $this->setData($key, $value);
            }
        }
        $this->setData('updated_at', Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s'));
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
            if ($value['updated']) {
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
            ->addFieldToFilter('action_id', $actionId)
            ->getData();
        if (!empty($results)) {
            return (int)$results[0]['id'];
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
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('state', self::STATE_NEW);
        if ($actionType !== null) {
            $collection->addFieldToFilter('action_type', $actionType);
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
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('state', self::STATE_NEW)
            ->addFieldToSelect('action_type');
        if (!empty($results)) {
            $lastAction = $results->getLastItem()->getData();
            return (string)$lastAction['action_type'];
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
        if (isset($result->error) && isset($result->error->message)) {
            throw new Lengow_Connector_Model_Exception($result->error->message);
        }
        if (isset($result->count) && $result->count > 0) {
            foreach ($result->results as $row) {
                $actionId = $this->getActionByActionId($row->id);
                if ($actionId) {
                    /** @var Lengow_Connector_Model_Import_Action $action */
                    $action = Mage::getModel('lengow/import_action')->load($actionId);
                    if ((int)$action->getData('state') === 0) {
                        $retry = (int)$action->getData('retry') + 1;
                        $action->updateAction(array('retry' => $retry));
                        $sendAction = false;
                    }
                    unset($action);
                } else {
                    // if update doesn't work, create new action
                    $this->createAction(
                        array(
                            'order_id' => $order->getId(),
                            'action_type' => $params[Lengow_Connector_Model_Import_Action::ARG_ACTION_TYPE],
                            'action_id' => $row->id,
                            'order_line_sku' => isset($params[Lengow_Connector_Model_Import_Action::ARG_LINE])
                                ? $params[Lengow_Connector_Model_Import_Action::ARG_LINE]
                                : null,
                            'parameters' => Mage::helper('core')->jsonEncode($params),
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
                        'order_id' => $order->getId(),
                        'action_type' => $params[Lengow_Connector_Model_Import_Action::ARG_ACTION_TYPE],
                        'action_id' => $result->id,
                        'order_line_sku' => isset($params[Lengow_Connector_Model_Import_Action::ARG_LINE])
                            ? $params[Lengow_Connector_Model_Import_Action::ARG_LINE]
                            : null,
                        'parameters' => Mage::helper('core')->jsonEncode($params),
                    )
                );
            } else {
                if ($result && $result !== null) {
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
            $order->getData('order_id_lengow')
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
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('state', self::STATE_NEW);
        if ($actionType !== null) {
            $collection->addFieldToFilter('action_type', $actionType);
        }
        $results = $collection->addFieldToSelect('id')->getData();
        if (!empty($results)) {
            foreach ($results as $result) {
                $action = Mage::getModel('lengow/import_action')->load($result['id']);
                $action->updateAction(array('state' => self::STATE_FINISH));
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
                    'date_from' => $coreDate->date('Y-m-d H:i:s', $dateFrom),
                    'date_to' => $coreDate->date('Y-m-d H:i:s', $dateTo),
                )
            ),
            $logOutput
        );
        do {
            $results = Mage::getModel('lengow/connector')->queryApi(
                Lengow_Connector_Model_Connector::GET,
                Lengow_Connector_Model_Connector::API_ORDER_ACTION,
                array(
                    'updated_from' => Mage::app()->getLocale()->date($dateFrom)->toString('c'),
                    'updated_to' => Mage::app()->getLocale()->date($dateTo)->toString('c'),
                    'page' => $page,
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
        } while ($results->next != null);
        if (empty($apiActions)) {
            return false;
        }
        // check foreach action if is complete
        foreach ($activeActions as $action) {
            if (!isset($apiActions[$action['action_id']])) {
                continue;
            }
            if (isset($apiActions[$action['action_id']]->queued)
                && isset($apiActions[$action['action_id']]->processed)
                && isset($apiActions[$action['action_id']]->errors)
            ) {
                if ($apiActions[$action['action_id']]->queued == false) {
                    // order action is waiting to return from the marketplace
                    if ($apiActions[$action['action_id']]->processed == false
                        && empty($apiActions[$action['action_id']]->errors)
                    ) {
                        continue;
                    }
                    // finish action in lengow_action table
                    /** @var Lengow_Connector_Model_Import_Action $lengowAction */
                    $lengowAction = Mage::getModel('lengow/import_action')->load($action['id']);
                    $lengowAction->updateAction(array('state' => self::STATE_FINISH));
                    $orderLengowId = Mage::getModel('lengow/import_order')
                        ->getLengowOrderIdWithOrderId($action['order_id']);
                    // if lengow order not exist do nothing (compatibility v2)
                    if ($orderLengowId) {
                        /** @var Lengow_Connector_Model_Import_Ordererror $orderError */
                        $orderError = Mage::getModel('lengow/import_ordererror');
                        $orderError->finishOrderErrors($orderLengowId, 'send');
                        /** @var Lengow_Connector_Model_Import_Order $orderLengow */
                        $orderLengow = Mage::getModel('lengow/import_order')->load($orderLengowId);
                        if ((bool)$orderLengow->getData('is_in_error')) {
                            $orderLengow->updateOrder(array('is_in_error' => 0));
                        }
                        $processStateFinish = $orderLengow->getOrderProcessState(
                            Lengow_Connector_Model_Import_Order::STATE_CLOSED
                        );
                        if ((int)$orderLengow->getData('order_process_state') !== $processStateFinish) {
                            // if action is accepted -> close order and finish all order actions
                            if ($apiActions[$action['action_id']]->processed == true
                                && empty($apiActions[$action['action_id']]->errors)
                            ) {
                                $orderLengow->updateOrder(array('order_process_state' => $processStateFinish));
                                $this->finishAllActions($action['order_id']);
                            } else {
                                // if action is denied -> create order error
                                $orderError->createOrderError(
                                    array(
                                        'order_lengow_id' => $orderLengowId,
                                        'message' => $apiActions[$action['action_id']]->errors,
                                        'type' => 'send',
                                    )
                                );
                                $orderLengow->updateOrder(array('is_in_error' => 1));
                                $helper->log(
                                    Lengow_Connector_Helper_Data::CODE_ACTION,
                                    $helper->setLogMessage(
                                        'log.order_action.call_action_failed',
                                        array('decoded_message' => $apiActions[$action['action_id']]->errors)
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
        }
        $configHelper->set('last_action_sync', time());
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
                $action = Mage::getModel('lengow/import_action')->load($action['id']);
                $action->updateAction(array('state' => self::STATE_FINISH));
                $orderLengowId = Mage::getModel('lengow/import_order')
                    ->getLengowOrderIdWithOrderId($action['order_id']);
                if ($orderLengowId) {
                    /** @var Lengow_Connector_Model_Import_Order $orderLengow */
                    $orderLengow = Mage::getModel('lengow/import_order')->load($orderLengowId);
                    $processStateFinish = $orderLengow->getOrderProcessState(
                        Lengow_Connector_Model_Import_Order::STATE_CLOSED
                    );
                    if ((int)$orderLengow->getData('order_process_state') !== $processStateFinish
                        && !(bool)$orderLengow->getData('is_in_error')
                    ) {
                        // if action is denied -> create order error
                        $errorMessage = $helper->setLogMessage('lengow_log.exception.action_is_too_old');
                        /** @var Lengow_Connector_Model_Import_Ordererror $orderError */
                        $orderError = Mage::getModel('lengow/import_ordererror');
                        $orderError->createOrderError(
                            array(
                                'order_lengow_id' => $orderLengowId,
                                'message' => $errorMessage,
                                'type' => 'send',
                            )
                        );
                        $orderLengow->updateOrder(array('is_in_error' => 1));
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
            ->addFieldToFilter('state', self::STATE_NEW)
            ->addFieldToFilter(
                'created_at',
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
                $shipment = $unsentOrder['action'] === Lengow_Connector_Model_Import_Action::TYPE_SHIP
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
        $lastActionSynchronisation = Mage::helper('lengow_connector/config')->get('last_action_sync');
        if ($lastActionSynchronisation) {
            $lastIntervalTime = time() - (int)$lastActionSynchronisation;
            $lastIntervalTime = $lastIntervalTime + self::SECURITY_INTERVAL_TIME;
            $intervalTime = $lastIntervalTime > $intervalTime ? $intervalTime : $lastIntervalTime;
        }
        return $intervalTime;
    }
}
