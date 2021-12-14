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
 * Model import importorder
 */
class Lengow_Connector_Model_Import_Importorder extends Varien_Object
{
    /* Import Order construct params */
    const PARAM_STORE_ID = 'store_id';
    const PARAM_FORCE_SYNC = 'force_sync';
    const PARAM_DEBUG_MODE = 'debug_mode';
    const PARAM_LOG_OUTPUT = 'log_output';
    const PARAM_MARKETPLACE_SKU = 'marketplace_sku';
    const PARAM_DELIVERY_ADDRESS_ID = 'delivery_address_id';
    const PARAM_ORDER_DATA = 'order_data';
    const PARAM_PACKAGE_DATA = 'package_data';
    const PARAM_FIRST_PACKAGE = 'first_package';
    const PARAM_IMPORT_ONE_ORDER = 'import_one_order';
    const PARAM_IMPORT_HELPER = 'import_helper';

    /* Import Order data */
    const MERCHANT_ORDER_ID = 'merchant_order_id';
    const MERCHANT_ORDER_REFERENCE = 'merchant_order_reference';
    const LENGOW_ORDER_ID = 'lengow_order_id';
    const MARKETPLACE_SKU = 'marketplace_sku';
    const MARKETPLACE_NAME = 'marketplace_name';
    const DELIVERY_ADDRESS_ID = 'delivery_address_id';
    const SHOP_ID = 'shop_id';
    const CURRENT_ORDER_STATUS = 'current_order_status';
    const PREVIOUS_ORDER_STATUS = 'previous_order_status';
    const ERRORS = 'errors';
    const RESULT_TYPE = 'result_type';

    /* Synchronisation results */
    const RESULT_CREATED = 'created';
    const RESULT_UPDATED = 'updated';
    const RESULT_FAILED = 'failed';
    const RESULT_IGNORED = 'ignored';

    /**
     * @var Lengow_Connector_Helper_Data|null Lengow helper instance
     */
    protected $_helper;

    /**
     * @var Lengow_Connector_Helper_Import|null Lengow import helper instance
     */
    protected $_importHelper;

    /**
     * @var Lengow_Connector_Helper_Config|null Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var Lengow_Connector_Model_Import_Order|null Lengow import order instance
     */
    protected $_modelOrder;

    /**
     * @var integer|null Magento store id
     */
    protected $_storeId;

    /**
     * @var boolean force import order even if there are errors
     */
    protected $_forceSync;

    /**
     * @var boolean use debug mode
     */
    protected $_debugMode = false;

    /**
     * @var boolean display log messages
     */
    protected $_logOutput = false;

    /**
     * @var Lengow_Connector_Model_Import_Marketplace Lengow marketplace instance
     */
    protected $_marketplace;

    /**
     * @var string id lengow of current order
     */
    protected $_marketplaceSku;

    /**
     * @var string marketplace label
     */
    protected $_marketplaceLabel;

    /**
     * @var integer id of delivery address for current order
     */
    protected $_deliveryAddressId;

    /**
     * @var mixed order data
     */
    protected $_orderData;

    /**
     * @var mixed package data
     */
    protected $_packageData;

    /**
     * @var boolean is first package
     */
    protected $_firstPackage;

    /**
     * @var boolean import one order var from lengow import
     */
    protected $_importOneOrder;

    /**
     * @var boolean re-import order
     */
    protected $_isReimported = false;

    /**
     * @var integer id of the record Lengow order table
     */
    protected $_orderLengowId;

    /**
     * @var integer id of the record Magento order table
     */
    protected $_orderId;

    /**
     * @var integer Magento order reference
     */
    protected $_orderReference;

    /**
     * @var string order date in GMT format
     */
    protected $_orderDate;

    /**
     * @var string marketplace order state
     */
    protected $_orderStateMarketplace;

    /**
     * @var string Previous Lengow order state
     */
    protected $_previousOrderStateLengow;

    /**
     * @var string Lengow order state
     */
    protected $_orderStateLengow;

    /**
     * @var float order processing fees
     */
    protected $_processingFee;

    /**
     * @var float order shipping costs
     */
    protected $_shippingCost;

    /**
     * @var float order amount
     */
    protected $_orderAmount;

    /**
     * @var integer order items
     */
    protected $_orderItems;

    /**
     * @var string|null carrier name
     */
    protected $_carrierName;

    /**
     * @var string|null carrier method
     */
    protected $_carrierMethod;

    /**
     * @var string|null carrier tracking number
     */
    protected $_trackingNumber;

    /**
     * @var boolean order shipped by marketplace
     */
    protected $_shippedByMp = false;

    /**
     * @var string|null carrier relay id
     */
    protected $_relayId;

    /**
     * @var int shipping tax amount
     */
    protected $_shippingTaxAmount = 0;

    /**
     * @var array order errors
     */
    private $_errors = array();

    /**
     * Construct the import order manager
     *
     * @param array $params optional options
     * integer store_id            Id store for current order
     * boolean debug_mode          debug mode
     * boolean log_output          display log messages
     * boolean marketplace_sku     marketplace sku
     * boolean delivery_address_id delivery address id
     * boolean order_data          order data
     * boolean package_data        package data
     * boolean first_package       first package
     * boolean import_one_order    synchronisation process for only one order
     * boolean import_helper       import helper
     */
    public function __construct($params = array())
    {
        // get params
        $this->_storeId = $params[self::PARAM_STORE_ID];
        $this->_forceSync = $params[self::PARAM_FORCE_SYNC];
        $this->_debugMode = $params[self::PARAM_DEBUG_MODE];
        $this->_logOutput = $params[self::PARAM_LOG_OUTPUT];
        $this->_marketplaceSku = $params[self::PARAM_MARKETPLACE_SKU];
        $this->_deliveryAddressId = $params[self::PARAM_DELIVERY_ADDRESS_ID];
        $this->_orderData = $params[self::PARAM_ORDER_DATA];
        $this->_packageData = $params[self::PARAM_PACKAGE_DATA];
        $this->_firstPackage = $params[self::PARAM_FIRST_PACKAGE];
        $this->_importOneOrder = $params[self::PARAM_IMPORT_ONE_ORDER];
        $this->_importHelper = $params[self::PARAM_IMPORT_HELPER];
        // get helpers
        $this->_helper = Mage::helper('lengow_connector/data');
        $this->_configHelper = Mage::helper('lengow_connector/config');
        $this->_modelOrder = Mage::getModel('lengow/import_order');
    }

