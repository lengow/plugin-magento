<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Model_Import_Order extends Mage_Core_Model_Abstract
{
    /**
    * integer order process state for new order not imported
    */
    const PROCESS_STATE_NEW = 0;

    /**
    * integer order process state for order imported
    */
    const PROCESS_STATE_IMPORT = 1;

    /**
    * integer order process state for order finished
    */
    const PROCESS_STATE_FINISH = 2;

    /**
     * @var array $_field_list field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    protected $_field_list = array(
        'order_id'              => array('required' => false, 'updated' => true),
        'order_sku'             => array('required' => false, 'updated' => true),
        'store_id'              => array('required' => true, 'updated' => false),
        'feed_id'               => array('required' => false, 'updated' => true),
        'delivery_address_id'   => array('required' => true, 'updated' => false),
        'delivery_country_iso'  => array('required' => false, 'updated' => true),
        'marketplace_sku'       => array('required' => true, 'updated' => false),
        'marketplace_name'      => array('required' => true, 'updated' => false),
        'marketplace_label'     => array('required' => true, 'updated' => false),
        'order_lengow_state'    => array('required' => true, 'updated' => true),
        'order_process_state'   => array('required' => false, 'updated' => true),
        'order_date'            => array('required' => true, 'updated' => false),
        'order_item'            => array('required' => false, 'updated' => true),
        'currency'              => array('required' => false, 'updated' => true),
        'total_paid'            => array('required' => false, 'updated' => true),
        'commission'            => array('required' => false, 'updated' => true),
        'customer_name'         => array('required' => false, 'updated' => true),
        'customer_email'        => array('required' => false, 'updated' => true),
        'carrier'               => array('required' => false, 'updated' => true),
        'carrier_method'        => array('required' => false, 'updated' => true),
        'carrier_tracking'      => array('required' => false, 'updated' => true),
        'carrier_id_relay'      => array('required' => false, 'updated' => true),
        'sent_marketplace'      => array('required' => false, 'updated' => true),
        'is_in_error'           => array('required' => false, 'updated' => true),
        'message'               => array('required' => true, 'updated' => true),
        'extra'                 => array('required' => false, 'updated' => true)
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
     * @param array $params
     *
     */
    public function createOrder($params = array())
    {
        foreach ($this->_field_list as $key => $value) {
            if (!array_key_exists($key, $params) && $value['required']) {
                throw new Lengow_Connector_Model_Exception(
                    Mage::helper('lengow_connector')->setLogMessage('log.import.error_value_required', array(
                        'key'   => $key
                    ))
                );
            }
        }
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        $this->setData('order_process_state', self::PROCESS_STATE_NEW);
        if (!$this->getCreatedAt()) {
            $this->setData('created_at', Mage::getModel('core/date')->date('Y-m-d H:i:s'));
        }
        return $this->save();
    }

    /**
     * Update Lengow order
     *
     * @param array $params
     *
     */
    public function updateOrder($params = array())
    {
        if (!$this->id) {
            return false;
        }
        $updated_fields = $this->getUpdatedFields();
        foreach ($params as $key => $value) {
            if (in_array($key, $updated_fields)) {
                $this->setData($key, $value);
            }
        }
        $this->setData('updated_at', Mage::getModel('core/date')->date('Y-m-d H:i:s'));
        return $this->save();
    }

    /**
     * Get updated fields
     *
     * @return array
     *
     */
    public function getUpdatedFields()
    {
        $updated_fields = array();
        foreach ($this->_field_list as $key => $value) {
            if ($value['updated']) {
                $updated_fields[] = $key;
            }
        }
        return $updated_fields;
    }

    /**
     * if order is already Imported
     *
     * @param string    $lengow_id              Lengow order id
     * @param string    $markeplace_name        marketplace name
     * @param integer   $delivery_address_id    delivery address id
     *
     * @return mixed
     */
    public function getOrderIdIfExist($marketplace_sku, $marketplace_name, $delivery_address_id)
    {
        // get order id from Magento flat order table
        $results = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToFilter('order_id_lengow', $marketplace_sku)
            ->addAttributeToFilter('marketplace_lengow', $marketplace_name)
            ->addAttributeToSelect('entity_id')
            ->addAttributeToSelect('delivery_address_id_lengow')
            ->addAttributeToSelect('feed_id_lengow')
            ->getData();
        if (count($results) > 0) {
            foreach ($results as $result) {
                if ($result['delivery_address_id_lengow'] == 0 && $result['feed_id_lengow'] != 0) {
                    return $result['entity_id'];
                } elseif ($result['delivery_address_id_lengow'] == $delivery_address_id) {
                    return $result['entity_id'];
                }
            }
        }
        return false;
    }

    /**
     * Check if an order has an error
     *
     * @param string    $marketplace_sku        Lengow order id
     * @param integer   $delivery_address_id    Id delivery address
     * @param string    $type                   Type (import or send)
     *
     * @return mixed
     */
    public function orderIsInError($marketplace_sku, $delivery_address_id, $type = 'import')
    {
        $order_error = Mage::getModel('lengow/import_ordererror');
        $error_type = $order_error->getOrderErrorType($type);
        // check if log already exists for the given order id
        $results = $order_error->getCollection()
            ->join(
                array('order'=> 'lengow/import_order'),
                'order.id=main_table.order_lengow_id',
                array('marketplace_sku' => 'marketplace_sku', 'delivery_address_id' => 'delivery_address_id')
            )
            ->addFieldToFilter('marketplace_sku', $marketplace_sku)
            ->addFieldToFilter('delivery_address_id', $delivery_address_id)
            ->addFieldToFilter('type', $error_type)
            ->addFieldToFilter('is_finished', array('eq' => 0))
            ->addFieldToSelect('id')
            ->addFieldToSelect('message')
            ->addFieldToSelect('created_at')
            ->getData();
        if (count($results) == 0) {
            return false;
        }
        return $results[0];
    }

    /**
     * Get Lengow ID with order ID Magento and delivery address ID
     *
     * @param integer   $order_id               magento order id
     * @param string    $delivery_address_id    delivery address id
     *
     * @return mixed
     */
    public function getOrderIdWithDeliveryAddress($order_id, $delivery_address_id)
    {
        // get marketplace_sku from Magento flat order table
        $results = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToFilter('entity_id', $order_id)
            ->addAttributeToFilter('delivery_address_id_lengow', $delivery_address_id)
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
     * @param string $marketplace_sku
     * @param string $marketplace_name
     *
     * @return array
     *
     */
    public function getAllOrderIds($marketplace_sku, $marketplace_name)
    {
        $results = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToFilter('order_id_lengow', $marketplace_sku)
            ->addAttributeToFilter('marketplace_lengow', $marketplace_name)
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
     * @param string   $marketplace_sku         marketplace sku
     * @param integer  $delivery_address_id     delivery address id
     *
     * @return mixed
     */
    public function getLengowOrderId($marketplace_sku, $delivery_address_id)
    {
        $results = $this->getCollection()
            ->addFieldToFilter('marketplace_sku', $marketplace_sku)
            ->addFieldToFilter('delivery_address_id', $delivery_address_id)
            ->addFieldToSelect('id')
            ->getData();
        if (count($results) > 0) {
            return $results[0]['id'];
        }
        return false;
    }

    /**
     * Get ID record from lengow orders table with Magento order Id
     *
     * @param integer $order_id Magento order id
     *
     * @return mixed
     */
    public function getLengowOrderIdWithOrderId($order_id)
    {
        $results = $this->getCollection()
            ->addFieldToFilter('order_id', $order_id)
            ->addFieldToSelect('id')
            ->getData();
        if (count($results) > 0) {
            return $results[0]['id'];
        }
        return false;
    }

    /**
     * Get Magento equivalent to lengow order state
     *
     * @param  string $order_state_lengow lengow state
     *
     * @return string
     */
    public function getOrderState($order_state_lengow)
    {
        switch ($order_state_lengow) {
            case 'new':
            case 'waiting_acceptance':
                return Mage_Sales_Model_Order::STATE_NEW;
                break;
            case 'accepted':
            case 'waiting_shipment':
                return Mage_Sales_Model_Order::STATE_PROCESSING;
                break;
            case 'shipped':
            case 'closed':
                return Mage_Sales_Model_Order::STATE_COMPLETE;
                break;
            case 'refused':
            case 'canceled':
                return Mage_Sales_Model_Order::STATE_CANCELED;
                break;
        }
    }

    /**
     * Get order process state
     *
     * @param string $state state to be matched
     *
     * @return integer
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
                return self::PROCESS_STATE_FINISH;
            default:
                return false;
        }
    }

    /**
     * Create invoice
     *
     * @param Mage_Sales_Model_Order $order
     *
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
     * @param Mage_Sales_Model_Order $order
     * @param string                 $carrier_name
     * @param string                 $carrier_method
     * @param string                 $tracking_number
     *
     */
    public function toShip($order, $carrier_name, $carrier_method, $tracking_number)
    {
        if ($order->canShip()) {
            $shipment = Mage::getModel('sales/service_order', $order)->prepareShipment();
            if ($shipment) {
                $shipment->register();
                $shipment->getOrder()->setIsInProcess(true);
                // Add tracking information
                if (!is_null($tracking_number)) {
                    $track = Mage::getModel('sales/order_shipment_track')
                        ->setNumber($tracking_number)
                        ->setCarrierCode($carrier_name)
                        ->setTitle($carrier_method);
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
     * @param Mage_Sales_Model_Order $order
     *
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
     * @param Mage_Sales_Model_Order $order              Magento Order
     * @param string                 $order_state_lengow lengow status
     * @param mixed                  $package_data       package data
     *
     * @return bool true if order has been updated
     */
    public function updateState($order, $order_state_lengow, $package_data)
    {
        // Update order's status only if in process, shipped, or canceled
        if ($order->getState() != $this->getOrderState($order_state_lengow) && $order->getData('from_lengow') == 1) {
            if ($order->getState() == $this->getOrderState('new')
                && ($order_state_lengow == 'accepted' || $order_state_lengow == 'waiting_shipment')
            ) {
                // Generate invoice
                $this->toInvoice($order);
                return 'Processing';
            } elseif (($order->getState() == $this->getOrderState('accepted')
                || $order->getState() == $this->getOrderState('new'))
                && ($order_state_lengow == 'shipped' || $order_state_lengow == 'closed')
            ) {
                // if order is new -> generate invoice
                if ($order->getState() == $this->getOrderState('new')) {
                    $this->toInvoice();
                }
                $trackings = $package_data->delivery->trackings;
                $this->toShip(
                    $order,
                    (count($trackings) > 0 ? (string)$trackings[0]->carrier : null),
                    (count($trackings) > 0 ? (string)$trackings[0]->method : null),
                    (count($trackings) > 0 ? (string)$trackings[0]->number : null)
                );
                return 'Complete';
            } else {
                if (($order->getState() == $this->getOrderState('new')
                    || $order->getState() == $this->getOrderState('accepted')
                    || $order->getState() == $this->getOrderState('shipped'))
                    && ($order_state_lengow == 'canceled' || $order_state_lengow == 'refused')
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
     * @param Mage_Sales_Model_Order           $order      Magento Order
     * @param Lengow_Connector_Model_Connector $connector  Lengow Connector for API calls
     * @param boolean                          $log_output See log or not
     *
     * @return boolean
     */
    public function synchronizeOrder($order, $connector = null, $log_output = false)
    {
        if ($order->getData('from_lengow') != 1) {
            return false;
        }
        $store_id = $order->getStore()->getId();
        $account_id = Mage::helper('lengow_connector/config')->get('account_id', $store_id);
        if (is_null($connector)) {
            $connector = Mage::getModel('lengow/connector');
            $connector_is_valid = $this->_connector->getConnectorByStore($store_id);
            if (!$connector_is_valid) {
                return false;
            }
        }
        $order_ids = $this->getAllOrderIds($order->getData('order_id_lengow'), $order->getData('marketplace_lengow'));
        if ($order_ids) {
            $magento_ids = array();
            foreach ($order_ids as $order_id) {
                $magento_ids[] = $order_id['entity_id'];
            }
            // compatibility V2
            if ($order->getData('feed_id_lengow') != 0) {
                $this->checkAndChangeMarketplaceName($store_id, $connector);
            }
            $result = $connector->patch(
                '/v3.0/orders',
                array(
                    'account_id'           => $account_id,
                    'marketplace_order_id' => $order->getData('order_id_lengow'),
                    'marketplace'          => $order->getData('marketplace_lengow'),
                    'merchant_order_id'    => $order_ids
                )
            );
            if (is_null($result)
                || (isset($result['detail']) && $result['detail'] == 'Pas trouvé.')
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
     * @param Mage_Sales_Model_Order           $order     Magento Order
     * @param Lengow_Connector_Model_Connector $connector Lengow Connector for API calls
     *
     * @return boolean
     */
    public function checkAndChangeMarketplaceName($order, $connector = null)
    {
        if ($order->getData('from_lengow') != 1) {
            return false;
        }
        $store_id = $order->getStore()->getId();
        $account_id = Mage::helper('lengow_connector/config')->get('account_id', $store_id);
        if (is_null($connector)) {
            $connector = Mage::getModel('lengow/connector');
            $connector_is_valid = $this->_connector->getConnectorByStore($store_id);
            if (!$connector_is_valid) {
                return false;
            }
        }
        $results = $connector->get(
            '/v3.0/orders',
            array(
                'marketplace_order_id' => $order->getData('order_id_lengow'),
                'marketplace'          => $order->getData('marketplace_lengow'),
                'account_id'           => $account_id
            ),
            'stream'
        );
        if (is_null($results)) {
            return false;
        }
        $results = Tools::jsonDecode($results);
        if (isset($results->error)) {
            return false;
        }
        foreach ($results->results as $order) {
            if ($order->getData('marketplace_lengow') != (string)$order->marketplace) {
                $order->setData('marketplace_lengow', (string)$order->marketplace);
                $order->save();
            }
        }
        return true;
    }

    /**
     * Count old lengow order
     */
    public function countNotMigrateOrder()
    {
        $coreResource = Mage::getSingleton('core/resource');
        $saleFlatOrder = $coreResource->getTableName('sales_flat_order');
        $lengowOrder = $coreResource->getTableName('lengow_order');
        $connection = $coreResource->getConnection('core_read');
        $query = $connection->select(array('COUNT(entity_id) as total'));
        $query->from($saleFlatOrder);
        $query->joinleft(array('lo' => $lengowOrder), 'lo.order_id = '.$saleFlatOrder.'.entity_id');
        $query->where($saleFlatOrder.'.from_lengow = 1 AND lo.order_id IS NULL');
        $rows = $connection->fetchCol($query);
        if ($rows) {
            return $rows[0];
        } else {
            return 0;
        }
    }

    /**
     * Migrate old order
     */
    public function migrateOldOrder()
    {
        $perPage = 500;
        $total = $this->countNotMigrateOrder();
        $nbPage = ceil($total / $perPage);
        for ($i = 1; $i <= $nbPage; $i++) {
            $order_collection = Mage::getModel('sales/order')->getCollection()
                ->addAttributeToFilter('from_lengow', 1);
            $order_collection->getSelect()->limit($perPage, ($i-1)*$perPage);
            foreach ($order_collection as $order) {
                $oldOrder = Mage::getModel('lengow/import_order')->getCollection()
                    ->addFieldToFilter('order_id', $order->getId())->getFirstItem();
                if ($oldOrder->getId() > 0) {
                    continue;
                }
                // Get old Lengow informations
                $lengowNode = json_decode($order->getXmlNodeLengow());
                $idFeed = isset($lengowNode->idFlux) ? $lengowNode->idFlux : $order->getFeedIdLengow();
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
                    $orderDate = $lengowNode->order_purchase_date.' '.$lengowNode->order_purchase_heure;
                } else {
                    $orderDate = $order->getCreatedAt();
                }

                if ($countryIso=='') {
                    $address = $order->getShippingAddress();
                    $countryIso = $address->getCountryId();
                }
                // create new lengow order
                $newOrder = Mage::getModel('lengow/import_order');
                $newOrder->createOrder(array(
                    'order_id'             => $order->getId(),
                    'order_sku'            => $order->getIncrementId(),
                    'store_id'             => $order->getStoreId(),
                    'feed_id'              => $idFeed,
                    'delivery_address_id'  => $order->getDeliveryAddressIdLengow(),
                    'delivery_country_iso' => $countryIso,
                    'marketplace_sku'      => $marketplaceSku,
                    'marketplace_name'     => $marketplaceName,
                    'marketplace_label'    => $marketplaceName,
                    'order_lengow_state'   => '',
                    'order_date'           => $orderDate,
                    'order_item'           => $order->getTotalItemCount(),
                    'currency'             => $order->getBaseCurrencyCode(),
                    'total_paid'           => $order->getTotalInvoiced(),
                    'commission'           => $commission,
                    'customer_name'        => $order->getCustomerFirstname().' '.$order->getCustomerLastname(),
                    'customer_email'       => $order->getCustomerEmail(),
                    'carrier'              => $order->getCarrierLengow(),
                    'carrier_method'       => $order->getCarrierMethodLengow(),
                    'carrier_tracking'     => $order->getCarrierTrackingLengow(),
                    'sent_marketplace'     => $sendByMarketplace ,
                    'created_at'           => $order->getCreatedAt(),
                    'updated_at'           => $order->getUpdateAt(),
                    'message'              => $order->getMessageLengow(),
                    'extra'                => $order->getXmlNodeLengow()
                ));
            }
        }
    }
}
