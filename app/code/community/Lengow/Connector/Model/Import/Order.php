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
     * @var string Lengow order table name
     */
    const TABLE_ORDER = 'lengow_order';

    /* Order fields */
    const FIELD_ID = 'id';
    const FIELD_ORDER_ID = 'order_id';
    const FIELD_ORDER_SKU = 'order_sku';
    const FIELD_STORE_ID = 'store_id';
    const FIELD_FEED_ID = 'feed_id';
    const FIELD_DELIVERY_ADDRESS_ID = 'delivery_address_id';
    const FIELD_DELIVERY_COUNTRY_ISO = 'delivery_country_iso';
    const FIELD_MARKETPLACE_SKU = 'marketplace_sku';
    const FIELD_MARKETPLACE_NAME = 'marketplace_name';
    const FIELD_MARKETPLACE_LABEL = 'marketplace_label';
    const FIELD_ORDER_LENGOW_STATE = 'order_lengow_state';
    const FIELD_ORDER_PROCESS_STATE = 'order_process_state';
    const FIELD_ORDER_DATE = 'order_date';
    const FIELD_ORDER_ITEM = 'order_item';
    const FIELD_ORDER_TYPES = 'order_types';
    const FIELD_CURRENCY = 'currency';
    const FIELD_TOTAL_PAID = 'total_paid';
    const FIELD_COMMISSION = 'commission';
    const FIELD_CUSTOMER_NAME = 'customer_name';
    const FIELD_CUSTOMER_EMAIL = 'customer_email';
    const FIELD_CUSTOMER_VAT_NUMBER = 'customer_vat_number';
    const FIELD_CARRIER = 'carrier';
    const FIELD_CARRIER_METHOD = 'carrier_method';
    const FIELD_CARRIER_TRACKING = 'carrier_tracking';
    const FIELD_CARRIER_RELAY_ID = 'carrier_id_relay';
    const FIELD_SENT_MARKETPLACE = 'sent_marketplace';
    const FIELD_IS_IN_ERROR = 'is_in_error';
    const FIELD_MESSAGE = 'message';
    const FIELD_CREATED_AT = 'created_at';
    const FIELD_UPDATED_AT = 'updated_at';
    const FIELD_EXTRA = 'extra';

    /* Order legacy fields */
    const FIELD_LEGACY_FROM_LENGOW = 'from_lengow';
    const FIELD_LEGACY_FOLLOW_BY_LENGOW = 'follow_by_lengow';
    const FIELD_LEGACY_MARKETPLACE_SKU = 'order_id_lengow';
    const FIELD_LEGACY_MARKETPLACE_NAME = 'marketplace_lengow';
    const FIELD_LEGACY_DELIVERY_ADDRESS_ID = 'delivery_address_id_lengow';
    const FIELD_LEGACY_FEED_ID = 'feed_id_lengow';
    const FIELD_LEGACY_TOTAL_PAID = 'total_paid_lengow';
    const FIELD_LEGACY_COMMISSION = 'fees_lengow';
    const FIELD_LEGACY_CARRIER = 'carrier_lengow';
    const FIELD_LEGACY_CARRIER_METHOD = 'carrier_method_lengow';
    const FIELD_LEGACY_CARRIER_TRACKING = 'carrier_tracking_lengow';
    const FIELD_LEGACY_CARRIER_RELAY_ID = 'carrier_id_relay_lengow';
    const FIELD_LEGACY_IS_REIMPORTED = 'is_reimported_lengow';
    const FIELD_LEGACY_EXTRA = 'xml_node_lengow';

    /* Order process states */
    const PROCESS_STATE_NEW = 0;
    const PROCESS_STATE_IMPORT = 1;
    const PROCESS_STATE_FINISH = 2;

    /* Order states */
    const STATE_NEW = 'new';
    const STATE_WAITING_ACCEPTANCE = 'waiting_acceptance';
    const STATE_ACCEPTED = 'accepted';
    const STATE_WAITING_SHIPMENT = 'waiting_shipment';
    const STATE_SHIPPED = 'shipped';
    const STATE_CLOSED = 'closed';
    const STATE_REFUSED = 'refused';
    const STATE_CANCELED = 'canceled';
    const STATE_REFUNDED = 'refunded';

    /* Order types */
    const TYPE_PRIME = 'is_prime';
    const TYPE_EXPRESS = 'is_express';
    const TYPE_BUSINESS = 'is_business';
    const TYPE_DELIVERED_BY_MARKETPLACE = 'is_delivered_by_marketplace';

    /**
     * @var string label fulfillment for old orders without order type
     */
    const LABEL_FULFILLMENT = 'Fulfillment';

    /**
     * @var array $_fieldList field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    protected $_fieldList = array(
        self::FIELD_ORDER_ID => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => false,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => true,
        ),
        self::FIELD_ORDER_SKU => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => false,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => true,
        ),
        self::FIELD_STORE_ID => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => true,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => false,
        ),
        self::FIELD_FEED_ID => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => false,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => true,
        ),
        self::FIELD_DELIVERY_ADDRESS_ID => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => true,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => false,
        ),
        self::FIELD_DELIVERY_COUNTRY_ISO => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => false,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => true,
        ),
        self::FIELD_MARKETPLACE_SKU => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => true,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => false,
        ),
        self::FIELD_MARKETPLACE_NAME => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => true,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => false,
        ),
        self::FIELD_MARKETPLACE_LABEL => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => true,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => false,
        ),
        self::FIELD_ORDER_LENGOW_STATE => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => true,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => true,
        ),
        self::FIELD_ORDER_PROCESS_STATE => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => false,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => true,
        ),
        self::FIELD_ORDER_DATE => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => true,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => false,
        ),
        self::FIELD_ORDER_ITEM => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => false,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => true,
        ),
        self::FIELD_ORDER_TYPES => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => true,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => false,
        ),
        self::FIELD_CURRENCY => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => false,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => true,
        ),
        self::FIELD_TOTAL_PAID => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => false,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => true,
        ),
        self::FIELD_COMMISSION => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => false,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => true,
        ),
        self::FIELD_CUSTOMER_NAME => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => false,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => true,
        ),
        self::FIELD_CUSTOMER_EMAIL => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => false,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => true,
        ),
        self::FIELD_CUSTOMER_VAT_NUMBER => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => false,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => false,
        ),
        self::FIELD_CARRIER => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => false,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => true,
        ),
        self::FIELD_CARRIER_METHOD => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => false,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => true,
        ),
        self::FIELD_CARRIER_TRACKING => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => false,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => true,
        ),
        self::FIELD_CARRIER_RELAY_ID => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => false,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => true,
        ),
        self::FIELD_SENT_MARKETPLACE => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => false,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => true,
        ),
        self::FIELD_IS_IN_ERROR => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => false,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => true,
        ),
        self::FIELD_MESSAGE => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => true,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => true,
        ),
        self::FIELD_EXTRA => array(
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
            if (!array_key_exists($key, $params) && $value[Lengow_Connector_Helper_Data::FIELD_REQUIRED]) {
                return false;
            }
        }
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        if (!array_key_exists(self::FIELD_ORDER_PROCESS_STATE, $params)) {
            $this->setData(self::FIELD_ORDER_PROCESS_STATE, self::PROCESS_STATE_NEW);
        }
        if (!$this->getCreatedAt()) {
            $this->setData(
                self::FIELD_CREATED_AT,
                Mage::getModel('core/date')->gmtDate(Lengow_Connector_Helper_Data::DATE_FULL)
            );
        }
        try {
            return $this->save();
        } catch (\Exception $e) {
            /** @var Lengow_Connector_Helper_Data $helper */
            $helper = Mage::helper('lengow_connector/data');
            $errorMessage = '[Orm error]: "' . $e->getMessage() . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
            $helper->log(
                Lengow_Connector_Helper_Data::CODE_ORM,
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
            /** @var Lengow_Connector_Helper_Data $helper */
            $helper = Mage::helper('lengow_connector/data');
            $errorMessage = '[Orm error]: "' . $e->getMessage() . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
            $helper->log(
                Lengow_Connector_Helper_Data::CODE_ORM,
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
            if ($value[Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED]) {
                $updatedFields[] = $key;
            }
        }
        return $updatedFields;
    }

    /**
     * Check if order is express
     *
     * @return boolean
     */
    public function isExpress()
    {
        $orderTypes = (string) $this->getData(self::FIELD_ORDER_TYPES);
        $orderTypes = $orderTypes !== '' ? json_decode($orderTypes, true) : array();
        return isset($orderTypes[self::TYPE_EXPRESS]) || isset($orderTypes[self::TYPE_PRIME]);
    }

    /**
     * Check if order is B2B
     *
     * @return boolean
     */
    public function isBusiness()
    {
        $orderTypes = (string) $this->getData(self::FIELD_ORDER_TYPES);
        $orderTypes = $orderTypes !== '' ? json_decode($orderTypes, true) : array();
        return isset($orderTypes[self::TYPE_BUSINESS]);
    }

    /**
     * Check if order is delivered by marketplace
     *
     * @return boolean
     */
    public function isDeliveredByMarketplace()
    {
        $orderTypes = (string) $this->getData(self::FIELD_ORDER_TYPES);
        $orderTypes = $orderTypes !== '' ? json_decode($orderTypes, true) : array();
        return isset($orderTypes[self::TYPE_DELIVERED_BY_MARKETPLACE])
            || (bool) $this->getData(self::FIELD_SENT_MARKETPLACE);
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
        $in = $marketplaceLegacy === null
            ? array($marketplaceName)
            : array($marketplaceName, strtolower($marketplaceLegacy));
        // get order id from Magento flat order table
        $results = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToFilter(self::FIELD_LEGACY_MARKETPLACE_SKU, $marketplaceSku)
            ->addAttributeToFilter(self::FIELD_LEGACY_MARKETPLACE_NAME, array('in' => $in))
            ->addAttributeToFilter(self::FIELD_LEGACY_FOLLOW_BY_LENGOW, array('eq' => 1))
            ->addAttributeToSelect('entity_id')
            ->addAttributeToSelect(self::FIELD_LEGACY_DELIVERY_ADDRESS_ID)
            ->addAttributeToSelect(self::FIELD_LEGACY_FEED_ID)
            ->getData();
        if (!empty($results)) {
            foreach ($results as $result) {
                if ($result[self::FIELD_LEGACY_DELIVERY_ADDRESS_ID] == 0 && $result[self::FIELD_LEGACY_FEED_ID] != 0) {
                    return $result['entity_id'];
                }
                if ($result[self::FIELD_LEGACY_DELIVERY_ADDRESS_ID] == $deliveryAddressId) {
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
     * @param integer $type order error type (import or send)
     *
     * @return array|false
     */
    public function orderIsInError(
        $marketplaceSku,
        $deliveryAddressId,
        $type = Lengow_Connector_Model_Import_Ordererror::TYPE_ERROR_IMPORT
    ) {
        // check if log already exists for the given order id
        $results = Mage::getModel('lengow/import_ordererror')->getCollection()
            ->join(
                'lengow/import_order',
                '`lengow/import_order`.id=main_table.order_lengow_id',
                array(
                    self::FIELD_MARKETPLACE_SKU => self::FIELD_MARKETPLACE_SKU,
                    self::FIELD_DELIVERY_ADDRESS_ID => self::FIELD_DELIVERY_ADDRESS_ID,
                )
            )
            ->addFieldToFilter(self::FIELD_MARKETPLACE_SKU, $marketplaceSku)
            ->addFieldToFilter(self::FIELD_DELIVERY_ADDRESS_ID, $deliveryAddressId)
            ->addFieldToFilter(Lengow_Connector_Model_Import_Ordererror::FIELD_TYPE, $type)
            ->addFieldToFilter(Lengow_Connector_Model_Import_Ordererror::FIELD_IS_FINISHED, array('eq' => 0))
            ->addFieldToSelect(Lengow_Connector_Model_Import_Ordererror::FIELD_ID)
            ->addFieldToSelect(Lengow_Connector_Model_Import_Ordererror::FIELD_MESSAGE)
            ->addFieldToSelect(Lengow_Connector_Model_Import_Ordererror::FIELD_CREATED_AT)
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
            ->addAttributeToFilter(self::FIELD_LEGACY_DELIVERY_ADDRESS_ID, $deliveryAddressId)
            ->addAttributeToFilter(self::FIELD_LEGACY_FOLLOW_BY_LENGOW, array('eq' => 1))
            ->addAttributeToSelect(self::FIELD_LEGACY_MARKETPLACE_SKU)
            ->getData();
        if (!empty($results)) {
            return $results[0][self::FIELD_LEGACY_MARKETPLACE_SKU];
        }
        return false;
    }

    /**
     * Get order ids from lengow order ID
     *
     * @param string $marketplaceSku marketplace sku
     * @param string $marketplaceName marketplace name
     *
     * @return array|false
     */
    public function getAllOrderIds($marketplaceSku, $marketplaceName)
    {
        $results = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToFilter(self::FIELD_LEGACY_MARKETPLACE_SKU, $marketplaceSku)
            ->addAttributeToFilter(self::FIELD_LEGACY_MARKETPLACE_NAME, $marketplaceName)
            ->addAttributeToFilter(self::FIELD_LEGACY_FOLLOW_BY_LENGOW, array('eq' => 1))
            ->addAttributeToSelect('entity_id')
            ->getData();
        if (!empty($results)) {
            return $results;
        }
        return false;
    }

    /**
     * Get ID record from lengow orders table
     *
     * @param string $marketplaceSku marketplace sku
     * @param string $marketplaceName marketplace name
     * @param integer $deliveryAddressId delivery address id
     *
     * @return integer|false
     */
    public function getLengowOrderId($marketplaceSku, $marketplaceName, $deliveryAddressId)
    {
        $results = $this->getCollection()
            ->addFieldToFilter(self::FIELD_MARKETPLACE_SKU, $marketplaceSku)
            ->addFieldToFilter(self::FIELD_MARKETPLACE_NAME, $marketplaceName)
            ->addFieldToFilter(self::FIELD_DELIVERY_ADDRESS_ID, $deliveryAddressId)
            ->addFieldToSelect(self::FIELD_ID)
            ->getData();
        if (!empty($results)) {
            return (int) $results[0][self::FIELD_ID];
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
            ->addFieldToFilter(self::FIELD_ORDER_ID, $orderId)
            ->addFieldToSelect(self::FIELD_ID)
            ->getData();
        if (!empty($results)) {
            return (int) $results[0][self::FIELD_ID];
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
        $results = $this->getCollection()
            ->join(
                array('magento_order' => 'sales/order'),
                'magento_order.entity_id=main_table.order_id',
                array(
                    'store_id' => 'store_id',
                    'updated_at' => 'updated_at',
                    self::FIELD_LEGACY_FOLLOW_BY_LENGOW => self::FIELD_LEGACY_FOLLOW_BY_LENGOW,
                    'state' => 'state',
                )
            )
            ->addFieldToFilter('magento_order.updated_at', array('from' => $date, 'datetime' => true))
            ->addFieldToFilter('magento_order.follow_by_lengow', array('eq' => 1))
            ->addFieldToFilter(
                'magento_order.state',
                array(
                    array('in' =>
                        array(
                            Mage_Sales_Model_Order::STATE_CANCELED,
                            Mage_Sales_Model_Order::STATE_COMPLETE,
                        )
                    )
                )
            )
            ->addFieldToFilter('main_table.order_process_state', array('eq' => 1))
            ->addFieldToFilter('main_table.is_in_error', array('eq' => 0))
            ->getData();
        if (!empty($results)) {
            $unsentOrders = array();
            foreach ($results as $result) {
                if (!Mage::getModel('lengow/import_action')->getActiveActionByOrderId($result[self::FIELD_ORDER_ID])) {
                    $unsentOrders[] = array(
                        'order_id' => $result[self::FIELD_ORDER_ID],
                        'action' => $result['state'] === Mage_Sales_Model_Order::STATE_CANCELED
                            ? Lengow_Connector_Model_Import_Action::TYPE_CANCEL
                            : Lengow_Connector_Model_Import_Action::TYPE_SHIP,
                    );
                }
            }
            if (!empty($unsentOrders)) {
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
        if ((int) $orderLengow->getData(self::FIELD_ORDER_PROCESS_STATE) === 0
            && (bool) $orderLengow->getData(self::FIELD_IS_IN_ERROR)
        ) {
            $params = array(
                Lengow_Connector_Model_Import::PARAM_TYPE => Lengow_Connector_Model_Import::TYPE_MANUAL,
                Lengow_Connector_Model_Import::PARAM_ORDER_LENGOW_ID => $orderLengowId,
                Lengow_Connector_Model_Import::PARAM_MARKETPLACE_SKU => $orderLengow->getData(
                    self::FIELD_MARKETPLACE_SKU
                ),
                Lengow_Connector_Model_Import::PARAM_MARKETPLACE_NAME => $orderLengow->getData(
                    self::FIELD_MARKETPLACE_NAME
                ),
                Lengow_Connector_Model_Import::PARAM_DELIVERY_ADDRESS_ID => $orderLengow->getData(
                    self::FIELD_DELIVERY_ADDRESS_ID
                ),
                Lengow_Connector_Model_Import::PARAM_STORE_ID => $orderLengow->getData(self::FIELD_STORE_ID),
            );
            return Mage::getModel('lengow/import', $params)->exec();
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
        if ((int) $orderLengow->getData(self::FIELD_ORDER_PROCESS_STATE) === 1
            && (bool) $orderLengow->getData(self::FIELD_IS_IN_ERROR)
        ) {
            $orderId = $orderLengow->getData(self::FIELD_ORDER_ID);
            if ($orderId !== null) {
                $order = Mage::getModel('sales/order')->load($orderId);
                $action = Mage::getModel('lengow/import_action')->getLastOrderActionType($orderId);
                if (!$action) {
                    $action = $order->getData('status') === Mage_Sales_Model_Order::STATE_CANCELED
                        ? Lengow_Connector_Model_Import_Action::TYPE_CANCEL
                        : Lengow_Connector_Model_Import_Action::TYPE_SHIP;
                }
                $shipment = $order->getShipmentsCollection()->getFirstItem();
                return $this->callAction($action, $order, $shipment);
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
            Lengow_Connector_Model_Import::PARAM_MARKETPLACE_SKU => $order->getData(
                self::FIELD_LEGACY_MARKETPLACE_SKU
            ),
            Lengow_Connector_Model_Import::PARAM_MARKETPLACE_NAME => $order->getData(
                self::FIELD_LEGACY_MARKETPLACE_NAME
            ),
            Lengow_Connector_Model_Import::PARAM_DELIVERY_ADDRESS_ID => $order->getData(
                self::FIELD_LEGACY_DELIVERY_ADDRESS_ID
            ),
            Lengow_Connector_Model_Import::PARAM_STORE_ID => $order->getData('store_id'),
        );
        $result = Mage::getModel('lengow/import', $params)->exec();
        if (!empty($result[Lengow_Connector_Model_Import::ORDERS_CREATED])) {
            $orderCreated = $result[Lengow_Connector_Model_Import::ORDERS_CREATED][0];
            $orderId = $orderCreated[Lengow_Connector_Model_Import_Importorder::MERCHANT_ORDER_ID];
            if ($orderId !== (int) $order->getData('order_id')) {
                try {
                    $order->addData(
                        array(
                            self::FIELD_LEGACY_IS_REIMPORTED => 0,
                            self::FIELD_LEGACY_FOLLOW_BY_LENGOW => 0,
                        )
                    );
                    // if state != STATE_COMPLETE or != STATE_CLOSED
                    $order->setState(
                        Lengow_Connector_Helper_Data::LENGOW_TECHNICAL_ERROR_STATE,
                        Lengow_Connector_Helper_Data::LENGOW_TECHNICAL_ERROR_STATE
                    );
                    $order->setData('status', Lengow_Connector_Helper_Data::LENGOW_TECHNICAL_ERROR_STATE);
                    $order->save();
                } catch (\Exception $e) {
                    $helper = Mage::helper('lengow_connector/data');
                    $errorMessage = '[Orm error]: "' . $e->getMessage()
                        . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
                    $helper->log(
                        Lengow_Connector_Helper_Data::CODE_ORM,
                        $helper->setLogMessage('log.orm.record_insert_failed', array('error_message' => $errorMessage))
                    );
                }
                return $orderId;
            }
        }
        $order->addData(
            array(
                self::FIELD_LEGACY_IS_REIMPORTED => 0,
                self::FIELD_LEGACY_FOLLOW_BY_LENGOW => 1,
            )
        );
        $order->save();
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
            $order->addData(
                array(
                    self::FIELD_LEGACY_IS_REIMPORTED => 1,
                    self::FIELD_LEGACY_FOLLOW_BY_LENGOW => 0,
                )
            );
            $order->save();
        } catch (\Exception $e) {
            return false;
        }
        // check success update in BDD
        if ((bool) $order->getData(self::FIELD_LEGACY_IS_REIMPORTED)) {
            return true;
        }
        return false;
    }

    /**
     * Get Magento equivalent to lengow order state
     *
     * @param string $orderStateLengow Lengow state
     *
     * @return integer
     */
    public function getOrderState($orderStateLengow)
    {
        switch ($orderStateLengow) {
            case self::STATE_NEW:
            case self::STATE_WAITING_ACCEPTANCE:
                return Mage_Sales_Model_Order::STATE_NEW;
            case self::STATE_ACCEPTED:
            case self::STATE_WAITING_SHIPMENT:
                return Mage_Sales_Model_Order::STATE_PROCESSING;
            case self::STATE_SHIPPED:
            case self::STATE_CLOSED:
                return Mage_Sales_Model_Order::STATE_COMPLETE;
            case self::STATE_REFUSED:
            case self::STATE_CANCELED:
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
            case self::STATE_ACCEPTED:
            case self::STATE_WAITING_SHIPMENT:
                return self::PROCESS_STATE_IMPORT;
            case self::STATE_SHIPPED:
            case self::STATE_CLOSED:
            case self::STATE_REFUSED:
            case self::STATE_CANCELED:
            case self::STATE_REFUNDED:
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
                if ($trackingNumber !== null && $trackingNumber !== '') {
                    $title = $carrierName;
                    if ($title === null || $title === 'None') {
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
                Mage::getModel('lengow/import_ordererror')->finishOrderErrors(
                    $orderLengowId,
                    Lengow_Connector_Model_Import_Ordererror::TYPE_ERROR_SEND
                );
            }
        }
        // update Lengow order if necessary
        if ($orderLengowId) {
            /** @var Lengow_Connector_Model_Import_Order $orderLengow */
            $orderLengow = Mage::getModel('lengow/import_order')->load($orderLengowId);
            $params = array();
            if ($orderLengow->getData(self::FIELD_ORDER_LENGOW_STATE) !== $orderStateLengow) {
                $params[self::FIELD_ORDER_LENGOW_STATE] = $orderStateLengow;
                $params[self::FIELD_CARRIER_TRACKING] = !empty($trackings) ? (string) $trackings[0]->number : null;
            }
            if ($orderProcessState === self::PROCESS_STATE_FINISH) {
                if ((int)$orderLengow->getData(self::FIELD_ORDER_PROCESS_STATE) !== $orderProcessState) {
                    $params[self::FIELD_ORDER_PROCESS_STATE] = $orderProcessState;
                }
                if ((bool) $orderLengow->getData(self::FIELD_IS_IN_ERROR)) {
                    $params[self::FIELD_IS_IN_ERROR] = 0;
                }
            }
            if (!empty($params)) {
                $orderLengow->updateOrder($params);
            }
            unset($orderLengow);
        }
        // update Magento order's status only if in accepted, waiting_shipment, shipped, closed or cancel
        if ((bool) $order->getData(self::FIELD_LEGACY_FROM_LENGOW)
            && $order->getState() !== $this->getOrderState($orderStateLengow)
        ) {
            if (($orderStateLengow === self::STATE_ACCEPTED || $orderStateLengow === self::STATE_WAITING_SHIPMENT)
                && $order->getState() === $this->getOrderState(self::STATE_NEW)
            ) {
                // generate invoice
                $this->toInvoice($order);
                return Mage_Sales_Model_Order::STATE_PROCESSING;
            }
            if (($orderStateLengow === self::STATE_SHIPPED || $orderStateLengow === self::STATE_CLOSED)
                && ($order->getState() === $this->getOrderState(self::STATE_ACCEPTED)
                    || $order->getState() === $this->getOrderState(self::STATE_NEW)
                )
            ) {
                // if order is new -> generate invoice
                if ($order->getState() === $this->getOrderState(self::STATE_NEW)) {
                    $this->toInvoice($order);
                }
                if (!empty($trackings)) {
                    $tracking = $trackings[0];
                    $carrierName = $tracking->carrier;
                    $carrierMethod = $tracking->method;
                    $trackingNumber = $tracking->number;
                }
                $this->toShip(
                    $order,
                    isset($carrierName) ? $carrierName : null,
                    isset($carrierMethod) ? $carrierMethod : null,
                    isset($trackingNumber) ? $trackingNumber : null
                );
                return Mage_Sales_Model_Order::STATE_COMPLETE;
            }
            if (($orderStateLengow === self::STATE_CANCELED || $orderStateLengow === self::STATE_REFUSED)
                && ($order->getState() === $this->getOrderState(self::STATE_NEW)
                    || $order->getState() === $this->getOrderState(self::STATE_ACCEPTED)
                    || $order->getState() === $this->getOrderState(self::STATE_SHIPPED)
                )
            ) {
                $this->toCancel($order);
                return Mage_Sales_Model_Order::STATE_CANCELED;
            }
        }
        return false;
    }

    /**
     * Synchronize order with Lengow API
     *
     * @param Mage_Sales_Model_Order $order Magento order instance
     * @param Lengow_Connector_Model_Connector|null $connector Lengow Connector for API calls
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public function synchronizeOrder($order, $connector = null, $logOutput = false)
    {
        if (!(bool) $order->getData(self::FIELD_LEGACY_FROM_LENGOW)) {
            return false;
        }
        /** @var Lengow_Connector_Helper_Config $configHelper */
        $configHelper = Mage::helper('lengow_connector/config');
        list($accountId, $accessToken, $secretToken) = $configHelper->getAccessIds();
        if ($connector === null) {
            /** @var Lengow_Connector_Model_Connector $connector */
            $connector = Mage::getModel('lengow/connector');
            if ($connector->isValidAuth($logOutput)) {
                $connector->init($accessToken, $secretToken);
            } else {
                return false;
            }
        }
        $orderIds = $this->getAllOrderIds(
            $order->getData(self::FIELD_LEGACY_MARKETPLACE_SKU),
            $order->getData(self::FIELD_LEGACY_MARKETPLACE_NAME)
        );
        if ($orderIds) {
            $magentoIds = array();
            foreach ($orderIds as $orderId) {
                $magentoIds[] = $orderId['entity_id'];
            }
            // compatibility V2
            if ($order->getData(self::FIELD_LEGACY_FEED_ID) != 0) {
                $this->checkAndChangeMarketplaceName($order, $connector, $logOutput);
            }
            $body = array(
                Lengow_Connector_Model_Import::ARG_ACCOUNT_ID => $accountId,
                Lengow_Connector_Model_Import::ARG_MARKETPLACE_ORDER_ID => $order->getData(
                    self::FIELD_LEGACY_MARKETPLACE_SKU
                ),
                Lengow_Connector_Model_Import::ARG_MARKETPLACE => $order->getData(
                    self::FIELD_LEGACY_MARKETPLACE_NAME
                ),
                Lengow_Connector_Model_Import::ARG_MERCHANT_ORDER_ID => $magentoIds,
            );
            try {
                $result = $connector->patch(
                    Lengow_Connector_Model_Connector::API_ORDER_MOI,
                    array(),
                    Lengow_Connector_Model_Connector::FORMAT_JSON,
                    Mage::helper('core')->jsonEncode($body),
                    $logOutput
                );
            } catch (Exception $e) {
                /** @var Lengow_Connector_Helper_Data $helper */
                $helper = Mage::helper('lengow_connector');
                $message = $helper->decodeLogMessage(
                    $e->getMessage(),
                    Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE
                );
                $error = $helper->setLogMessage(
                    'log.connector.error_api',
                    array(
                        'error_code' => $e->getCode(),
                        'error_message' => $message,
                    )
                );
                $helper->log(Lengow_Connector_Helper_Data::CODE_CONNECTOR, $error, $logOutput);
                return false;
            }
            if ($result === null
                || (isset($result['detail']) && $result['detail'] === 'Pas trouvé.')
                || isset($result['error'])
            ) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Check and change the name of the marketplace for v3 compatibility
     *
     * @param Mage_Sales_Model_Order $order Magento order instance
     * @param Lengow_Connector_Model_Connector|null $connector Lengow Connector for API calls
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public function checkAndChangeMarketplaceName($order, $connector = null, $logOutput = false)
    {
        if (!(bool) $order->getData(self::FIELD_LEGACY_FROM_LENGOW)) {
            return false;
        }
        /** @var Lengow_Connector_Helper_Config $configHelper */
        $configHelper = Mage::helper('lengow_connector/config');
        list($accountId, $accessToken, $secretToken) = $configHelper->getAccessIds();
        if ($connector === null) {
            /** @var Lengow_Connector_Model_Connector $connector */
            $connector = Mage::getModel('lengow/connector');
            if ($connector->isValidAuth($logOutput)) {
                $connector->init($accessToken, $secretToken);
            } else {
                return false;
            }
        }
        try {
            $results = $connector->get(
                Lengow_Connector_Model_Connector::API_ORDER,
                array(
                    Lengow_Connector_Model_Import::ARG_MARKETPLACE_ORDER_ID => $order->getData(
                        self::FIELD_LEGACY_MARKETPLACE_SKU
                    ),
                    Lengow_Connector_Model_Import::ARG_MARKETPLACE => $order->getData(
                        self::FIELD_LEGACY_MARKETPLACE_NAME
                    ),
                    Lengow_Connector_Model_Import::ARG_ACCOUNT_ID => $accountId,
                ),
                Lengow_Connector_Model_Connector::FORMAT_STREAM,
                '',
                $logOutput
            );
        } catch (Exception $e) {
            /** @var Lengow_Connector_Helper_Data $helper */
            $helper = Mage::helper('lengow_connector');
            $message = $helper->decodeLogMessage(
                $e->getMessage(),
                Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE
            );
            $error = $helper->setLogMessage(
                'log.connector.error_api',
                array(
                    'error_code' => $e->getCode(),
                    'error_message' => $message,
                )
            );
            $helper->log(Lengow_Connector_Helper_Data::CODE_CONNECTOR, $error, $logOutput);
            return false;
        }
        if ($results === null) {
            return false;
        }
        $results = json_decode($results);
        if (isset($results->error)) {
            return false;
        }
        foreach ($results->results as $result) {
            if ($order->getData(self::FIELD_LEGACY_MARKETPLACE_NAME) !== (string) $result->marketplace) {
                try {
                    $order->setData(self::FIELD_LEGACY_MARKETPLACE_NAME, (string) $result->marketplace);
                    $order->save();
                } catch (Exception $e) {
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
            Lengow_Connector_Helper_Data::CODE_ACTION,
            $helper->setLogMessage(
                'log.order_action.try_to_send_action',
                array(
                    'action' => $action,
                    'order_id' => $order->getIncrementId()
                )
            ),
            false,
            $order->getData(self::FIELD_LEGACY_MARKETPLACE_SKU)
        );
        if (!(bool) $order->getData(self::FIELD_LEGACY_FROM_LENGOW)) {
            $success = false;
        }
        if ($success) {
            $orderLengowId = $this->getLengowOrderIdWithOrderId($order->getId());
            // finish all order errors before API call
            if ($orderLengowId) {
                /** @var Lengow_Connector_Model_Import_Ordererror $orderError */
                $orderError = Mage::getModel('lengow/import_ordererror');
                $orderError->finishOrderErrors(
                    $orderLengowId,
                    Lengow_Connector_Model_Import_Ordererror::TYPE_ERROR_SEND
                );
                /** @var Lengow_Connector_Model_Import_Order $orderLengow */
                $orderLengow = Mage::getModel('lengow/import_order')->load($orderLengowId);
                if ((bool) $orderLengow->getData(self::FIELD_IS_IN_ERROR)) {
                    $orderLengow->updateOrder(array(self::FIELD_IS_IN_ERROR => 0));
                }
            }
            try {
                // compatibility V2
                if ((int) $order->getData(self::FIELD_LEGACY_FEED_ID) !== 0) {
                    $this->checkAndChangeMarketplaceName($order);
                }
                /** @var Lengow_Connector_Model_Import_Marketplace $marketplace */
                $marketplace = Mage::helper('lengow_connector/import')->getMarketplaceSingleton(
                    (string) $order->getData(self::FIELD_LEGACY_MARKETPLACE_NAME)
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
                        $results[] = $marketplace->callAction(
                            $action,
                            $order,
                            $shipment,
                            $orderLine[Lengow_Connector_Model_Import_Orderline::FIELD_ORDER_LINE_ID]
                        );
                    }
                    $success = !in_array(false, $results, true);
                } else {
                    $success = $marketplace->callAction($action, $order, $shipment);
                }
            } catch (Lengow_Connector_Model_Exception $e) {
                $errorMessage = $e->getMessage();
            } catch (Exception $e) {
                $errorMessage = '[Magento error]: "' . $e->getMessage()
                    . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
            }
            if (isset($errorMessage)) {
                if (isset($orderLengow, $orderError) && $orderLengowId) {
                    if ((int) $orderLengow->getData(self::FIELD_ORDER_PROCESS_STATE) !== self::PROCESS_STATE_FINISH) {
                        $orderLengow->updateOrder(array(self::FIELD_IS_IN_ERROR => 1));
                        $orderError->createOrderError(
                            array(
                                Lengow_Connector_Model_Import_Ordererror::FIELD_ORDER_LENGOW_ID => $orderLengowId,
                                Lengow_Connector_Model_Import_Ordererror::FIELD_MESSAGE => $errorMessage,
                                Lengow_Connector_Model_Import_Ordererror::FIELD_TYPE =>
                                    Lengow_Connector_Model_Import_Ordererror::TYPE_ERROR_SEND,
                            )
                        );
                    }
                    unset($orderLengow, $orderError);
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
                    $order->getData(self::FIELD_LEGACY_MARKETPLACE_SKU)
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
        $helper->log(
            Lengow_Connector_Helper_Data::CODE_ACTION,
            $message,
            false,
            $order->getData(self::FIELD_LEGACY_MARKETPLACE_SKU)
        );
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
        if (!(bool) $order->getData(self::FIELD_LEGACY_FROM_LENGOW)) {
            return false;
        }
        $orderLines = array();
        $results = Mage::getModel('lengow/connector')->queryApi(
            Lengow_Connector_Model_Connector::GET,
            Lengow_Connector_Model_Connector::API_ORDER,
            array(
                Lengow_Connector_Model_Import::ARG_MARKETPLACE_ORDER_ID => $order->getData(
                    self::FIELD_LEGACY_MARKETPLACE_SKU
                ),
                Lengow_Connector_Model_Import::ARG_MARKETPLACE => $order->getData(
                    self::FIELD_LEGACY_MARKETPLACE_NAME
                ),
            )
        );
        if (isset($results->count) && $results->count === 0) {
            return false;
        }
        $orderData = $results->results[0];
        foreach ($orderData->packages as $package) {
            $productLines = array();
            foreach ($package->cart as $product) {
                $productLines[] = array(
                    Lengow_Connector_Model_Import_Orderline::FIELD_ORDER_LINE_ID =>
                        (string) $product->marketplace_order_line_id
                );
            }
            if ((int) $order->getData(self::FIELD_LEGACY_DELIVERY_ADDRESS_ID) === 0) {
                return !empty($productLines) ? $productLines : false;
            }
            $orderLines[(int) $package->delivery->id] = $productLines;
        }
        $return = $orderLines[$order->getData(self::FIELD_LEGACY_DELIVERY_ADDRESS_ID)];
        return !empty($return) ? $return : false;
    }

    /**
     * Count order lengow with error
     *
     * @return integer
     */
    public function countOrderWithError()
    {
        $results = $this->getCollection()
            ->addFieldToFilter(self::FIELD_IS_IN_ERROR, 1)
            ->addFieldToSelect(self::FIELD_ID)
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
            ->addFieldToFilter(self::FIELD_ORDER_PROCESS_STATE, 1)
            ->addFieldToSelect(self::FIELD_ID)
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
        $lo = $coreResource->getTableName(self::TABLE_ORDER);
        $connection = $coreResource->getConnection('core_read');
        $processing = $isProcessing
            ? ' AND ' . $sfo . '.state = \'' . $this->getOrderState(self::STATE_ACCEPTED) . '\''
            : '';
        $query = 'SELECT COUNT(entity_id) as total FROM ' . $sfo . '
            LEFT JOIN ' . $lo . ' AS `lo` ON lo.order_id = ' . $sfo . '.entity_id
            WHERE (' . $sfo . '.from_lengow = 1
            AND ' . $sfo . '.follow_by_lengow = 1 
            AND lo.order_id IS NULL' . $processing . ')';
        $rows = $connection->fetchCol($query);
        if ($rows) {
            return $rows[0];
        }
        return 0;
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
                    ->addAttributeToFilter(self::FIELD_LEGACY_FROM_LENGOW, 1)
                    ->addAttributeToFilter(self::FIELD_LEGACY_FOLLOW_BY_LENGOW, 1);
                if ($isProcessing) {
                    $orderCollection->addAttributeToFilter('state', $this->getOrderState(self::STATE_ACCEPTED));
                }
                $orderCollection->getSelect()->limit($perPage, ($i - 1) * $perPage);
                /** @var Mage_Sales_Model_Order[] $orderCollection */
                foreach ($orderCollection as $order) {
                    $oldOrder = Mage::getModel('lengow/import_order')->getCollection()
                        ->addFieldToFilter(self::FIELD_ORDER_ID, $order->getId())->getFirstItem();
                    if ($oldOrder->getId() > 0) {
                        unset($oldOrder);
                        continue;
                    }
                    // get old Lengow information
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
                        ? (bool) $lengowNode->tracking_informations->tracking_deliveringByMarketPlace
                        : 0;
                    $commission = isset($lengowNode->commission) ? $lengowNode->commission : 0;
                    if (isset($lengowNode->order_purchase_date, $lengowNode->order_purchase_heure)) {
                        $orderDate = $lengowNode->order_purchase_date . ' ' . $lengowNode->order_purchase_heure;
                    } else {
                        $orderDate = $order->getCreatedAt();
                    }
                    if ($countryIso === '') {
                        $address = $order->getShippingAddress();
                        $countryIso = $address->getCountryId();
                    }
                    $orderProcessState = $order->getState() === $this->getOrderState(self::STATE_ACCEPTED)
                        ? self::PROCESS_STATE_IMPORT
                        : self::PROCESS_STATE_FINISH;
                    // create new lengow order
                    /** @var Lengow_Connector_Model_Import_Order $newOrder */
                    $newOrder = Mage::getModel('lengow/import_order');
                    $newOrder->createOrder(
                        array(
                            self::FIELD_ORDER_ID => $order->getId(),
                            self::FIELD_ORDER_SKU => $order->getIncrementId(),
                            self::FIELD_STORE_ID => $order->getStoreId(),
                            self::FIELD_FEED_ID => $feedId,
                            self::FIELD_DELIVERY_ADDRESS_ID => $order->getDeliveryAddressIdLengow(),
                            self::FIELD_DELIVERY_COUNTRY_ISO => $countryIso,
                            self::FIELD_MARKETPLACE_SKU => $marketplaceSku,
                            self::FIELD_MARKETPLACE_NAME => $marketplaceName,
                            self::FIELD_MARKETPLACE_LABEL => $marketplaceName,
                            self::FIELD_ORDER_LENGOW_STATE => self::STATE_WAITING_SHIPMENT,
                            self::FIELD_ORDER_PROCESS_STATE => $orderProcessState,
                            self::FIELD_ORDER_DATE => $orderDate,
                            self::FIELD_ORDER_ITEM => $order->getTotalItemCount(),
                            self::FIELD_CURRENCY => $order->getBaseCurrencyCode(),
                            self::FIELD_TOTAL_PAID => $order->getTotalInvoiced(),
                            self::FIELD_COMMISSION => $commission,
                            self::FIELD_CUSTOMER_NAME => $order->getCustomerFirstname()
                                . ' ' . $order->getCustomerLastname(),
                            self::FIELD_CUSTOMER_EMAIL => $order->getCustomerEmail(),
                            self::FIELD_CARRIER => $order->getCarrierLengow(),
                            self::FIELD_CARRIER_METHOD => $order->getCarrierMethodLengow(),
                            self::FIELD_CARRIER_TRACKING => $order->getCarrierTrackingLengow(),
                            self::FIELD_SENT_MARKETPLACE => $sendByMarketplace,
                            self::FIELD_CREATED_AT => $order->getCreatedAt(),
                            self::FIELD_UPDATED_AT => $order->getUpdateAt(),
                            self::FIELD_MESSAGE => $order->getMessageLengow(),
                            self::FIELD_EXTRA => $order->getXmlNodeLengow(),
                        )
                    );
                    unset($oldOrder);
                }
            }
        }
    }
}
