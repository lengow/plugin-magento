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
 * Model import order
 */
class Lengow_Connector_Model_Import_Order extends Mage_Core_Model_Abstract
{
    /**
     * @var integer order process state for new order not imported
     */
    const PROCESS_STATE_NEW = 0;

    /**
     * @var integer order process state for order imported
     */
    const PROCESS_STATE_IMPORT = 1;

    /**
     * @var integer order process state for order finished
     */
    const PROCESS_STATE_FINISH = 2;

    /**
     * @var array $_fieldList field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    protected $_fieldList = array(
        'order_id' => array('required' => false, 'updated' => true),
        'order_sku' => array('required' => false, 'updated' => true),
        'store_id' => array('required' => true, 'updated' => false),
        'feed_id' => array('required' => false, 'updated' => true),
        'delivery_address_id' => array('required' => true, 'updated' => false),
        'delivery_country_iso' => array('required' => false, 'updated' => true),
        'marketplace_sku' => array('required' => true, 'updated' => false),
        'marketplace_name' => array('required' => true, 'updated' => false),
        'marketplace_label' => array('required' => true, 'updated' => false),
        'order_lengow_state' => array('required' => true, 'updated' => true),
        'order_process_state' => array('required' => false, 'updated' => true),
        'order_date' => array('required' => true, 'updated' => false),
        'order_item' => array('required' => false, 'updated' => true),
        'currency' => array('required' => false, 'updated' => true),
        'total_paid' => array('required' => false, 'updated' => true),
        'commission' => array('required' => false, 'updated' => true),
        'customer_name' => array('required' => false, 'updated' => true),
        'customer_email' => array('required' => false, 'updated' => true),
        'carrier' => array('required' => false, 'updated' => true),
        'carrier_method' => array('required' => false, 'updated' => true),
        'carrier_tracking' => array('required' => false, 'updated' => true),
        'carrier_id_relay' => array('required' => false, 'updated' => true),
        'sent_marketplace' => array('required' => false, 'updated' => true),
        'is_in_error' => array('required' => false, 'updated' => true),
        'message' => array('required' => true, 'updated' => true),
        'extra' => array('required' => false, 'updated' => true),
    );

    /**
     * Constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('lengow/import_order');
    }

    /**
     * Create Lengow order
     *
     * @param array $params order parameters
     *
     * @return Lengow_Connector_Model_Import_Order|false
     */
    public function createOrder($params = array())
    {
        foreach ($this->_fieldList as $key => $value) {
            if (!array_key_exists($key, $params) && $value['required']) {
                return false;
            }
        }
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        if (!array_key_exists('order_process_state', $params)) {
            $this->setData('order_process_state', self::PROCESS_STATE_NEW);
        }
        if (!$this->getCreatedAt()) {
            $this->setData('created_at', Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s'));
        }
        try {
            return $this->save();
        } catch (\Exception $e) {
            /** @var Lengow_Connector_Helper_Data $helper */
            $helper = Mage::helper('lengow_connector/data');
            $errorMessage = 'Orm error: "' . $e->getMessage() . '" ' . $e->getFile() . ' line ' . $e->getLine();
            $helper->log(
                'Orm',
                $helper->setLogMessage('log.orm.record_insert_failed', array('error_message' => $errorMessage))
            );
            return false;
        }
    }

    /**
     * Update Lengow order
     *
     * @param array $params order parameters
     *
     * @return Lengow_Connector_Model_Import_Order|false
     */
    public function updateOrder($params = array())
    {
        if (!$this->id) {
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
            /** @var Lengow_Connector_Helper_Data $helper */
            $helper = Mage::helper('lengow_connector/data');
            $errorMessage = 'Orm error: "' . $e->getMessage() . '" ' . $e->getFile() . ' line ' . $e->getLine();
            $helper->log(
                'Orm',
                $helper->setLogMessage('log.orm.record_insert_failed', array('error_message' => $errorMessage))
            );
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
     * if order is already Imported
     *
     * @param string $marketplaceSku marketplace sku
     * @param string $marketplaceName marketplace name
     * @param integer $deliveryAddressId delivery address id
     * @param string $marketplaceLegacy old marketplace name for v2 compatibility
     *
     * @return integer|false
     */
    public function getOrderIdIfExist($marketplaceSku, $marketplaceName, $deliveryAddressId, $marketplaceLegacy)
    {
        // v2 compatibility
        $in = is_null($marketplaceLegacy)
            ? array($marketplaceName)
            : array($marketplaceName, strtolower($marketplaceLegacy));
        // get order id from Magento flat order table
        $results = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToFilter('order_id_lengow', $marketplaceSku)
            ->addAttributeToFilter('marketplace_lengow', array('in' => $in))
            ->addAttributeToFilter('follow_by_lengow', array('eq' => 1))
            ->addAttributeToSelect('entity_id')
            ->addAttributeToSelect('delivery_address_id_lengow')
            ->addAttributeToSelect('feed_id_lengow')
            ->getData();
        if (count($results) > 0) {
            foreach ($results as $result) {
                if ($result['delivery_address_id_lengow'] == 0 && $result['feed_id_lengow'] != 0) {
                    return $result['entity_id'];
                } elseif ($result['delivery_address_id_lengow'] == $deliveryAddressId) {
                    return $result['entity_id'];
                }
            }
        }
        return false;
    }

    /**
     * Check if an order has an error
     *
     * @param string $marketplaceSku marketplace sku
     * @param integer $deliveryAddressId delivery address id
     * @param string $type order error type (import or send)
     *
     * @return array|false
     */
    public function orderIsInError($marketplaceSku, $deliveryAddressId, $type = 'import')
    {
        $orderError = Mage::getModel('lengow/import_ordererror');
        $errorType = $orderError->getOrderErrorType($type);
        // check if log already exists for the given order id
        $results = $orderError->getCollection()
            ->join(
                'lengow/import_order',
                '`lengow/import_order`.id=main_table.order_lengow_id',
                array('marketplace_sku' => 'marketplace_sku', 'delivery_address_id' => 'delivery_address_id')
            )
            ->addFieldToFilter('marketplace_sku', $marketplaceSku)
            ->addFieldToFilter('delivery_address_id', $deliveryAddressId)
            ->addFieldToFilter('type', $errorType)
            ->addFieldToFilter('is_finished', array('eq' => 0))
            ->addFieldToSelect('id')
            ->addFieldToSelect('message')
            ->addFieldToSelect('created_at')
            ->getData();
        if (empty($results)) {
            return false;
        }
        return $results[0];
    }

    /**
     * Get Lengow ID with order ID Magento and delivery address ID
     *
     * @param integer $orderId Magento order id
     * @param string $deliveryAddressId delivery address id
     *
     * @return string|false
     */
    public function getOrderIdWithDeliveryAddress($orderId, $deliveryAddressId)
    {
        // get marketplace_sku from Magento flat order table
        $results = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToFilter('entity_id', $orderId)
            ->addAttributeToFilter('delivery_address_id_lengow', $deliveryAddressId)
            ->addAttributeToFilter('follow_by_lengow', array('eq' => 1))
            ->addAttributeToSelect('order_id_lengow')
            ->getData();
        if (count($results) > 0) {
            return $results[0]['order_id_lengow'];
        }
        return false;
    }

    /**
     * Get order ids from lengow order ID
     *
     * @param string $marketplaceSku marketplace sku
     * @param string $marketplaceName delivery address id
     *
     * @return array|false
     */
    public function getAllOrderIds($marketplaceSku, $marketplaceName)
    {
        $results = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToFilter('order_id_lengow', $marketplaceSku)
            ->addAttributeToFilter('marketplace_lengow', $marketplaceName)
            ->addAttributeToFilter('follow_by_lengow', array('eq' => 1))
            ->addAttributeToSelect('entity_id')
            ->getData();
        if (count($results) > 0) {
            return $results;
        }
        return false;
    }

    /**
     * Get ID record from lengow orders table
     *
     * @param string $marketplaceSku marketplace sku
     * @param integer $deliveryAddressId delivery address id
     *
     * @return integer|false
     */
    public function getLengowOrderId($marketplaceSku, $deliveryAddressId)
    {
        $results = $this->getCollection()
            ->addFieldToFilter('marketplace_sku', $marketplaceSku)
            ->addFieldToFilter('delivery_address_id', $deliveryAddressId)
            ->addFieldToSelect('id')
            ->getData();
        if (count($results) > 0) {
            return (int)$results[0]['id'];
        }
        return false;
    }

    /**
     * Get ID record from lengow orders table with Magento order Id
     *
     * @param integer $orderId Magento order id
     *
     * @return integer|false
     */
    public function getLengowOrderIdWithOrderId($orderId)
    {
        $results = $this->getCollection()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToSelect('id')
            ->getData();
        if (count($results) > 0) {
            return (int)$results[0]['id'];
        }
        return false;
    }


    /**
     * Get all unset orders
     *
     * @return array|false
     */
    public function getUnsentOrders()
    {
        $date = strtotime('-5 days', time());
        // compatibility for version 1.5
        if (Mage::getVersion() < '1.6.0.0') {
            $results = $this->getCollection()
                ->join(
                    'sales/order',
                    'entity_id=main_table.order_id',
                    array(
                        'store_id' => 'store_id',
                        'updated_at' => 'updated_at',
                        'follow_by_lengow' => 'follow_by_lengow',
                        'state' => 'state',
                    )
                )
                ->addFieldToFilter('`sales/order`.updated_at', array('from' => $date, 'datetime' => true))
                ->addFieldToFilter('`sales/order`.follow_by_lengow', array('eq' => 1))
                ->addFieldToFilter('`sales/order`.state', array(array('in' => array('cancel', 'complete'))))
                ->addFieldToFilter('order_process_state', array('eq' => 1))
                ->addFieldToFilter('is_in_error', array('eq' => 0))
                ->getData();
        } else {
            $results = $this->getCollection()
                ->join(
                    array('magento_order' => 'sales/order'),
                    'magento_order.entity_id=main_table.order_id',
                    array(
                        'store_id' => 'store_id',
                        'updated_at' => 'updated_at',
                        'follow_by_lengow' => 'follow_by_lengow',
                        'state' => 'state',
                    )
                )
                ->addFieldToFilter('magento_order.updated_at', array('from' => $date, 'datetime' => true))
                ->addFieldToFilter('magento_order.follow_by_lengow', array('eq' => 1))
                ->addFieldToFilter('magento_order.state', array(array('in' => array('cancel', 'complete'))))
                ->addFieldToFilter('main_table.order_process_state', array('eq' => 1))
                ->addFieldToFilter('main_table.is_in_error', array('eq' => 0))
                ->getData();
        }
        if (count($results) > 0) {
            $unsentOrders = array();
            foreach ($results as $result) {
                if (!Mage::getModel('lengow/import_action')->getActiveActionByOrderId($result['order_id'])) {
                    $unsentOrders[] = array(
                        'order_id' => $result['order_id'],
                        'action' => $result['state'] === 'cancel' ? 'cancel' : 'ship',
                    );
                }
            }
            if (count($unsentOrders) > 0) {
                return $unsentOrders;
            }
        }
        return false;
    }

    /**
     * Re-import order lengow
     *
     * @param integer $orderLengowId Lengow order id
     *
     * @return array|false
     */
    public function reImportOrder($orderLengowId)
    {
        /** @var Lengow_Connector_Model_Import_Order $orderLengow */
        $orderLengow = Mage::getModel('lengow/import_order')->load($orderLengowId);
        if ((int)$orderLengow->getData('order_process_state') === 0 && (bool)$orderLengow->getData('is_in_error')) {
            $params = array(
                'type' => 'manual',
                'order_lengow_id' => $orderLengowId,
                'marketplace_sku' => $orderLengow->getData('marketplace_sku'),
                'marketplace_name' => $orderLengow->getData('marketplace_name'),
                'delivery_address_id' => $orderLengow->getData('delivery_address_id'),
                'store_id' => $orderLengow->getData('store_id'),
            );
            $result = Mage::getModel('lengow/import', $params)->exec();
            return $result;
        }
        return false;
    }

    /**
     * Re-send order lengow
     *
     * @param integer $orderLengowId Lengow order id
     *
     * @return boolean
     */
    public function reSendOrder($orderLengowId)
    {
        $orderLengow = Mage::getModel('lengow/import_order')->load($orderLengowId);
        if ((int)$orderLengow->getData('order_process_state') === 1 && (bool)$orderLengow->getData('is_in_error')) {
            $orderId = $orderLengow->getData('order_id');
            if (!is_null($orderId)) {
                $order = Mage::getModel('sales/order')->load($orderId);
                $action = Mage::getModel('lengow/import_action')->getLastOrderActionType($orderId);
                if (!$action) {
                    $action = $order->getData('status') === 'canceled' ? 'cancel' : 'ship';
                }
                $shipment = $order->getShipmentsCollection()->getFirstItem();
                $result = $this->callAction($action, $order, $shipment);
                return $result;
            }
        }
        return false;
    }

    /**
     * Cancel and re-import order
     *
     * @param Mage_Sales_Model_Order $order Magento order instance
     *
     * @return integer|false
     */
    public function cancelAndReImportOrder($order)
    {
        if (!$this->isReimported($order)) {
            return false;
        }
        $params = array(
            'marketplace_sku' => $order->getData('order_id_lengow'),
            'marketplace_name' => $order->getData('marketplace_lengow'),
            'delivery_address_id' => $order->getData('delivery_address_id_lengow'),
            'store_id' => $order->getData('store_id'),
        );
        try {
            $result = Mage::getModel('lengow/import', $params)->exec();
            if ((isset($result['order_id']) && (int)$result['order_id'] !== (int)$order->getData('order_id'))
                && (isset($result['order_new']) && $result['order_new'])
            ) {
                $order->addData(
                    array(
                        'is_reimported_lengow' => 0,
                        'follow_by_lengow' => 0,
                    )
                );
                // if state != STATE_COMPLETE or != STATE_CLOSED
                $order->setState('lengow_technical_error', 'lengow_technical_error');
                $order->setData('status', 'lengow_technical_error');
                $order->save();
                return (int)$result['order_id'];
            }
            $order->addData(array('is_reimported_lengow' => 0));
            $order->save();
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }

    /**
     * Mark order as is_reimported in sales_flat_order table
     *
     * @param Mage_Sales_Model_Order $order Magento order instance
     *
     * @return boolean
     */
    public function isReimported($order)
    {
        try {
            $order->addData(array('is_reimported_lengow' => 1));
            $order->save();
        } catch (\Exception $e) {
            return false;
        }
        // check success update in BDD
        if ($order->getData('is_reimported_lengow') == 1) {
            return true;
        }
        return false;
    }

    /**
     * Get Magento equivalent to lengow order state
     *
     * @param  string $orderStateLengow Lengow state
     *
     * @return integer
     */
    public function getOrderState($orderStateLengow)
    {
        switch ($orderStateLengow) {
            case 'new':
            case 'waiting_acceptance':
                return Mage_Sales_Model_Order::STATE_NEW;
            case 'accepted':
            case 'waiting_shipment':
                return Mage_Sales_Model_Order::STATE_PROCESSING;
            case 'shipped':
            case 'closed':
                return Mage_Sales_Model_Order::STATE_COMPLETE;
            case 'refused':
            case 'canceled':
                return Mage_Sales_Model_Order::STATE_CANCELED;
        }
    }

    /**
     * Get order process state
     *
     * @param string $state state to be matched
     *
     * @return integer|false
     */
    public function getOrderProcessState($state)
    {
        switch ($state) {
            case 'accepted':
            case 'waiting_shipment':
                return self::PROCESS_STATE_IMPORT;
            case 'shipped':
            case 'closed':
            case 'refused':
            case 'canceled':
            case 'refunded':
                return self::PROCESS_STATE_FINISH;
            default:
                return false;
        }
    }

    /**
     * Create invoice
     *
     * @param Mage_Sales_Model_Order $order Magento order instance
     */
    public function toInvoice($order)
    {
        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
        if ($invoice) {
            $invoice->register();
            $invoice->getOrder()->setIsInProcess(true);
            $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
            $transactionSave->save();
        }
    }

    /**
     * Ship order
     *
     * @param Mage_Sales_Model_Order $order Magento order instance
     * @param string $carrierName carrier name
     * @param string $carrierMethod carrier method
     * @param string $trackingNumber tracking number
     */
    public function toShip($order, $carrierName, $carrierMethod, $trackingNumber)
    {
        if ($order->canShip()) {
            $shipment = Mage::getModel('sales/service_order', $order)->prepareShipment();
            if ($shipment) {
                $shipment->register();
                $shipment->getOrder()->setIsInProcess(true);
                // add tracking information
                if (!is_null($trackingNumber) && $trackingNumber !== '') {
                    $title = $carrierName;
                    if (is_null($title) || $title === 'None') {
                        $title = $carrierMethod;
                    }
                    $track = Mage::getModel('sales/order_shipment_track')
                        ->setNumber($trackingNumber)
                        ->setCarrierCode(Mage_Sales_Model_Order_Shipment_Track::CUSTOM_CARRIER_CODE)
                        ->setTitle($title);
                    $shipment->addTrack($track);
                }
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($shipment)
                    ->addObject($shipment->getOrder());
                $transactionSave->save();
                $shipment->save();
            }
        }
    }

    /**
     * Cancel order
     *
     * @param Mage_Sales_Model_Order $order Magento order instance
     */
    public function toCancel($order)
    {
        if ($order->canCancel()) {
            $order->cancel();
        }
    }

    /**
     * Update order state to marketplace state
     *
     * @param Mage_Sales_Model_Order $order Magento order instance
     * @param string $orderStateLengow lengow order status
     * @param mixed $packageData package data
     * @param mixed $orderLengowId lengow order id or false
     *
     * @return string|false
     */
    public function updateState($order, $orderStateLengow, $packageData, $orderLengowId)
    {
        // finish actions if lengow order is shipped, closed, cancel or refunded
        $orderProcessState = $this->getOrderProcessState($orderStateLengow);
        $trackings = $packageData->delivery->trackings;
        if ($orderProcessState === self::PROCESS_STATE_FINISH) {
            Mage::getModel('lengow/import_action')->finishAllActions($order->getId());
            if ($orderLengowId) {
                Mage::getModel('lengow/import_ordererror')->finishOrderErrors($orderLengowId, 'send');
            }
        }
        // update Lengow order if necessary
        if ($orderLengowId) {
            /** @var Lengow_Connector_Model_Import_Order $orderLengow */
            $orderLengow = Mage::getModel('lengow/import_order')->load($orderLengowId);
            $params = array();
            if ($orderLengow->getData('order_lengow_state') !== $orderStateLengow) {
                $params['order_lengow_state'] = $orderStateLengow;
                $params['carrier_tracking'] = count($trackings) > 0 ? (string)$trackings[0]->number : null;
            }
            if ($orderProcessState === self::PROCESS_STATE_FINISH) {
                if ((int)$orderLengow->getData('order_process_state') !== $orderProcessState) {
                    $params['order_process_state'] = $orderProcessState;
                }
                if ((bool)$orderLengow->getData('is_in_error')) {
                    $params['is_in_error'] = 0;
                }
            }
            if (count($params) > 0) {
                $orderLengow->updateOrder($params);
            }
            unset($orderLengow);
        }
        // update Magento order's status only if in accepted, waiting_shipment, shipped, closed or cancel
        if ($order->getState() !== $this->getOrderState($orderStateLengow) && (bool)$order->getData('from_lengow')) {
            if ($order->getState() === $this->getOrderState('new')
                && ($orderStateLengow === 'accepted' || $orderStateLengow === 'waiting_shipment')
            ) {
                // generate invoice
                $this->toInvoice($order);
                return 'Processing';
            } elseif (($order->getState() === $this->getOrderState('accepted')
                    || $order->getState() === $this->getOrderState('new'))
                && ($orderStateLengow === 'shipped' || $orderStateLengow === 'closed')
            ) {
                // if order is new -> generate invoice
                if ($order->getState() === $this->getOrderState('new')) {
                    $this->toInvoice();
                }
                if (count($trackings) > 0) {
                    $carrierName = !is_null($trackings[0]->carrier) ? (string)$trackings[0]->carrier : null;
                    $carrierMethod = !is_null($trackings[0]->method) ? (string)$trackings[0]->method : null;
                    $trackingNumber = !is_null($trackings[0]->number) ? (string)$trackings[0]->number : null;
                }
                $this->toShip(
                    $order,
                    isset($carrierName) ? $carrierName : null,
                    isset($carrierMethod) ? $carrierMethod : null,
                    isset($trackingNumber) ? $trackingNumber : null
                );
                return 'Complete';
            } else {
                if (($order->getState() === $this->getOrderState('new')
                        || $order->getState() === $this->getOrderState('accepted')
                        || $order->getState() === $this->getOrderState('shipped'))
                    && ($orderStateLengow === 'canceled' || $orderStateLengow === 'refused')
                ) {
                    $this->toCancel($order);
                    return 'Canceled';
                }
            }
        }
        return false;
    }

    /**
     * Synchronize order with Lengow API
     *
     * @param Mage_Sales_Model_Order $order Magento order instance
     * @param Lengow_Connector_Model_Connector|null $connector Lengow Connector for API calls
     *
     * @return boolean
     */
    public function synchronizeOrder($order, $connector = null)
    {
        if (!(bool)$order->getData('from_lengow')) {
            return false;
        }
        /** @var Lengow_Connector_Helper_Config $configHelper */
        $configHelper = Mage::helper('lengow_connector/config');
        list($accountId, $accessToken, $secretToken) = $configHelper->getAccessIds();
        if (is_null($connector)) {
            if ($configHelper->isValidAuth()) {
                /** @var Lengow_Connector_Model_Connector $connector */
                $connector = Mage::getModel('lengow/connector');
                $connector->init($accessToken, $secretToken);
            } else {
                return false;
            }
        }
        $orderIds = $this->getAllOrderIds($order->getData('order_id_lengow'), $order->getData('marketplace_lengow'));
        if ($orderIds) {
            $magentoIds = array();
            foreach ($orderIds as $orderId) {
                $magentoIds[] = $orderId['entity_id'];
            }
            // compatibility V2
            if ($order->getData('feed_id_lengow') != 0) {
                $this->checkAndChangeMarketplaceName($order, $connector);
            }
            $result = $connector->patch(
                '/v3.0/orders/moi/',
                array(
                    'account_id' => $accountId,
                    'marketplace_order_id' => $order->getData('order_id_lengow'),
                    'marketplace' => $order->getData('marketplace_lengow'),
                    'merchant_order_id' => $magentoIds,
                )
            );
            if (is_null($result)
                || (isset($result['detail']) && $result['detail'] === 'Pas trouvé.')
                || isset($result['error'])
            ) {
                return false;
            } else {
                return true;
            }
        }
        return false;
    }

    /**
     * Check and change the name of the marketplace for v3 compatibility
     *
     * @param Mage_Sales_Model_Order $order Magento order instance
     * @param Lengow_Connector_Model_Connector|null $connector Lengow Connector for API calls
     *
     * @return boolean
     */
    public function checkAndChangeMarketplaceName($order, $connector = null)
    {
        if (!(bool)$order->getData('from_lengow')) {
            return false;
        }
        /** @var Lengow_Connector_Helper_Config $configHelper */
        $configHelper = Mage::helper('lengow_connector/config');
        list($accountId, $accessToken, $secretToken) = $configHelper->getAccessIds();
        if (is_null($connector)) {
            if ($configHelper->isValidAuth()) {
                /** @var Lengow_Connector_Model_Connector $connector */
                $connector = Mage::getModel('lengow/connector');
                $connector->init($accessToken, $secretToken);
            } else {
                return false;
            }
        }
        $results = $connector->get(
            '/v3.0/orders',
            array(
                'marketplace_order_id' => $order->getData('order_id_lengow'),
                'marketplace' => $order->getData('marketplace_lengow'),
                'account_id' => $accountId,
            ),
            'stream'
        );
        if (is_null($results)) {
            return false;
        }
        $results = json_decode($results);
        if (isset($results->error)) {
            return false;
        }
        foreach ($results->results as $order) {
            if ($order->getData('marketplace_lengow') !== (string)$order->marketplace) {
                try {
                    $order->setData('marketplace_lengow', (string)$order->marketplace);
                    $order->save();
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        return true;
    }

    /**
     * Send Order action
     *
     * @param string $action Lengow Actions (ship or cancel)
     * @param Mage_Sales_Model_Order $order Magento order instance
     * @param Mage_Sales_Model_Order_Shipment|null $shipment Magento Shipment instance
     *
     * @return boolean
     */
    public function callAction($action, $order, $shipment = null)
    {
        $success = true;
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector');
        $helper->log(
            'API-OrderAction',
            $helper->setLogMessage(
                'log.order_action.try_to_send_action',
                array(
                    'action' => $action,
                    'order_id' => $order->getIncrementId()
                )
            ),
            false,
            $order->getData('order_id_lengow')
        );
        if (!(bool)$order->getData('from_lengow')) {
            $success = false;
        }
        if ($success) {
            $orderLengowId = $this->getLengowOrderIdWithOrderId($order->getId());
            // finish all order errors before API call
            if ($orderLengowId) {
                /** @var Lengow_Connector_Model_Import_Ordererror $orderError */
                $orderError = Mage::getModel('lengow/import_ordererror');
                $orderError->finishOrderErrors($orderLengowId, 'send');
                /** @var Lengow_Connector_Model_Import_Order $orderLengow */
                $orderLengow = Mage::getModel('lengow/import_order')->load($orderLengowId);
                if ($orderLengow->getData('is_in_error') == 1) {
                    $orderLengow->updateOrder(array('is_in_error' => 0));
                }
            }
            try {
                // compatibility V2
                if ((int)$order->getData('feed_id_lengow') !== 0) {
                    $this->checkAndChangeMarketplaceName($order);
                }
                /** @var Lengow_Connector_Model_Import_Marketplace $marketplace */
                $marketplace = Mage::helper('lengow_connector/import')->getMarketplaceSingleton(
                    (string)$order->getData('marketplace_lengow')
                );
                if ($marketplace->containOrderLine($action)) {
                    $orderLineCollection = Mage::getModel('lengow/import_orderline')
                        ->getOrderLineByOrderID($order->getId());
                    // compatibility V2 and security
                    if (!$orderLineCollection) {
                        $orderLineCollection = $this->getOrderLineByApi($order);
                    }
                    if (!$orderLineCollection) {
                        throw new Lengow_Connector_Model_Exception(
                            $helper->setLogMessage('lengow_log.exception.order_line_required')
                        );
                    }
                    $results = array();
                    foreach ($orderLineCollection as $orderLine) {
                        $results[] = $marketplace->callAction($action, $order, $shipment, $orderLine['order_line_id']);
                    }
                    $success = !in_array(false, $results);
                } else {
                    $success = $marketplace->callAction($action, $order, $shipment);
                }
            } catch (Lengow_Connector_Model_Exception $e) {
                $errorMessage = $e->getMessage();
            } catch (Exception $e) {
                $errorMessage = '[Magento error]: "' . $e->getMessage()
                    . '" ' . $e->getFile() . ' line ' . $e->getLine();
            }
            if (isset($errorMessage)) {
                if ($orderLengowId && isset($orderLengow) && isset($orderError)) {
                    if ((int)$orderLengow->getData('order_process_state') !== self::PROCESS_STATE_FINISH) {
                        $orderLengow->updateOrder(array('is_in_error' => 1));
                        $orderError->createOrderError(
                            array(
                                'order_lengow_id' => $orderLengowId,
                                'message' => $errorMessage,
                                'type' => 'send',
                            )
                        );
                    }
                    unset($orderLengow, $orderError);
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
                $success = false;
            }
        }
        if ($success) {
            $message = $helper->setLogMessage(
                'log.order_action.action_send',
                array(
                    'action' => $action,
                    'order_id' => $order->getIncrementId(),
                )
            );
        } else {
            $message = $helper->setLogMessage(
                'log.order_action.action_not_send',
                array(
                    'action' => $action,
                    'order_id' => $order->getIncrementId(),
                )
            );
        }
        $helper->log('API-OrderAction', $message, false, $order->getData('order_id_lengow'));
        return $success;
    }

    /**
     * Get order line by API
     *
     * @param Mage_Sales_Model_Order $order Magento Order
     *
     * @return array|false
     */
    public function getOrderLineByApi($order)
    {
        if (!(bool)$order->getData('from_lengow')) {
            return false;
        }
        $orderLines = array();
        $results = Mage::getModel('lengow/connector')->queryApi(
            'get',
            '/v3.0/orders',
            array(
                'marketplace_order_id' => $order->getData('order_id_lengow'),
                'marketplace' => $order->getData('marketplace_lengow'),
            )
        );
        if (isset($results->count) && $results->count === 0) {
            return false;
        }
        $orderData = $results->results[0];
        foreach ($orderData->packages as $package) {
            $productLines = array();
            foreach ($package->cart as $product) {
                $productLines[] = array('order_line_id' => (string)$product->marketplace_order_line_id);
            }
            if ((int)$order->getData('delivery_address_id_lengow') === 0) {
                return count($productLines) > 0 ? $productLines : false;
            } else {
                $orderLines[(int)$package->delivery->id] = $productLines;
            }
        }
        $return = $orderLines[$order->getData('delivery_address_id_lengow')];
        return count($return) > 0 ? $return : false;
    }

    /**
     * Count order lengow with error
     *
     * @return integer
     */
    public function countOrderWithError()
    {
        $results = $this->getCollection()
            ->addFieldToFilter('is_in_error', 1)
            ->addFieldToSelect('id')
            ->getData();
        return count($results);
    }

    /**
     * Count order lengow to be sent
     *
     * @return integer
     */
    public function countOrderToBeSent()
    {
        $results = $this->getCollection()
            ->addFieldToFilter('order_process_state', 1)
            ->addFieldToSelect('id')
            ->getData();
        return count($results);
    }

    /**
     * Count lengow order imported in Magento
     *
     * @return integer
     */
    public function countOrderImportedByLengow()
    {
        $coreResource = Mage::getSingleton('core/resource');
        $sfo = $coreResource->getTableName('sales_flat_order');
        $connection = $coreResource->getConnection('core_read');
        $query = 'SELECT COUNT(entity_id) as total FROM ' . $sfo . '
            WHERE (' . $sfo . '.from_lengow = 1 AND ' . $sfo . '.follow_by_lengow = 1)';
        $rows = $connection->fetchCol($query);
        return $rows[0];
    }

    /**
     * Count old lengow order
     *
     * @param boolean $isProcessing get only order in processing
     *
     * @return integer
     */
    public function countNotMigrateOrder($isProcessing = true)
    {
        $coreResource = Mage::getSingleton('core/resource');
        $sfo = $coreResource->getTableName('sales_flat_order');
        $lo = $coreResource->getTableName('lengow_order');
        $connection = $coreResource->getConnection('core_read');
        $processing = $isProcessing ? ' AND ' . $sfo . '.state = \'' . $this->getOrderState('accepted') . '\'' : '';
        $query = 'SELECT COUNT(entity_id) as total FROM ' . $sfo . '
            LEFT JOIN ' . $lo . ' AS `lo` ON lo.order_id = ' . $sfo . '.entity_id
            WHERE (' . $sfo . '.from_lengow = 1
            AND ' . $sfo . '.follow_by_lengow = 1 
            AND lo.order_id IS NULL' . $processing . ')';
        $rows = $connection->fetchCol($query);
        if ($rows) {
            return $rows[0];
        } else {
            return 0;
        }
    }

    /**
     * Migrate old order
     *
     * @param boolean $isProcessing migrate only order in processing
     */
    public function migrateOldOrder($isProcessing = true)
    {
        $total = $this->countNotMigrateOrder($isProcessing);
        if ($total > 0) {
            $perPage = 500;
            $nbPage = ceil($total / $perPage);
            for ($i = 1; $i <= $nbPage; $i++) {
                $orderCollection = Mage::getModel('sales/order')->getCollection()
                    ->addAttributeToFilter('from_lengow', 1)
                    ->addAttributeToFilter('follow_by_lengow', 1);
                if ($isProcessing) {
                    $orderCollection->addAttributeToFilter('state', $this->getOrderState('accepted'));
                }
                $orderCollection->getSelect()->limit($perPage, ($i - 1) * $perPage);
                /** @var Mage_Sales_Model_Order[] $orderCollection */
                foreach ($orderCollection as $order) {
                    $oldOrder = Mage::getModel('lengow/import_order')->getCollection()
                        ->addFieldToFilter('order_id', $order->getId())->getFirstItem();
                    if ($oldOrder->getId() > 0) {
                        unset($oldOrder);
                        continue;
                    }
                    // get old Lengow informations
                    $lengowNode = json_decode($order->getXmlNodeLengow());
                    $feedId = isset($lengowNode->idFlux) ? $lengowNode->idFlux : $order->getFeedIdLengow();
                    $marketplaceSku = isset($lengowNode->order_id_lengow)
                        ? $lengowNode->order_id
                        : $order->getOrderIdLengow();
                    $countryIso = isset($lengowNode->delivery_address->delivery_country_iso)
                        ? $lengowNode->delivery_address->delivery_country_iso
                        : '';
                    $marketplaceName = isset($lengowNode->marketplace)
                        ? $lengowNode->marketplace
                        : $order->getMarketplaceLengow();
                    $sendByMarketplace = isset($lengowNode->tracking_informations->tracking_deliveringByMarketPlace)
                        ? (bool)$lengowNode->tracking_informations->tracking_deliveringByMarketPlace
                        : 0;
                    $commission = isset($lengowNode->commission) ? $lengowNode->commission : 0;
                    if (isset($lengowNode->order_purchase_date) && isset($lengowNode->order_purchase_heure)) {
                        $orderDate = $lengowNode->order_purchase_date . ' ' . $lengowNode->order_purchase_heure;
                    } else {
                        $orderDate = $order->getCreatedAt();
                    }
                    if ($countryIso === '') {
                        $address = $order->getShippingAddress();
                        $countryIso = $address->getCountryId();
                    }
                    $orderProcessState = $order->getState() === $this->getOrderState('accepted')
                        ? self::PROCESS_STATE_IMPORT
                        : self::PROCESS_STATE_FINISH;
                    // create new lengow order
                    /** @var Lengow_Connector_Model_Import_Order $newOrder */
                    $newOrder = Mage::getModel('lengow/import_order');
                    $newOrder->createOrder(
                        array(
                            'order_id' => $order->getId(),
                            'order_sku' => $order->getIncrementId(),
                            'store_id' => $order->getStoreId(),
                            'feed_id' => $feedId,
                            'delivery_address_id' => $order->getDeliveryAddressIdLengow(),
                            'delivery_country_iso' => $countryIso,
                            'marketplace_sku' => $marketplaceSku,
                            'marketplace_name' => $marketplaceName,
                            'marketplace_label' => $marketplaceName,
                            'order_lengow_state' => 'waiting_shipment',
                            'order_process_state' => $orderProcessState,
                            'order_date' => $orderDate,
                            'order_item' => $order->getTotalItemCount(),
                            'currency' => $order->getBaseCurrencyCode(),
                            'total_paid' => $order->getTotalInvoiced(),
                            'commission' => $commission,
                            'customer_name' => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname(),
                            'customer_email' => $order->getCustomerEmail(),
                            'carrier' => $order->getCarrierLengow(),
                            'carrier_method' => $order->getCarrierMethodLengow(),
                            'carrier_tracking' => $order->getCarrierTrackingLengow(),
                            'sent_marketplace' => $sendByMarketplace,
                            'created_at' => $order->getCreatedAt(),
                            'updated_at' => $order->getUpdateAt(),
                            'message' => $order->getMessageLengow(),
                            'extra' => $order->getXmlNodeLengow(),
                        )
                    );
                    unset($oldOrder);
                }
            }
        }
    }
}