    /**
     * Create or update order
     *
     * @throws Exception
     *
     * @return array
     */
    public function importOrder()
    {
        // load marketplace singleton and marketplace data
        if (!$this->_loadMarketplaceData()) {
            return $this->_returnResult(self::RESULT_IGNORED);
        }
        // checks if a record already exists in the lengow order table
        $this->_orderLengowId = $this->_modelOrder->getLengowOrderId(
            $this->_marketplaceSku,
            $this->_marketplace->name,
            $this->_deliveryAddressId
        );
        // checks if an order already has an error in progress
        if ($this->_orderLengowId && $this->_orderErrorAlreadyExist()) {
            return $this->_returnResult(self::RESULT_IGNORED);
        }
        // recovery id if the command has already been imported
        $orderId = $this->_modelOrder->getOrderIdIfExist(
            $this->_marketplaceSku,
            $this->_marketplace->name,
            $this->_deliveryAddressId,
            $this->_marketplace->legacyCode
        );
        // update order state if already imported
        if ($orderId) {
            $orderUpdated = $this->_checkAndUpdateOrder($orderId);
            if ($orderUpdated) {
                return $this->_returnResult(self::RESULT_UPDATED);
            }
            if (!$this->_isReimported) {
                return $this->_returnResult(self::RESULT_IGNORED);
            }
        }
        // checks if the order is not anonymized or too old
        if (!$this->_orderLengowId && !$this->_canCreateOrder()) {
            return $this->_returnResult(self::RESULT_IGNORED);
        }
        // checks if an external id already exists
        if (!$this->_orderLengowId && $this->_externalIdAlreadyExist()) {
            return $this->_returnResult(self::RESULT_IGNORED);
        }
        // checks if the order status is valid for order creation
        if (!$this->_orderStatusIsValid()) {
            return $this->_returnResult(self::RESULT_IGNORED);
        }
        // load data and create a new record in lengow order table if not exist
        if (!$this->_createLengowOrder()) {
            return $this->_returnResult(self::RESULT_IGNORED);
        }
        /** @var Lengow_Connector_Model_Import_Order $orderLengow */
        $orderLengow = $this->_modelOrder->load((int) $this->_orderLengowId);
        // checks if the required order data is present and update Lengow order record
        if (!$this->_checkAndUpdateLengowOrderData($orderLengow)) {
            return $this->_returnResult(self::RESULT_FAILED);
        }
        // checks if an order sent by the marketplace must be created or not
        if (!$this->_canCreateOrderShippedByMarketplace($orderLengow)) {
            return $this->_returnResult(self::RESULT_IGNORED);
        }
        // create Magento order
        if (!$this->_createOrder($orderLengow)) {
            return $this->_returnResult(self::RESULT_FAILED);
        }
        return $this->_returnResult(self::RESULT_CREATED);
    }

