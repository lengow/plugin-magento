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
 * Model resource import action
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
     * @var array $_fieldList field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    protected $_fieldList = array(
        'order_id'       => array('required' => true, 'updated' => false),
        'action_id'      => array('required' => true, 'updated' => false),
        'order_line_sku' => array('required' => false, 'updated' => false),
        'action_type'    => array('required' => true, 'updated' => false),
        'retry'          => array('required' => false, 'updated' => true),
        'parameters'     => array('required' => true, 'updated' => false),
        'state'          => array('required' => false, 'updated' => true)
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
        return $this->save();
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
        if ((int)$this->getData('state') != self::STATE_NEW) {
            return false;
        }
        $updatedFields = $this->getUpdatedFields();
        foreach ($params as $key => $value) {
            if (in_array($key, $updatedFields)) {
                $this->setData($key, $value);
            }
        }
        $this->setData('updated_at', Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s'));
        return $this->save();
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
    public function getActiveActionByActionId($actionId)
    {
        $results = $this->getCollection()
            ->addFieldToFilter('action_id', $actionId)
            ->addFieldToFilter('state', self::STATE_NEW)
            ->getData();
        if (count($results) > 0) {
            return (int)$results[0]['id'];
        }
        return false;
    }

    /**
     * Find active actions by order id
     *
     * @param integer $orderId    Magento order id
     * @param string  $actionType action type (ship or cancel)
     *
     * @return array|false
     */
    public function getActiveActionByOrderId($orderId, $actionType = null)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('state', self::STATE_NEW);
        if (!is_null($actionType)) {
            $collection->addFieldToFilter('action_type', $actionType);
        }
        $results = $collection->getData();
        if (count($results) > 0) {
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
        if (count($results) > 0) {
            $lastAction = $results->getLastItem()->getData();
            return (string)$lastAction['action_type'];
        }
        return false;
    }

    /**
     * Find active actions by store
     *
     * @param integer $storeId Magento store id
     *
     * @return array|false
     */
    public function getActiveActionByStore($storeId)
    {
        // Compatibility for version 1.5
        if (Mage::getVersion() < '1.6.0.0') {
            $results = $this->getCollection()
                ->join(
                    'sales/order',
                    'entity_id=main_table.order_id',
                    array('store_id' => 'store_id')
                )
                ->addFieldToFilter('store_id', $storeId)
                ->addFieldToFilter('main_table.state', self::STATE_NEW)
                ->getData();
        } else {
            $results = $this->getCollection()
                ->join(
                    array('magento_order' => 'sales/order'),
                    'magento_order.entity_id=main_table.order_id',
                    array('store_id' => 'store_id')
                )
                ->addFieldToFilter('magento_order.store_id', $storeId)
                ->addFieldToFilter('main_table.state', self::STATE_NEW)
                ->getData();
        }
        if (count($results) > 0) {
            return $results;
        }
        return false;
    }

    /**
     * Removes all actions for one order Magento
     *
     * @param integer $orderId    Magento order id
     * @param string  $actionType action type (null, ship or cancel)
     *
     * @return boolean
     */
    public function finishAllActions($orderId, $actionType = null)
    {
        // get all order action
        $collection = $this->getCollection()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('state', self::STATE_NEW);
        if (!is_null($actionType)) {
            $collection->addFieldToFilter('action_type', $actionType);
        }
        $results = $collection->addFieldToSelect('id')->getData();
        if (count($results) > 0) {
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
     * Remove old actions > 3 days
     *
     * @param string $actionType action type (null, ship or cancel)
     *
     * @return boolean
     */
    public function finishAllOldActions($actionType = null)
    {
        // get all old order action (+ 3 days)
        $collection = $this->getCollection()
            ->addFieldToFilter('state', self::STATE_NEW)
            ->addFieldToFilter(
                'created_at',
                array(
                    'to'       => strtotime('-3 days', time()),
                    'datetime' => true
                )
            );
        if (!is_null($actionType)) {
            $collection->addFieldToFilter('action_type', $actionType);
        }
        $results = $collection->getData();

        if (count($results) > 0) {
            foreach ($results as $result) {
                $action = Mage::getModel('lengow/import_action')->load($result['id']);
                $action->updateAction(array('state' => self::STATE_FINISH));
                $orderLengowId = Mage::getModel('lengow/import_order')
                    ->getLengowOrderIdWithOrderId($result['order_id']);
                if ($orderLengowId) {
                    $helper = Mage::helper('lengow_connector/data');
                    $orderLengow = Mage::getModel('lengow/import_order')->load($orderLengowId);
                    $processStateFinish = $orderLengow->getOrderProcessState('closed');
                    if ((int)$orderLengow->getData('order_process_state') != $processStateFinish
                        && $orderLengow->getData('is_in_error') == 0
                    ) {
                        // If action is denied -> create order error
                        $errorMessage = $helper->setLogMessage('lengow_log.exception.action_is_too_old');
                        $orderError = Mage::getModel('lengow/import_ordererror');
                        $orderError->createOrderError(
                            array(
                                'order_lengow_id' => $orderLengowId,
                                'message'         => $errorMessage,
                                'type'            => 'send',
                            )
                        );
                        $orderLengow->updateOrder(array('is_in_error' => 1));
                        $decodedMessage = $helper->decodeLogMessage($errorMessage, 'en_GB');
                        $helper->log(
                            'API-OrderAction',
                            $helper->setLogMessage(
                                'log.order_action.call_action_failed',
                                array('decoded_message' => $decodedMessage)
                            ),
                            false,
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
     * Check if active actions are finished
     *
     * @return boolean
     */
    public function checkFinishAction()
    {
        $config = Mage::helper('lengow_connector/config');
        $helper = Mage::helper('lengow_connector/data');
        if ((bool)$config->get('preprod_mode_enable')) {
            return false;
        }
        // get all store to check active actions
        $storeCollection = Mage::getResourceModel('core/store_collection')->addFieldToFilter('is_active', 1);
        foreach ($storeCollection as $store) {
            if ($config->get('store_enable', (int)$store->getId())) {
                $helper->log(
                    'API-OrderAction',
                    $helper->setLogMessage(
                        'log.order_action.start_for_store',
                        array(
                            'store_name' => $store->getName(),
                            'store_id'   => $store->getId()
                        )
                    )
                );
                // Get all active actions by store
                $storeActions = $this->getActiveActionByStore((int)$store->getId());
                // If no active action, do nothing
                if (!$storeActions) {
                    continue;
                }
                // Get all actions with API for 3 days
                $page = 1;
                $apiActions = array();
                $connector = Mage::getModel('lengow/connector');
                do {
                    $results = $connector->queryApi(
                        'get',
                        '/v3.0/orders/actions/',
                        (int)$store->getId(),
                        array(
                            'updated_from' => date('c', strtotime(date('Y-m-d').' -3days')),
                            'updated_to'   => date('c'),
                            'page'         => $page
                        )
                    );
                    if (!is_object($results) || isset($results->error)) {
                        break;
                    }
                    // Construct array actions
                    foreach ($results->results as $action) {
                        if (isset($action->id)) {
                            $apiActions[$action->id] = $action;
                        }
                    }
                    $page++;
                } while ($results->next != null);
                if (count($apiActions) == 0) {
                    continue;
                }
                // Check foreach action if is complete
                foreach ($storeActions as $action) {
                    if (!isset($apiActions[$action['action_id']])) {
                        continue;
                    }
                    if (isset($apiActions[$action['action_id']]->queued)
                        && isset($apiActions[$action['action_id']]->processed)
                        && isset($apiActions[$action['action_id']]->errors)
                    ) {
                        if ($apiActions[$action['action_id']]->queued == false) {
                            // Finish action in lengow_action table
                            $lengowAction = Mage::getModel('lengow/import_action')->load($action['id']);
                            $lengowAction->updateAction(array('state' => self::STATE_FINISH));
                            $orderLengowId = Mage::getModel('lengow/import_order')
                                ->getLengowOrderIdWithOrderId($action['order_id']);
                            // if lengow order not exist do nothing (compatibility v2)
                            if ($orderLengowId) {
                                $orderError = Mage::getModel('lengow/import_ordererror');
                                $orderError->finishOrderErrors($orderLengowId, 'send');
                                $orderLengow = Mage::getModel('lengow/import_order')->load($orderLengowId);
                                if ($orderLengow->getData('is_in_error') == 1) {
                                    $orderLengow->updateOrder(array('is_in_error' => 0));
                                }
                                $processStateFinish = $orderLengow->getOrderProcessState('closed');
                                if ((int)$orderLengow->getData('order_process_state') != $processStateFinish) {
                                    // If action is accepted -> close order and finish all order actions
                                    if ($apiActions[$action['action_id']]->processed == true) {
                                        $orderLengow->updateOrder(
                                            array('order_process_state' => $processStateFinish)
                                        );
                                        $this->finishAllActions($action['order_id']);
                                    } else {
                                        // If action is denied -> create order error
                                        $orderError->createOrderError(
                                            array(
                                                'order_lengow_id' => $orderLengowId,
                                                'message'         => $apiActions[$action['action_id']]->errors,
                                                'type'            => 'send',
                                            )
                                        );
                                        $orderLengow->updateOrder(array('is_in_error' => 1));
                                        $helper->log(
                                            'API-OrderAction',
                                            $helper->setLogMessage(
                                                'log.order_action.call_action_failed',
                                                array(
                                                    'decoded_message' => $apiActions[$action['action_id']]->errors
                                                )
                                            ),
                                            false,
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
            }
        }
        // Clean actions after 3 days
        $this->finishAllOldActions();
        return true;
    }

    /**
     * Check if actions are not sent
     *
     * @return boolean
     */
    public function checkActionNotSent()
    {
        $config = Mage::helper('lengow_connector/config');
        $helper = Mage::helper('lengow_connector/data');
        if ((bool)$config->get('preprod_mode_enable')) {
            return false;
        }
        // get all store to check active actions
        $storeCollection = Mage::getResourceModel('core/store_collection')->addFieldToFilter('is_active', 1);
        foreach ($storeCollection as $store) {
            if ($config->get('store_enable', (int)$store->getId())) {
                $helper->log(
                    'API-OrderAction',
                    $helper->setLogMessage(
                        'log.order_action.start_not_sent_for_store',
                        array(
                            'store_name' => $store->getName(),
                            'store_id'   => $store->getId()
                        )
                    )
                );
                // Get unsent orders by store
                $unsentOrders = Mage::getModel('lengow/import_order')->getUnsentOrderByStore((int)$store->getId());
                // If no unsent orders, do nothing
                if (!$unsentOrders) {
                    continue;
                }
                foreach ($unsentOrders as $unsentOrder) {
                    $shipment = null;
                    $order = Mage::getModel('sales/order')->load($unsentOrder['order_id']);
                    if ($unsentOrder['action'] == 'ship') {
                        $shipment = $order->getShipmentsCollection()->getFirstItem();
                    }
                    Mage::getModel('lengow/import_order')->callAction($unsentOrder['action'], $order, $shipment);
                }
            }
        }
        return true;
    }
}