    /**
     * Load marketplace singleton and marketplace data
     *
     * @return boolean
     */
    private function _loadMarketplaceData()
    {
        try {
            // get marketplace and Lengow order state
            $this->_marketplace = $this->_importHelper->getMarketplaceSingleton(
                (string) $this->_orderData->marketplace
            );
            $this->_marketplaceLabel = $this->_marketplace->labelName;
            $this->_orderStateMarketplace = (string) $this->_orderData->marketplace_status;
            $this->_orderStateLengow = $this->_marketplace->getStateLengow($this->_orderStateMarketplace);
            $this->_previousOrderStateLengow = $this->_orderStateLengow;
            return true;
        } catch (Lengow_Connector_Model_Exception $e) {
            $this->_errors[] = $this->_helper->decodeLogMessage($e->getMessage(), false);
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_IMPORT,
                $e->getMessage(),
                $this->_logOutput,
                $this->_marketplaceSku
            );
        }
        return false;
    }

    /**
     * Return an array of result for each order
     *
     * @param string $resultType Type of result (created, updated, failed or ignored)
     *
     * @return array
     */
    protected function _returnResult($resultType)
    {
        return array(
            self::MERCHANT_ORDER_ID => $this->_orderId,
            self::MERCHANT_ORDER_REFERENCE => $this->_orderReference,
            self::LENGOW_ORDER_ID => $this->_orderLengowId,
            self::MARKETPLACE_SKU => $this->_marketplaceSku,
            self::MARKETPLACE_NAME =>  $this->_marketplace ? $this->_marketplace->name : null,
            self::DELIVERY_ADDRESS_ID => $this->_deliveryAddressId,
            self::SHOP_ID => $this->_storeId,
            self::CURRENT_ORDER_STATUS => $this->_orderStateLengow,
            self::PREVIOUS_ORDER_STATUS => $this->_previousOrderStateLengow,
            self::ERRORS => $this->_errors,
            self::RESULT_TYPE => $resultType,
        );
    }

    /**
     * Checks if an order already has an error in progress
     *
     * @return boolean
     */
    private function _orderErrorAlreadyExist()
    {
        // if log import exist and not finished
        $orderError = $this->_modelOrder->orderIsInError($this->_marketplaceSku, $this->_deliveryAddressId);
        if (!$orderError) {
            return false;
        }
        // force order synchronization by removing pending errors
        if ($this->_forceSync) {
            Mage::getModel('lengow/import_ordererror')->finishOrderErrors($this->_orderLengowId);
            return false;
        }
        $dateMessage = Mage::getModel('core/date')->date(
            Lengow_Connector_Helper_Data::DATE_FULL,
            strtotime($orderError[Lengow_Connector_Model_Import_Ordererror::FIELD_CREATED_AT])
        );
        $decodedMessage = $this->_helper->decodeLogMessage(
            $orderError[Lengow_Connector_Model_Import_Ordererror::FIELD_MESSAGE],
            Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE
        );
        $message = $this->_helper->setLogMessage(
            'log.import.error_already_created',
            array(
                'decoded_message' => $decodedMessage,
                'date_message' => $dateMessage,
            )
        );
        $this->_errors[] = $this->_helper->decodeLogMessage(
            $message,
            Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE
        );
        $this->_helper->log(
            Lengow_Connector_Helper_Data::CODE_IMPORT,
            $message,
            $this->_logOutput,
            $this->_marketplaceSku
        );
        return true;
    }

    /**
     * Check the command and updates data if necessary
     *
     * @param integer $orderId Magento order id
     *
     * @return boolean
     */
    private function _checkAndUpdateOrder($orderId)
    {
        $orderUpdated = false;
        $order = Mage::getModel('sales/order')->load($orderId);
        $this->_helper->log(
            Lengow_Connector_Helper_Data::CODE_IMPORT,
            $this->_helper->setLogMessage(
                'log.import.order_already_imported',
                array('order_id' => $order->getIncrementId())
            ),
            $this->_logOutput,
            $this->_marketplaceSku
        );
        $orderLengowId = $this->_modelOrder->getLengowOrderIdWithOrderId($orderId);
        /** @var Lengow_Connector_Model_Import_Order $orderLengow */
        $orderLengow = $this->_modelOrder->load((int) $orderLengowId);
        // Lengow -> cancel and reimport order
        if ((bool) $order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_IS_REIMPORTED)) {
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_IMPORT,
                $this->_helper->setLogMessage(
                    'log.import.order_ready_to_reimport',
                    array('order_id' => $order->getIncrementId())
                ),
                $this->_logOutput,
                $this->_marketplaceSku
            );
            $this->_isReimported = true;
            return $orderUpdated;
        }
        // load data for return
        $this->_orderId = (int) $orderId;
        $this->_orderReference = $order->getIncrementId();
        $this->_previousOrderStateLengow = $orderLengow->getData(
            Lengow_Connector_Model_Import_Order::FIELD_ORDER_LENGOW_STATE
        );
        // try to update magento order, lengow order and finish actions if necessary
        $orderUpdated = $this->_modelOrder->updateState(
            $order,
            $this->_orderStateLengow,
            $this->_packageData,
            $orderLengowId
        );
        if ($orderUpdated) {
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_IMPORT,
                $this->_helper->setLogMessage(
                    'log.import.order_state_updated',
                    array('state_name' => $orderUpdated)
                ),
                $this->_logOutput,
                $this->_marketplaceSku
            );
            $orderUpdated = true;
        }
        unset($order, $orderLengow);
        return $orderUpdated;
    }

    /**
     * Checks if the order is not anonymized or too old
     *
     * @return boolean
     */
    private function _canCreateOrder()
    {
        if ($this->_importOneOrder) {
            return true;
        }
        // skip import if the order is anonymize
        if ($this->_orderData->anonymized) {
            $message = $this->_helper->setLogMessage('log.import.anonymized_order');
            $this->_errors[] = $this->_helper->decodeLogMessage(
                $message,
                Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE
            );
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_IMPORT,
                $message,
                $this->_logOutput,
                $this->_marketplaceSku
            );
            return false;
        }
        // skip import if the order is older than 3 months
        try {
            $dateTimeOrder = new DateTime($this->_orderData->marketplace_order_date);
            $interval = $dateTimeOrder->diff(new DateTime());
            $monthsInterval = $interval->m + ($interval->y * 12);
            if ($monthsInterval >= Lengow_Connector_Model_Import::MONTH_INTERVAL_TIME) {
                $message = $this->_helper->setLogMessage('log.import.old_order');
                $this->_errors[] = $this->_helper->decodeLogMessage(
                    $message,
                    Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE
                );
                $this->_helper->log(
                    Lengow_Connector_Helper_Data::CODE_IMPORT,
                    $message,
                    $this->_logOutput,
                    $this->_marketplaceSku
                );
                return false;
            }
        } catch (Exception $e) {
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_IMPORT,
                $this->_helper->setLogMessage('log.import.unable_verify_date'),
                $this->_logOutput,
                $this->_marketplaceSku
            );
        }
        return true;
    }

    /**
     * Checks if an external id already exists
     *
     * @return boolean
     */
    private function _externalIdAlreadyExist()
    {
        if (empty($this->_orderData->merchant_order_id) || $this->_debugMode || $this->_isReimported) {
            return false;
        }
        foreach ($this->_orderData->merchant_order_id as $externalId) {
            if ($this->_modelOrder->getOrderIdWithDeliveryAddress((int) $externalId, (int) $this->_deliveryAddressId)) {
                $message = $this->_helper->setLogMessage(
                    'log.import.external_id_exist',
                    array('order_id' => $externalId)
                );
                $this->_errors[] = $this->_helper->decodeLogMessage(
                    $message,
                    Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE
                );
                $this->_helper->log(
                    Lengow_Connector_Helper_Data::CODE_IMPORT,
                    $message,
                    $this->_logOutput,
                    $this->_marketplaceSku
                );
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if the order status is valid for order creation
     *
     * @return boolean
     */
    private function _orderStatusIsValid()
    {
        if ($this->_importHelper->checkState($this->_orderStateMarketplace, $this->_marketplace)) {
            return true;
        }
        $orderProcessState = $this->_modelOrder->getOrderProcessState($this->_orderStateLengow);
        // check and complete an order not imported if it is canceled or refunded
        if ($this->_orderLengowId
            && $orderProcessState === Lengow_Connector_Model_Import_Order::PROCESS_STATE_FINISH
        ) {
            Mage::getModel('lengow/import_ordererror')->finishOrderErrors($this->_orderLengowId);
            // load lengow order
            /** @var Lengow_Connector_Model_Import_Order $orderLengow */
            $orderLengow = $this->_modelOrder->load((int) $this->_orderLengowId);
            $orderLengow->updateOrder(
                array(
                    Lengow_Connector_Model_Import_Order::FIELD_IS_IN_ERROR => 0,
                    Lengow_Connector_Model_Import_Order::FIELD_ORDER_LENGOW_STATE => $this->_orderStateLengow,
                    Lengow_Connector_Model_Import_Order::FIELD_ORDER_PROCESS_STATE => $orderProcessState,
                )
            );
        }
        $message = $this->_helper->setLogMessage(
            'log.import.current_order_state_unavailable',
            array(
                'order_state_marketplace' => $this->_orderStateMarketplace,
                'marketplace_name' => $this->_marketplace->name,
            )
        );
        $this->_errors[] = $this->_helper->decodeLogMessage(
            $message,
            Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE
        );
        $this->_helper->log(
            Lengow_Connector_Helper_Data::CODE_IMPORT,
            $message,
            $this->_logOutput,
            $this->_marketplaceSku
        );
        return false;
    }

    /**
     * Create an order in lengow orders table
     *
     * @return boolean
     */
    private function _createLengowOrder()
    {
        // load order date
        $this->_loadOrderDate();
        // If the Lengow order already exists do not recreate it
        if ($this->_orderLengowId) {
            return true;
        }
        $params = array(
            Lengow_Connector_Model_Import_Order::FIELD_STORE_ID => (int) $this->_storeId,
            Lengow_Connector_Model_Import_Order::FIELD_MARKETPLACE_SKU => $this->_marketplaceSku,
            Lengow_Connector_Model_Import_Order::FIELD_MARKETPLACE_NAME => $this->_marketplace->name,
            Lengow_Connector_Model_Import_Order::FIELD_MARKETPLACE_LABEL => $this->_marketplaceLabel,
            Lengow_Connector_Model_Import_Order::FIELD_DELIVERY_ADDRESS_ID => (int) $this->_deliveryAddressId,
            Lengow_Connector_Model_Import_Order::FIELD_ORDER_LENGOW_STATE => $this->_orderStateLengow,
            Lengow_Connector_Model_Import_Order::FIELD_ORDER_TYPES => $this->_getOrderTypesData(),
            Lengow_Connector_Model_Import_Order::FIELD_CUSTOMER_VAT_NUMBER => $this->_getVatNumberFromOrderData(),
            Lengow_Connector_Model_Import_Order::FIELD_ORDER_DATE => $this->_orderDate,
            Lengow_Connector_Model_Import_Order::FIELD_MESSAGE => $this->_getOrderComment(),
            Lengow_Connector_Model_Import_Order::FIELD_EXTRA => Mage::helper('core')->jsonEncode($this->_orderData),
            Lengow_Connector_Model_Import_Order::FIELD_IS_IN_ERROR => 1,
        );
        // create lengow order
        $this->_modelOrder->createOrder($params);
        // get lengow order id
        $this->_orderLengowId = $this->_modelOrder->getLengowOrderId(
            $this->_marketplaceSku,
            $this->_marketplace->name,
            $this->_deliveryAddressId
        );
        if ($this->_orderLengowId) {
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_IMPORT,
                $this->_helper->setLogMessage('log.import.lengow_order_saved'),
                $this->_logOutput,
                $this->_marketplaceSku
            );
            return true;
        }
        $message = $this->_helper->setLogMessage('log.import.lengow_order_not_saved');
        $this->_errors[] = $this->_helper->decodeLogMessage(
            $message,
            Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE
        );
        $this->_helper->log(
            Lengow_Connector_Helper_Data::CODE_IMPORT,
            $message,
            $this->_logOutput,
            $this->_marketplaceSku
        );
        return false;
    }

    /**
     * Load order date in GMT format
     */
    private function _loadOrderDate()
    {
        $orderDate = $this->_orderData->marketplace_order_date !== null
            ? (string) $this->_orderData->marketplace_order_date
            : (string) $this->_orderData->imported_at;
        $coreDate = Mage::getModel('core/date');
        $this->_orderDate = $coreDate->gmtDate(
            Lengow_Connector_Helper_Data::DATE_FULL,
            $coreDate->timestamp($orderDate)
        );
    }

    /**
     * Get order types data and update Lengow order record
     */
    private function _getOrderTypesData()
    {
        $orderTypes = array();
        if ($this->_orderData->order_types !== null && !empty($this->_orderData->order_types)) {
            foreach ($this->_orderData->order_types as $orderType) {
                $orderTypes[$orderType->type] = $orderType->label;
                if ($orderType->type === Lengow_Connector_Model_Import_Order::TYPE_DELIVERED_BY_MARKETPLACE) {
                    $this->_shippedByMp = true;
                }
            }
        }
        return Mage::helper('core')->jsonEncode($orderTypes);
    }

    /**
     * Get order comment from marketplace
     *
     * @return string
     */
    private function _getOrderComment()
    {
        if (isset($this->_orderData->comments) && is_array($this->_orderData->comments)) {
            return implode(',', $this->_orderData->comments);
        }
        return (string) $this->_orderData->comments;
    }

    /**
     * Get vat_number from lengow order data
     *
     * @return string|null
     */
    private function _getVatNumberFromOrderData() {
        if (isset($this->_orderData->billing_address->vat_number)) {
            return $this->_orderData->billing_address->vat_number;
        }
        if (isset($this->_packageData->delivery->vat_number)) {
            return $this->_packageData->delivery->vat_number;
        }
        return null;
    }

    /**
     * Checks if the required order data is present and update Lengow order record
     *
     * @param Lengow_Connector_Model_Import_Order $orderLengow Lengow order instance
     *
     * @return boolean
     */
    private function _checkAndUpdateLengowOrderData($orderLengow)
    {
        // checks if all necessary order data are present
        if (!$this->_checkOrderData()) {
            return false;
        }
        // load order amount, processing fees and shipping costs
        $this->_loadOrderAmount();
        // load tracking data
        $this->_loadTrackingData();
        // update Lengow order with new information
        $orderLengow->updateOrder(
            array(
                Lengow_Connector_Model_Import_Order::FIELD_CURRENCY => $this->_orderData->currency->iso_a3,
                Lengow_Connector_Model_Import_Order::FIELD_TOTAL_PAID => $this->_orderAmount,
                Lengow_Connector_Model_Import_Order::FIELD_ORDER_ITEM => $this->_orderItems,
                Lengow_Connector_Model_Import_Order::FIELD_CUSTOMER_NAME => $this->_getCustomerName(),
                Lengow_Connector_Model_Import_Order::FIELD_CUSTOMER_EMAIL => $this->_getCustomerEmail(),
                Lengow_Connector_Model_Import_Order::FIELD_COMMISSION => (float) $this->_orderData->commission,
                Lengow_Connector_Model_Import_Order::FIELD_CARRIER => $this->_carrierName,
                Lengow_Connector_Model_Import_Order::FIELD_CARRIER_METHOD => $this->_carrierMethod,
                Lengow_Connector_Model_Import_Order::FIELD_CARRIER_TRACKING => $this->_trackingNumber,
                Lengow_Connector_Model_Import_Order::FIELD_CARRIER_RELAY_ID => $this->_relayId,
                Lengow_Connector_Model_Import_Order::FIELD_SENT_MARKETPLACE => $this->_shippedByMp,
                Lengow_Connector_Model_Import_Order::FIELD_DELIVERY_COUNTRY_ISO =>
                    $this->_packageData->delivery->common_country_iso_a2,
                Lengow_Connector_Model_Import_Order::FIELD_ORDER_LENGOW_STATE => $this->_orderStateLengow,
                Lengow_Connector_Model_Import_Order::FIELD_EXTRA => Mage::helper('core')->jsonEncode($this->_orderData),
            )
        );
        return true;
    }

    /**
     * Checks if order data are present
     *
     * @return boolean
     */
    protected function _checkOrderData()
    {
        $errorMessages = array();
        if (empty($this->_packageData->cart)) {
            $errorMessages[] = $this->_helper->setLogMessage('lengow_log.error.no_product');
        }
        if (!isset($this->_orderData->currency->iso_a3)) {
            $errorMessages[] = $this->_helper->setLogMessage('lengow_log.error.no_currency');
        }
        if ($this->_orderData->total_order == -1) {
            $errorMessages[] = $this->_helper->setLogMessage('lengow_log.error.no_change_rate');
        }
        if ($this->_orderData->billing_address === null) {
            $errorMessages[] = $this->_helper->setLogMessage('lengow_log.error.no_billing_address');
        } elseif ($this->_orderData->billing_address->common_country_iso_a2 === null) {
            $errorMessages[] = $this->_helper->setLogMessage('lengow_log.error.no_country_for_billing_address');
        }
        if ($this->_packageData->delivery->common_country_iso_a2 === null) {
            $errorMessages[] = $this->_helper->setLogMessage('lengow_log.error.no_country_for_delivery_address');
        }
        if (empty($errorMessages)) {
            return true;
        }
        foreach ($errorMessages as $errorMessage) {
            Mage::getModel('lengow/import_ordererror')->createOrderError(
                array(
                    Lengow_Connector_Model_Import_Ordererror::FIELD_ORDER_LENGOW_ID => $this->_orderLengowId,
                    Lengow_Connector_Model_Import_Ordererror::FIELD_MESSAGE => $errorMessage,
                    Lengow_Connector_Model_Import_Ordererror::FIELD_TYPE =>
                        Lengow_Connector_Model_Import_Ordererror::TYPE_ERROR_IMPORT,
                )
            );
            $decodedMessage = $this->_helper->decodeLogMessage(
                $errorMessage,
                Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE
            );
            $this->_errors[] = $decodedMessage;
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_IMPORT,
                $this->_helper->setLogMessage(
                    'log.import.order_import_failed',
                    array('decoded_message' => $decodedMessage)
                ),
                $this->_logOutput,
                $this->_marketplaceSku
            );
        }
        return false;
    }

    /**
     * Load order amount, processing fees and shipping costs
     */
    protected function _loadOrderAmount()
    {
        $this->_processingFee = (float) $this->_orderData->processing_fee;
        $this->_shippingCost = (float) $this->_orderData->shipping;
        // rewrite processing fees and shipping cost
        if (!$this->_firstPackage) {
            $this->_processingFee = 0;
            $this->_shippingCost = 0;
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_IMPORT,
                $this->_helper->setLogMessage('log.import.rewrite_processing_fee'),
                $this->_logOutput,
                $this->_marketplaceSku
            );
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_IMPORT,
                $this->_helper->setLogMessage('log.import.rewrite_shipping_cost'),
                $this->_logOutput,
                $this->_marketplaceSku
            );
        }
        // get total amount and the number of items
        $nbItems = 0;
        $totalAmount = 0;
        foreach ($this->_packageData->cart as $product) {
            // check whether the product is canceled for amount
            if ($product->marketplace_status !== null) {
                $stateProduct = $this->_marketplace->getStateLengow((string) $product->marketplace_status);
                if ($stateProduct === Lengow_Connector_Model_Import_Order::STATE_CANCELED
                    || $stateProduct === Lengow_Connector_Model_Import_Order::STATE_REFUSED
                ) {
                    continue;
                }
            }
            $nbItems += (int) $product->quantity;
            $totalAmount += (float) $product->amount;
        }
        $this->_orderItems = $nbItems;
        $this->_orderAmount = $totalAmount + $this->_processingFee + $this->_shippingCost;
    }

    /**
     * Get tracking data and update Lengow order record
     */
    protected function _loadTrackingData()
    {
        $tracks = $this->_packageData->delivery->trackings;
        if (!empty($tracks)) {
            $tracking = $tracks[0];
            $this->_carrierName = $tracking->carrier;
            $this->_carrierMethod = $tracking->method;
            $this->_trackingNumber = $tracking->number;
            $this->_relayId = $tracking->relay->id;
        }
    }

    /**
     * Get customer name
     *
     * @return string
     */
    private function _getCustomerName()
    {
        $firstname = (string) $this->_orderData->billing_address->first_name;
        $lastname = (string) $this->_orderData->billing_address->last_name;
        $firstname = ucfirst(strtolower($firstname));
        $lastname = ucfirst(strtolower($lastname));
        if (empty($firstname) && empty($lastname)) {
            return (string) $this->_orderData->billing_address->full_name;
        }
        if (empty($firstname)) {
            return $lastname;
        }
        if (empty($lastname)) {
            return $firstname;
        }
        return $firstname . ' ' . $lastname;
    }

    /**
     * Get customer email
     *
     * @return string
     */
    private function _getCustomerEmail()
    {
        return $this->_orderData->billing_address->email !== null
            ? (string) $this->_orderData->billing_address->email
            : (string) $this->_packageData->delivery->email;
    }

    /**
     * Checks if an order sent by the marketplace must be created or not
     *
     * @param Lengow_Connector_Model_Import_Order $orderLengow Lengow order instance
     *
     * @return boolean
     */
    private function _canCreateOrderShippedByMarketplace($orderLengow)
    {
        // check if the order is shipped by marketplace
        if ($this->_shippedByMp) {
            $message =  $this->_helper->setLogMessage(
                'log.import.order_shipped_by_marketplace',
                array('marketplace_name' => $this->_marketplace->name)
            );
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_IMPORT,
                $message,
                $this->_logOutput,
                $this->_marketplaceSku
            );
            if (!$this->_configHelper->get(
                Lengow_Connector_Helper_Config::SHIPPED_BY_MARKETPLACE_ENABLED,
                $this->_storeId
            )) {
                $this->_errors[] = $this->_helper->decodeLogMessage(
                    $message,
                    Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE
                );
                $orderLengow->updateOrder(
                    array(
                        Lengow_Connector_Model_Import_Order::FIELD_IS_IN_ERROR => 0,
                        Lengow_Connector_Model_Import_Order::FIELD_ORDER_PROCESS_STATE => 2,
                    )
                );
                return false;
            }
        }
        return true;
    }

    /**
     * Create a Magento order
     *
     * @param Lengow_Connector_Model_Import_Order $orderLengow Lengow order instance
     *
     * @return boolean
     */
    private function _createOrder($orderLengow)
    {
        try {
            // get products from Api
            $products = $this->_getProducts();
            // create or Update customer with addresses
            $customer = Mage::getModel('lengow/import_customer')->createCustomer(
                $this->_orderData,
                $this->_packageData->delivery,
                $this->_storeId,
                $this->_marketplaceSku,
                $this->_logOutput
            );
            // if the order is B2B, activate B2bTaxesApplicator
            $noTax = false;
            if ((bool) $this->_configHelper->get(Lengow_Connector_Helper_Config::B2B_WITHOUT_TAX_ENABLED)
                && $orderLengow->isBusiness()
            ) {
                // set backend session b2b attribute
                Mage::getSingleton('core/session')->setIsLengowB2b(1);
                $noTax = true;
            }
            // create Magento Quote
            $quote = $this->_createQuote($customer, $products, $noTax);
            // create Magento order
            $order = $this->_makeOrder($quote, $orderLengow, $noTax);
            // If order is successfully imported
            if (!$order) {
                throw new Lengow_Connector_Model_Exception(
                    $this->_helper->setLogMessage('lengow_log.exception.order_is_empty')
                );
            }
            // load order data for return
            $this->_orderId = (int) $order->getId();
            $this->_orderReference = $order->getIncrementId();
            // save order line id in lengow_order_line table
            $this->_saveLengowOrderLine($order, $products);
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_IMPORT,
                $this->_helper->setLogMessage(
                    'log.import.order_successfully_imported',
                    array('order_id' => $order->getIncrementId())
                ),
                $this->_logOutput,
                $this->_marketplaceSku
            );
            // checks and places the order in complete status in Magento
            $this->_updateStateToShip($order);
            // add quantity back for re-imported order and order shipped by marketplace
            $this->_addQuantityBack($products);
            // Inactivate quote (Test)
            $quote->setIsActive(false)->save();
        } catch (Lengow_Connector_Model_Exception $e) {
            $errorMessage = $e->getMessage();
        } catch (Exception $e) {
            $errorMessage = '[Magento error]: "' . $e->getMessage()
                . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
        }
        if (!isset($errorMessage)) {
            return true;
        }
        if ((bool) $orderLengow->getData(Lengow_Connector_Model_Import_Order::FIELD_IS_IN_ERROR)) {
            Mage::getModel('lengow/import_ordererror')->createOrderError(
                array(
                    Lengow_Connector_Model_Import_Ordererror::FIELD_ORDER_LENGOW_ID => $this->_orderLengowId,
                    Lengow_Connector_Model_Import_Ordererror::FIELD_MESSAGE => $errorMessage,
                    Lengow_Connector_Model_Import_Ordererror::FIELD_TYPE =>
                        Lengow_Connector_Model_Import_Ordererror::TYPE_ERROR_IMPORT,
                )
            );
        }
        $decodedMessage = $this->_helper->decodeLogMessage(
            $errorMessage,
            Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE
        );
        $this->_errors[] = $decodedMessage;
        $this->_helper->log(
            Lengow_Connector_Helper_Data::CODE_IMPORT,
            $this->_helper->setLogMessage(
                'log.import.order_import_failed',
                array('decoded_message' => $decodedMessage)
            ),
            $this->_logOutput,
            $this->_marketplaceSku
        );
        $orderLengow->updateOrder(
            array(
                Lengow_Connector_Model_Import_Order::FIELD_EXTRA => Mage::helper('core')->jsonEncode($this->_orderData),
                Lengow_Connector_Model_Import_Order::FIELD_ORDER_LENGOW_STATE => $this->_orderStateLengow,
            )
        );
        return false;
    }

    /**
     * Find product in Magento based on API data
     *
     * @throws Lengow_Connector_Model_Exception
     *
     * @return array
     */
    private function _getProducts()
    {
        $lengowProducts = array();
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector/data');
        foreach ($this->_packageData->cart as $product) {
            $found = false;
            $magentoProduct = false;
            $productModel = Mage::getModel('catalog/product');
            $orderLineId = (string) $product->marketplace_order_line_id;
            // check whether the product is canceled
            if ($product->marketplace_status !== null) {
                $stateProduct = $this->_marketplace->getStateLengow((string) $product->marketplace_status);
                if ($stateProduct === Lengow_Connector_Model_Import_Order::STATE_CANCELED
                    || $stateProduct === Lengow_Connector_Model_Import_Order::STATE_REFUSED
                ) {
                    $productId = $product->merchant_product_id->id !== null
                        ? (string) $product->merchant_product_id->id
                        : (string) $product->marketplace_product_id;
                    $helper->log(
                        Lengow_Connector_Helper_Data::CODE_IMPORT,
                        $helper->setLogMessage(
                            'log.import.product_state_canceled',
                            array(
                                'product_id' => $productId,
                                'state_product' => $stateProduct,
                            )
                        ),
                        $this->_logOutput,
                        $this->_marketplaceSku
                    );
                    continue;
                }
            }
            $productIds = array(
                'merchant_product_id' => $product->merchant_product_id->id,
                'marketplace_product_id' => $product->marketplace_product_id,
            );
            $productField = $product->merchant_product_id->field !== null
                ? strtolower((string) $product->merchant_product_id->field)
                : false;
            // search product foreach value
            foreach ($productIds as $attributeName => $attributeValue) {
                // remove _FBA from product id
                $attributeValue = preg_replace('/_FBA$/', '', $attributeValue);
                if (empty($attributeValue)) {
                    continue;
                }
                // search by field if exists
                if ($productField) {
                    $attributeModel = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $productField);
                    if ($attributeModel->getAttributeId()) {
                        $collection = Mage::getResourceModel('catalog/product_collection')
                            ->setStoreId($this->_storeId)
                            ->addAttributeToSelect($productField)
                            ->addAttributeToFilter($productField, $attributeValue)
                            ->setPage(1, 1)
                            ->getData();
                        if (is_array($collection) && !empty($collection)) {
                            $magentoProduct = $productModel->load($collection[0]['entity_id']);
                        }
                    }
                }
                // search by id or sku
                if (!$magentoProduct || !$magentoProduct->getId()) {
                    if (preg_match('/^[0-9]*$/', $attributeValue)) {
                        $magentoProduct = $productModel->load((integer)$attributeValue);
                    }
                    if (!$magentoProduct || !$magentoProduct->getId()) {
                        $attributeValue = str_replace('\_', '_', $attributeValue);
                        $magentoProduct = $productModel->load($productModel->getIdBySku($attributeValue));
                    }
                }
                if ($magentoProduct && $magentoProduct->getId()) {
                    $magentoProductId = $magentoProduct->getId();
                    // save total row Lengow for each product
                    if (array_key_exists($magentoProductId, $lengowProducts)) {
                        $lengowProducts[$magentoProductId]['quantity'] += (int)$product->quantity;
                        $lengowProducts[$magentoProductId]['amount'] += (float) $product->amount;
                        $lengowProducts[$magentoProductId]['order_line_ids'][] = $orderLineId;
                    } else {
                        $lengowProducts[$magentoProductId] = array(
                            'magento_product' => $magentoProduct,
                            'sku' => (string) $magentoProduct->getSku(),
                            'title' => (string) $product->title,
                            'amount' => (float) $product->amount,
                            'price_unit' => (float) ($product->amount / $product->quantity),
                            'quantity' => (int) $product->quantity,
                            'order_line_ids' => array($orderLineId),
                        );
                    }
                    $helper->log(
                        Lengow_Connector_Helper_Data::CODE_IMPORT,
                        $helper->setLogMessage(
                            'log.import.product_be_found',
                            array(
                                'product_id' => $magentoProduct->getId(),
                                'attribute_name' => $attributeName,
                                'attribute_value' => $attributeValue,
                            )
                        ),
                        $this->_logOutput,
                        $this->_marketplaceSku
                    );
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $productId = $product->merchant_product_id->id !== null
                    ? (string) $product->merchant_product_id->id
                    : (string) $product->marketplace_product_id;
                throw new Lengow_Connector_Model_Exception(
                    $helper->setLogMessage(
                        'lengow_log.exception.product_not_be_found',
                        array('product_id' => $productId)
                    )
                );
            }
            if ($magentoProduct->getTypeId() === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                throw new Lengow_Connector_Model_Exception(
                    $helper->setLogMessage(
                        'lengow_log.exception.product_is_a_parent',
                        array('product_id' => $magentoProduct->getId())
                    )
                );
            }
        }
        return $lengowProducts;
    }

    /**
     * Create quote
     *
     * @param Mage_Customer_Model_Customer $customer Lengow customer instance
     * @param array $products Lengow products from Api
     * @param bool $noTax Should apply shipping tax
     *
     * @throws Exception
     *
     * @return Lengow_Connector_Model_Import_Quote
     */
    private function _createQuote($customer, $products, $noTax = false)
    {
        /** @var Lengow_Connector_Model_Import_Quote $quote */
        $quote = Mage::getModel('lengow/import_quote')
            ->setIsMultiShipping(false)
            ->setStore(Mage::app()->getStore($this->_storeId))
            ->setIsSuperMode(true); // set quote to superMode
        // import customer addresses into quote
        // set billing Address
        $customerBillingAddress = Mage::getModel('customer/address')->load($customer->getDefaultBilling());
        $billingAddress = Mage::getModel('sales/quote_address')
            ->setShouldIgnoreValidation(true)
            ->importCustomerAddress($customerBillingAddress)
            ->setSaveInAddressBook(0);
        // set shipping Address
        $customerShippingAddress = Mage::getModel('customer/address')->load($customer->getDefaultShipping());
        $shippingAddress = Mage::getModel('sales/quote_address')
            ->setShouldIgnoreValidation(true)
            ->importCustomerAddress($customerShippingAddress)
            ->setSaveInAddressBook(0)
            ->setSameAsBilling(0);
        $quote->assignCustomerWithAddressChange($customer, $billingAddress, $shippingAddress);
        // check if store include tax (Product and shipping cost)
        $priceIncludeTax = Mage::helper('tax')->priceIncludesTax($quote->getStore());
        $shippingIncludeTax = Mage::helper('tax')->shippingPriceIncludesTax($quote->getStore());
        // If order is B2B, set $priceIncludeTax to true
        if ((bool) Mage::getSingleton('core/session')->getIsLengowB2b()) {
            $priceIncludeTax = true;
            $shippingIncludeTax = true;
        }
        // add product in quote
        $quote->addLengowProducts($products, $priceIncludeTax);
        // get shipping cost with tax
        $shippingCost = $this->_processingFee + $this->_shippingCost;
        // if shipping cost not include tax -> get shipping cost without tax
        if (!$shippingIncludeTax) {
            $basedOn = Mage::getStoreConfig(
                Mage_Tax_Model_Config::CONFIG_XML_PATH_BASED_ON,
                $quote->getStore()
            );
            $countryId = $basedOn === 'shipping' ? $shippingAddress->getCountryId() : $billingAddress->getCountryId();
            $shippingTaxClass = Mage::getStoreConfig(
                Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS,
                $quote->getStore()
            );
            $taxCalculator = Mage::getModel('tax/calculation');
            $taxRequest = new Varien_Object();
            $taxRequest->setCountryId($countryId)
                ->setCustomerClassId($customer->getTaxClassId())
                ->setProductClassId($shippingTaxClass);
            $taxRate = (float) $taxCalculator->getRate($taxRequest);
            $taxShippingCost = (float) $taxCalculator->calcTaxAmount($shippingCost, $taxRate, true);
            $shippingCost -= $taxShippingCost;
        }
        // update shipping rates for current order
        $rates = $quote->getShippingAddress()
            ->setCollectShippingRates(true)
            ->collectShippingRates()
            ->getShippingRatesCollection();
        $shippingMethod = $this->_updateRates($rates, $shippingCost);
        // set shipping price and shipping method for current order
        $quote->getShippingAddress()
            ->setShippingPrice($shippingCost)
            ->setShippingMethod($shippingMethod);
        // collect totals
        $quote->collectTotals();
        // re-adjust cents for item quote
        // conversion Tax Include > Tax Exclude > Tax Include maybe make 0.01 amount error
        if (!$priceIncludeTax && (float) $quote->getGrandTotal() !== $this->_orderAmount) {
            $quoteItems = $quote->getAllItems();
            foreach ($quoteItems as $item) {
                $lengowProduct = $products[(string) $item->getProduct()->getId()];
                if ($lengowProduct['amount'] !== (float) $item->getRowTotalInclTax()) {
                    $diff = $lengowProduct['amount'] - $item->getRowTotalInclTax();
                    $item->setPriceInclTax($item->getPriceInclTax() + ($diff / $item->getQty()));
                    $item->setBasePriceInclTax($item->getPriceInclTax());
                    $item->setPrice($item->getPrice() + ($diff / $item->getQty()));
                    $item->setOriginalPrice($item->getPrice());
                    $item->setRowTotal($item->getRowTotal() + $diff);
                    $item->setBaseRowTotal($item->getRowTotal());
                    $item->setRowTotalInclTax($lengowProduct['amount']);
                    $item->setBaseRowTotalInclTax($item->getRowTotalInclTax());
                }
            }
        }
        // get payment information
        $paymentInfo = '';
        if (!empty($this->_orderData->payments)) {
            $payment = $this->_orderData->payments[0];
            $paymentInfo .= ' - ' . $payment->type;
            if (isset($payment->payment_terms->external_transaction_id)) {
                $paymentInfo .= ' - ' . $payment->payment_terms->external_transaction_id;
            }
        }
        // set payment method lengow
        $quote->getPayment()->importData(
            array(
                'method' => 'lengow',
                'marketplace' => $this->_orderData->marketplace . $paymentInfo,
            )
        );
        if ($noTax) {
            $this->_shippingTaxAmount = $quote->getShippingAddress()->getShippingTaxAmount();
            $quote->getShippingAddress()
                  ->setAppliedTaxes(array())
                  ->setTaxAmount(0)
                  ->setBaseTaxAmount(0)
                  ->setShippingTaxAmount(0)
                  ->setBaseShippingTaxAmount(0);
        }
        $quote->save();
        return $quote;
    }

    /**
     * Update Rates with shipping cost
     *
     * @param Mage_Sales_Model_Quote_Address_Rate $rates Magento rates
     * @param float $shippingCost shipping cost
     * @param string|null $shippingMethod Magento shipping method
     * @param boolean $first stop recursive effect
     *
     * @return boolean
     */
    private function _updateRates($rates, $shippingCost, $shippingMethod = null, $first = true)
    {
        if (!$shippingMethod) {
            $shippingMethod = $this->_configHelper->get(
                Lengow_Connector_Helper_Config::DEFAULT_IMPORT_CARRIER_ID,
                $this->_storeId
            );
        }
        if (empty($shippingMethod)) {
            $shippingMethod = 'lengow_lengow';
        }
        foreach ($rates as &$rate) {
            // make sure the chosen shipping method is correct
            if ($rate->getCode() === $shippingMethod) {
                if ((float) $rate->getPrice() !== $shippingCost) {
                    $rate->setPrice($shippingCost);
                    $rate->setCost($shippingCost);
                }
                return $rate->getCode();
            }
        }
        // stop recursive effect
        if (!$first) {
            return 'lengow_lengow';
        }
        // get lengow shipping method if selected shipping method is unavailable
        $this->_helper->log(
            Lengow_Connector_Helper_Data::CODE_IMPORT,
            $this->_helper->setLogMessage('log.import.shipping_method_unavailable'),
            $this->_logOutput,
            $this->_marketplaceSku
        );
        return $this->_updateRates($rates, $shippingCost, 'lengow_lengow', false);
    }

    /**
     * Create order
     *
     * @param Lengow_Connector_Model_Import_Quote $quote Lengow quote instance
     * @param Lengow_Connector_Model_Import_Order $orderLengow Lengow order instance
     * @param bool $noTax Should apply shipping tax
     *
     * @throws Lengow_Connector_Model_Exception order failed with quote
     *
     * @return Mage_Sales_Model_Order
     */
    private function _makeOrder(Lengow_Connector_Model_Import_Quote $quote, $orderLengow, $noTax = false)
    {
        $currencyIsoA3 = (string) $this->_orderData->currency->iso_a3;
        $additionalData = array(
            Lengow_Connector_Model_Import_Order::FIELD_LEGACY_FROM_LENGOW => true,
            Lengow_Connector_Model_Import_Order::FIELD_LEGACY_FOLLOW_BY_LENGOW => true,
            Lengow_Connector_Model_Import_Order::FIELD_LEGACY_MARKETPLACE_NAME =>
                (string) $this->_orderData->marketplace,
            Lengow_Connector_Model_Import_Order::FIELD_LEGACY_MARKETPLACE_SKU => $this->_marketplaceSku,
            Lengow_Connector_Model_Import_Order::FIELD_LEGACY_DELIVERY_ADDRESS_ID => $this->_deliveryAddressId,
            Lengow_Connector_Model_Import_Order::FIELD_LEGACY_IS_REIMPORTED => false,
            'global_currency_code' => $currencyIsoA3,
            'base_currency_code' => $currencyIsoA3,
            'store_currency_code' => $currencyIsoA3,
            'order_currency_code' => $currencyIsoA3,
        );
        $service = Mage::getModel('sales/service_quote', $quote);
        $service->setOrderData($additionalData);
        /** @var Mage_Sales_Model_Order $order */
        if (method_exists($service, 'submitAll')) {
            $service->submitAll();
            $order = $service->getOrder();
        } else {
            $order = $service->submit();
        }
        if (!$order) {
            throw new Lengow_Connector_Model_Exception(
                $this->_helper->setLogMessage('lengow_log.exception.order_failed_with_quote')
            );
        }
        // modify order dates to use actual dates
        $order->setCreatedAt($this->_orderDate);
        $order->setUpdatedAt($this->_orderDate);
        $order->save();
        // update lengow_order table directly after creating the Magento order
        $orderLengow->updateOrder(
            array(
                Lengow_Connector_Model_Import_Order::FIELD_ORDER_ID => $order->getId(),
                Lengow_Connector_Model_Import_Order::FIELD_ORDER_SKU => $order->getIncrementId(),
                Lengow_Connector_Model_Import_Order::FIELD_ORDER_PROCESS_STATE =>
                    $this->_modelOrder->getOrderProcessState($this->_orderStateLengow),
                Lengow_Connector_Model_Import_Order::FIELD_ORDER_LENGOW_STATE => $this->_orderStateLengow,
                Lengow_Connector_Model_Import_Order::FIELD_IS_IN_ERROR => 0,
            )
        );
        $this->_helper->log(
            Lengow_Connector_Helper_Data::CODE_IMPORT,
            $this->_helper->setLogMessage('log.import.lengow_order_updated'),
            $this->_logOutput,
            $this->_marketplaceSku
        );
        // re-adjust cents for total and shipping cost
        // conversion Tax Include > Tax Exclude > Tax Include maybe make 0.01 amount error
        $priceIncludeTax = Mage::helper('tax')->priceIncludesTax($quote->getStore());
        $shippingIncludeTax = Mage::helper('tax')->shippingPriceIncludesTax($quote->getStore());
        if ((!$priceIncludeTax || !$shippingIncludeTax) && !$noTax) {
            if ((float) $order->getGrandTotal() !== $this->_orderAmount) {
                // check Grand Total
                $diff = $this->_orderAmount - $order->getGrandTotal();
                $order->setGrandTotal($this->_orderAmount);
                $order->setBaseGrandTotal($order->getGrandTotal());
                // if the difference is only on the grand total, removing the difference of shipping cost
                if ((float) ($order->getSubtotalInclTax() + $order->getShippingInclTax()) === $this->_orderAmount) {
                    $order->setShippingAmount($order->getShippingAmount() + $diff);
                    $order->setBaseShippingAmount($order->getShippingAmount());
                } else {
                    // check Shipping Cost
                    $diffShipping = 0;
                    $shippingCost = $this->_processingFee + $this->_shippingCost;
                    if ((float) $order->getShippingInclTax() !== $shippingCost) {
                        $diffShipping = ($shippingCost - $order->getShippingInclTax());
                        $order->setShippingAmount($order->getShippingAmount() + $diffShipping);
                        $order->setBaseShippingAmount($order->getShippingAmount());
                        $order->setShippingInclTax($shippingCost);
                        $order->setBaseShippingInclTax($order->getShippingInclTax());
                    }
                    // update Subtotal without shipping cost
                    $order->setSubtotalInclTax($order->getSubtotalInclTax() + ($diff - $diffShipping));
                    $order->setBaseSubtotalInclTax($order->getSubtotalInclTax());
                    $order->setSubtotal($order->getSubtotal() + ($diff - $diffShipping));
                    $order->setBaseSubtotal($order->getSubtotal());
                }
            }
            $order->save();
        }
        // generate invoice for order
        if ($order->canInvoice()) {
            $this->_modelOrder->toInvoice($order);
        }
        $carrierName = $this->_carrierName;
        if ($carrierName === null || $carrierName === 'None') {
            $carrierName = $this->_carrierMethod;
        }
        $order->setShippingDescription(
            $order->getShippingDescription() . ' [marketplace shipping method : ' . $carrierName . ']'
        );
        // if order is B2B, remove shipping tax amount from grand total
        $order->setGrandTotal($order->getGrandTotal() - $this->_shippingTaxAmount);
        $this->_shippingTaxAmount = 0;
        $order->save();
        return $order;
    }

    /**
     * Save order line in lengow orders line table
     *
     * @param Mage_Sales_Model_Order $order Magento order instance
     * @param array $products Lengow products from Api
     */
    private function _saveLengowOrderLine($order, $products)
    {
        $orderLineSaved = false;
        foreach ($products as $product) {
            foreach ($product['order_line_ids'] as $idOrderLine) {
                Mage::getModel('lengow/import_orderline')->createOrderLine(
                    array(
                        Lengow_Connector_Model_Import_Orderline::FIELD_ORDER_ID => (int) $order->getId(),
                        Lengow_Connector_Model_Import_Orderline::FIELD_ORDER_LINE_ID => $idOrderLine,
                    )
                );
                $orderLineSaved .= !$orderLineSaved ? $idOrderLine : ' / ' . $idOrderLine;
            }
        }
        if ($orderLineSaved) {
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_IMPORT,
                $this->_helper->setLogMessage(
                    'log.import.lengow_order_line_saved',
                    array('order_line_saved' => $orderLineSaved)
                ),
                $this->_logOutput,
                $this->_marketplaceSku
            );
        } else {
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_IMPORT,
                $this->_helper->setLogMessage('log.import.lengow_order_line_not_saved'),
                $this->_logOutput,
                $this->_marketplaceSku
            );
        }
    }

    /**
     * Checks and places the order in complete status in Magento
     *
     * @param Mage_Sales_Model_Order $order Magento order instance
     *
     * @throws Exception
     */
    private function _updateStateToShip($order)
    {
        if ($this->_orderStateLengow === Lengow_Connector_Model_Import_Order::STATE_SHIPPED
            || $this->_orderStateLengow === Lengow_Connector_Model_Import_Order::STATE_CLOSED
        ) {
            $this->_modelOrder->toShip(
                $order,
                $this->_carrierName,
                $this->_carrierMethod,
                $this->_trackingNumber
            );
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_IMPORT,
                $this->_helper->setLogMessage(
                    'log.import.order_state_updated',
                    array('state_name' => Mage_Sales_Model_Order::STATE_COMPLETE)
                ),
                $this->_logOutput,
                $this->_marketplaceSku
            );
        }
    }

    /**
     * Add quantity back to stock
     *
     * @param array $products Lengow products from Api
     */
    private function _addQuantityBack($products)
    {
        // add quantity back for re-imported order and order shipped by marketplace
        if ($this->_isReimported
            || ($this->_shippedByMp
                && !$this->_configHelper->get(
                    Lengow_Connector_Helper_Config::SHIPPED_BY_MARKETPLACE_STOCK_ENABLED,
                    $this->_storeId
                )
            )
        ) {
            $messageKey = $this->_isReimported
                ? 'log.import.quantity_back_reimported_order'
                : 'log.import.quantity_back_shipped_by_marketplace';
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_IMPORT,
                $this->_helper->setLogMessage($messageKey),
                $this->_logOutput,
                $this->_marketplaceSku
            );
            foreach ($products as $productId => $product) {
                Mage::getModel('cataloginventory/stock')->backItemQty($productId, $product['quantity']);
            }
        }
    }

}
