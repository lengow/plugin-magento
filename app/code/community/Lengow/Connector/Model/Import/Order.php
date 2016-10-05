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
     * @return Lengow_Connector_Model_Import_Order
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
        if (!array_key_exists('order_process_state', $params)) {
            $this->setData('order_process_state', self::PROCESS_STATE_NEW);
        }
        if (!$this->getCreatedAt()) {
            $this->setData('created_at', Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s'));
        }
        return $this->save();
    }

    /**
     * Update Lengow order
     *
     * @param array $params
     *
     * @return Lengow_Connector_Model_Import_Order
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
     * @param string  $lengow_id           Lengow order id
     * @param string  $markeplace_name     marketplace name
     * @param integer $delivery_address_id delivery address id
     * @param string  $marketplace_legacy  old marketplace name for v2 compatibility
     *
     * @return mixed
     */
    public function getOrderIdIfExist($marketplace_sku, $marketplace_name, $delivery_address_id, $marketplace_legacy)
    {
        // V2 compatibility
        $in = is_null($marketplace_legacy) ? array($marketplace) : array($marketplace, strtolower($marketplace_legacy));

        // get order id from Magento flat order table
        $results = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToFilter('order_id_lengow', $marketplace_sku)
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
     * @param string  $marketplace_sku     Lengow order id
     * @param integer $delivery_address_id Id delivery address
     * @param string  $type                Type (import or send)
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
                'lengow/import_order',
                '`lengow/import_order`.id=main_table.order_lengow_id',
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
     * @param integer $order_id            magento order id
     * @param string  $delivery_address_id delivery address id
     *
     * @return mixed
     */
    public function getOrderIdWithDeliveryAddress($order_id, $delivery_address_id)
    {
        // get marketplace_sku from Magento flat order table
        $results = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToFilter('entity_id', $order_id)
            ->addAttributeToFilter('delivery_address_id_lengow', $delivery_address_id)
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
     * @param string $marketplace_sku
     * @param string $marketplace_name
     *
     * @return array
     */
    public function getAllOrderIds($marketplace_sku, $marketplace_name)
    {
        $results = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToFilter('order_id_lengow', $marketplace_sku)
            ->addAttributeToFilter('marketplace_lengow', $marketplace_name)
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
     * @param string  $marketplace_sku     marketplace sku
     * @param integer $delivery_address_id delivery address id
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
            return (int)$results[0]['id'];
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
            return (int)$results[0]['id'];
        }
        return false;
    }


    /**
     * Get unset order by store
     *
     * @param integer $store_id Magento store id
     *
     * @return mixed
     */
    public function getUnsentOrderByStore($store_id)
    {
        // Compatibility for version 1.5
        if (Mage::getVersion() < '1.6.0.0') {
            $results = $this->getCollection()
                ->join(
                    'sales/order',
                    'entity_id=main_table.order_id',
                    array('store_id' => 'store_id', 'follow_by_lengow' => 'follow_by_lengow', 'state' => 'state')
                )
                ->addFieldToFilter('`sales/order`.store_id', $store_id)
                ->addFieldToFilter('follow_by_lengow', array('eq' => 1))
                ->addFieldToFilter('state', array(array('in' => array('cancel', 'complete'))))
                ->addFieldToFilter('order_process_state', array('eq' => 1))
                ->addFieldToFilter('is_in_error', array('eq' => 0))
                ->getData();
        } else {
            $results = $this->getCollection()
                ->join(
                    array('magento_order' => 'sales/order'),
                    'magento_order.entity_id=main_table.order_id',
                    array('store_id' => 'store_id', 'follow_by_lengow' => 'follow_by_lengow', 'state' => 'state')
                )
                ->addFieldToFilter('magento_order.store_id', $store_id)
                ->addFieldToFilter('magento_order.follow_by_lengow', array('eq' => 1))
                ->addFieldToFilter('magento_order.state', array(array('in' => array('cancel', 'complete'))))
                ->addFieldToFilter('main_table.order_process_state', array('eq' => 1))
                ->addFieldToFilter('main_table.is_in_error', array('eq' => 0))
                ->getData();
        }
        if (count($results) > 0) {
            $unsent_orders = array();
            foreach ($results as $result) {
                if (!Mage::getModel('lengow/import_action')->getActiveActionByOrderId($result['order_id'])) {
                    $unsent_orders[] = array(
                        'order_id' => $result['order_id'],
                        'action'   => $result['state'] == 'cancel' ? 'cancel' : 'ship'
                    );
                }
            }
            if (count($unsent_orders) > 0) {
                return $unsent_orders;
            }
        }
        return false;
    }

    /**
     * Re-import order lengow
     *
     * @param integer $order_lengow_id
     *
     * @return mixed
     */
    public function reImportOrder($order_lengow_id)
    {
        $order_lengow = Mage::getModel('lengow/import_order')->load($order_lengow_id);
        if ($order_lengow->getData('order_process_state') == 0 && $order_lengow->getData('is_in_error') == 1) {
            $params =  array(
                'type'                => 'manual',
                'order_lengow_id'     => $order_lengow_id,
                'marketplace_sku'     => $order_lengow->getData('marketplace_sku'),
                'marketplace_name'    => $order_lengow->getData('marketplace_name'),
                'delivery_address_id' => $order_lengow->getData('delivery_address_id'),
                'store_id'            => $order_lengow->getData('store_id')
            );
            $import = Mage::getModel('lengow/import', $params);
            $result = $import->exec();
            return $result;
        }
        return false;
    }

    /**
     * Re-send order lengow
     *
     * @param integer $order_lengow_id
     *
     * @return mixed
     */
    public function reSendOrder($order_lengow_id)
    {
        $order_lengow = Mage::getModel('lengow/import_order')->load($order_lengow_id);
        if ($order_lengow->getData('order_process_state') == 1 && $order_lengow->getData('is_in_error') == 1) {
            $order_id = $order_lengow->getData('order_id');
            if (!is_null($order_id)) {
                $action = Mage::getModel('lengow/import_action')->getLastOrderActionType($order_id);
                $action = $action ? $action : 'ship';
                $order = Mage::getModel('sales/order')->load($order_id);
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
     * @param Mage_Sales_Model_Order $order Magento Order
     *
     * @return mixed
     */
    public function cancelAndReImportOrder($order)
    {
        if (!$this->isReimported($order)) {
            return false;
        }
        $params =  array(
            'marketplace_sku'     => $order->getData('order_id_lengow'),
            'marketplace_name'    => $order->getData('marketplace_lengow'),
            'delivery_address_id' => $order->getData('delivery_address_id_lengow'),
            'store_id'            => $order->getData('store_id')
        );
        $import = Mage::getModel('lengow/import', $params);
        $result = $import->exec();
        if ((isset($result['order_id']) && $result['order_id'] != $order->getData('order_id'))
            && (isset($result['order_new']) && $result['order_new'])
        ) {
            $order->addData(array(
                'is_reimported_lengow' => 0,
                'follow_by_lengow'     => 0
            ));
            // if state != STATE_COMPLETE or != STATE_CLOSED
            $order->setState('lengow_technical_error', 'lengow_technical_error');
            $order->setData('status', 'lengow_technical_error');
            $order->save();
            return (int)$result['order_id'];
        }
        $order->addData(array('is_reimported_lengow' => 0));
        $order->save();
        return false;
    }

    /**
     * Mark order as is_reimported in sales_flat_order table
     *
     * @param Mage_Sales_Model_Order $order Magento Order
     *
     * @return boolean
     */
    public function isReimported($order)
    {
        $order->addData(array('is_reimported_lengow' => 1));
        $order->save();
        //check success update in BDD
        if ($order->getData('is_reimported_lengow') == 1) {
            return true;
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
     * @return mixed
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
     * @param mixed                  $order_data         order data
     * @param mixed                  $package_data       package data
     * @param mixed                  $order_lengow_id    lengow order id or false
     *
     * @return mixed (Processing, Complete, Canceled or false)
     */
    public function updateState($order, $order_state_lengow, $order_data, $package_data, $order_lengow_id)
    {
        // Finish actions if lengow order is shipped, closed or cancel
        $order_process_state = $this->getOrderProcessState($order_state_lengow);
        $trackings = $package_data->delivery->trackings;
        if ($order_process_state == self::PROCESS_STATE_FINISH) {
            Mage::getModel('lengow/import_action')->finishAllActions($order->getId());
        }
        // Update Lengow order if necessary
        if ($order_lengow_id) {
            $order_lengow = Mage::getModel('lengow/import_order')->load($order_lengow_id);
            $params = array();
            if ($order_lengow->getData('order_lengow_state') != $order_state_lengow) {
                $params['order_lengow_state'] = $order_state_lengow;
                $params['extra'] = Mage::helper('core')->jsonEncode($order_data);
                $params['tracking'] = count($trackings) > 0 ? (string)$trackings[0]->number : null;
            }
            if ($order_process_state == self::PROCESS_STATE_FINISH) {
                if ((int)$order_lengow->getData('order_process_state') != $order_process_state) {
                    $params['order_process_state'] = $order_process_state;
                }
                if ((int)$order_lengow->getData('is_in_error') != 0) {
                    $params['is_in_error'] = 0;
                }
            }
            if (count($params) > 0) {
                $order_lengow->updateOrder($params);
            }
            unset($order_lengow);
        }
        // Update Magento order's status only if in accepted, waiting_shipment, shipped, closed or cancel
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
     * @param Mage_Sales_Model_Order           $order     Magento Order
     * @param Lengow_Connector_Model_Connector $connector Lengow Connector for API calls
     *
     * @return boolean
     */
    public function synchronizeOrder($order, $connector = null)
    {
        if ($order->getData('from_lengow') != 1) {
            return false;
        }
        $store_id = $order->getStore()->getId();
        $account_id = Mage::helper('lengow_connector/config')->get('account_id', $store_id);
        if (is_null($connector)) {
            $connector = Mage::getModel('lengow/connector');
            $connector_is_valid = $connector->getConnectorByStore($store_id);
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
                $this->checkAndChangeMarketplaceName($order, $connector);
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
            $connector_is_valid = $connector->getConnectorByStore($store_id);
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
        $results = json_decode($results);
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
     * Send Order action
     *
     * @param string                          $action   Lengow Actions (ship or cancel)
     * @param Mage_Sales_Model_Order          $order    Magento Order
     * @param Mage_Sales_Model_Order_Shipment $shipment Magento Shipment
     */
    public function callAction($action, $order, $shipment = null)
    {
        $success = true;
        $helper = Mage::helper('lengow_connector');
        $helper->log(
            'API-OrderAction',
            $helper->setLogMessage('log.order_action.try_to_send_action', array(
                'action'   => $action,
                'order_id' => $order->getIncrementId()
            )),
            false,
            $order->getData('order_id_lengow')
        );
        if ($order->getData('from_lengow') != 1) {
            $success = false;
        }
        if ($success) {
            $order_lengow_id = $this->getLengowOrderIdWithOrderId($order->getId());
            // Finish all order errors before API call
            if ($order_lengow_id) {
                $order_error = Mage::getModel('lengow/import_ordererror');
                $order_error->finishOrderErrors($order_lengow_id, 'send');
                $order_lengow = Mage::getModel('lengow/import_order')->load($order_lengow_id);
                if ($order_lengow->getData('is_in_error') == 1) {
                    $order_lengow->updateOrder(array('is_in_error' => 0));
                }
            }
            try {
                // compatibility V2
                if ($order->getData('feed_id_lengow') != 0) {
                    $this->checkAndChangeMarketplaceName($order);
                }
                $marketplace = Mage::helper('lengow_connector/import')->getMarketplaceSingleton(
                    (string)$order->getData('marketplace_lengow'),
                    $order->getStore()->getId()
                );
                if ($marketplace->containOrderLine($action)) {
                    $order_line_collection = Mage::getModel('lengow/import_orderline')
                        ->getOrderLineByOrderID($order->getId());
                    // compatibility V2 and security
                    if (!$order_line_collection) {
                        $order_line_collection = $this->getOrderLineByApi($order);
                    }
                    if (!$order_line_collection) {
                        throw new Lengow_Connector_Model_Exception(
                            $helper->setLogMessage('lengow_log.exception.order_line_required')
                        );
                    }
                    $results = array();
                    foreach ($order_line_collection as $order_line) {
                        $results[] = $marketplace->callAction($action, $order, $shipment, $order_line['order_line_id']);
                    }
                    $success = !in_array(false, $results);
                } else {
                    $success = $marketplace->callAction($action, $order, $shipment);
                }
            } catch (Lengow_Connector_Model_Exception $e) {
                $error_message = $e->getMessage();
            } catch (Exception $e) {
                $error_message = '[Magento error]: "'.$e->getMessage().'" '.$e->getFile().' line '.$e->getLine();
            }
            if (isset($error_message)) {
                if ($order_lengow_id) {
                    if ((int)$order_lengow->getData('order_process_state') != self::PROCESS_STATE_FINISH) {
                        $order_lengow->updateOrder(array('is_in_error' => 1));
                        $order_error->createOrderError(array(
                            'order_lengow_id' => $order_lengow_id,
                            'message'         => $error_message,
                            'type'            => 'send'
                        ));
                    }
                    unset($order_lengow);
                }
                $decoded_message = $helper->decodeLogMessage($error_message, 'en_GB');
                $helper->log(
                    'API-OrderAction',
                    $helper->setLogMessage('log.order_action.call_action_failed', array(
                        'decoded_message' => $decoded_message
                    )),
                    false,
                    $order->getData('order_id_lengow')
                );
                $success = false;
            }
        }
        if ($success) {
            $message = $helper->setLogMessage('log.order_action.action_send', array(
                'action'   => $action,
                'order_id' => $order->getIncrementId()
            ));
        } else {
            $message = $helper->setLogMessage('log.order_action.action_not_send', array(
                'action'   => $action,
                'order_id' => $order->getIncrementId()
            ));
        }
        $helper->log('API-OrderAction', $message, false, $order->getData('order_id_lengow'));
        return $success;
    }

    /**
     * Get order line by API
     *
     * @param Mage_Sales_Model_Order $order Magento Order
     *
     * @return mixed
     */
    public function getOrderLineByApi($order)
    {
        if ($order->getData('from_lengow') != 1) {
            return false;
        }
        $order_lines = array();
        $store_id = $order->getStore()->getId();
        $account_id = Mage::helper('lengow_connector/config')->get('account_id', $store_id);
        $connector = Mage::getModel('lengow/connector');
        $results = $connector->queryApi(
            'get',
            '/v3.0/orders',
            $store_id,
            array(
                'marketplace_order_id' => $order->getData('order_id_lengow'),
                'marketplace'          => $order->getData('marketplace_lengow')
            )
        );
        if (isset($results->count) && $results->count == 0) {
            return false;
        }
        $order_data = $results->results[0];
        foreach ($order_data->packages as $package) {
            $product_lines = array();
            foreach ($package->cart as $product) {
                $product_lines[] = array('order_line_id' => (string)$product->marketplace_order_line_id);
            }
            if ($order->getData('delivery_address_id_lengow') == 0) {
                return count($product_lines) > 0 ? $product_lines : false;
            } else {
                $order_lines[(int)$package->delivery->id] = $product_lines;
            }
        }
        $return = $order_lines[$order->getData('delivery_address_id_lengow')];
        return count($return) > 0 ? $return : false;
    }

    /**
     * Count order lengow with error
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
        $core_resource = Mage::getSingleton('core/resource');
        $sfo = $core_resource->getTableName('sales_flat_order');
        $connection = $core_resource->getConnection('core_read');
        $query = 'SELECT COUNT(entity_id) as total FROM '.$sfo.'
            WHERE ('.$sfo.'.from_lengow = 1 AND '.$sfo.'.follow_by_lengow = 1)';
        $rows = $connection->fetchCol($query);
        return $rows[0];
    }

    /**
     * Count old lengow order
     *
     * @param boolean $is_processing get only order in processing
     *
     * @return integer
     */
    public function countNotMigrateOrder($is_processing = true)
    {
        $core_resource = Mage::getSingleton('core/resource');
        $sfo = $core_resource->getTableName('sales_flat_order');
        $lo = $core_resource->getTableName('lengow_order');
        $connection = $core_resource->getConnection('core_read');
        $processing = $is_processing ? ' AND '.$sfo.'.state = \''.$this->getOrderState('accepted').'\'' : '';
        $query = 'SELECT COUNT(entity_id) as total FROM '.$sfo.'
            LEFT JOIN '.$lo.' AS `lo` ON lo.order_id = '.$sfo.'.entity_id
            WHERE ('.$sfo.'.from_lengow = 1 AND '.$sfo.'.follow_by_lengow = 1  AND lo.order_id IS NULL'.$processing.')';
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
     * @param boolean $is_processing migrate only order in processing
     */
    public function migrateOldOrder($is_processing = true)
    {
        $total = $this->countNotMigrateOrder($is_processing);
        if ($total > 0) {
            $per_page = 500;
            $nb_page = ceil($total / $per_page);
            for ($i = 1; $i <= $nb_page; $i++) {
                $order_collection = Mage::getModel('sales/order')->getCollection()
                    ->addAttributeToFilter('from_lengow', 1)
                    ->addAttributeToFilter('follow_by_lengow', 1);
                if ($is_processing) {
                    $order_collection->addAttributeToFilter('state', $this->getOrderState('accepted'));
                }
                $order_collection->getSelect()->limit($per_page, ($i-1)*$per_page);
                foreach ($order_collection as $order) {
                    $old_order = Mage::getModel('lengow/import_order')->getCollection()
                        ->addFieldToFilter('order_id', $order->getId())->getFirstItem();
                    if ($old_order->getId() > 0) {
                        unset($old_order);
                        continue;
                    }
                    // Get old Lengow informations
                    $lengow_node = json_decode($order->getXmlNodeLengow());
                    $feed_id = isset($lengow_node->idFlux) ? $lengow_node->idFlux : $order->getFeedIdLengow();
                    $marketplace_sku = isset($lengow_node->order_id_lengow)
                        ? $lengow_node->order_id
                        : $order->getOrderIdLengow();
                    $country_iso = isset($lengow_node->delivery_address->delivery_country_iso)
                        ? $lengow_node->delivery_address->delivery_country_iso
                        : '';
                    $marketplace_name = isset($lengow_node->marketplace)
                        ? $lengow_node->marketplace
                        : $order->getMarketplaceLengow();
                    $send_by_marketplace = isset($lengow_node->tracking_informations->tracking_deliveringByMarketPlace)
                        ? (bool)$lengow_node->tracking_informations->tracking_deliveringByMarketPlace
                        : 0;
                    $commission = isset($lengow_node->commission) ? $lengow_node->commission : 0;
                    if (isset($lengow_node->order_purchase_date) && isset($lengow_node->order_purchase_heure)) {
                        $order_date = $lengow_node->order_purchase_date.' '.$lengow_node->order_purchase_heure;
                    } else {
                        $order_date = $order->getCreatedAt();
                    }
                    if ($country_iso=='') {
                        $address = $order->getShippingAddress();
                        $country_iso = $address->getCountryId();
                    }
                    $order_process_state = $order->getState() == $this->getOrderState('accepted')
                        ? self::PROCESS_STATE_IMPORT
                        : self::PROCESS_STATE_FINISH;
                    // create new lengow order
                    $new_order = Mage::getModel('lengow/import_order');
                    $new_order->createOrder(array(
                        'order_id'             => $order->getId(),
                        'order_sku'            => $order->getIncrementId(),
                        'store_id'             => $order->getStoreId(),
                        'feed_id'              => $feed_id,
                        'delivery_address_id'  => $order->getDeliveryAddressIdLengow(),
                        'delivery_country_iso' => $country_iso,
                        'marketplace_sku'      => $marketplace_sku,
                        'marketplace_name'     => $marketplace_name,
                        'marketplace_label'    => $marketplace_name,
                        'order_lengow_state'   => 'waiting_shipment',
                        'order_process_state'  => $order_process_state,
                        'order_date'           => $order_date,
                        'order_item'           => $order->getTotalItemCount(),
                        'currency'             => $order->getBaseCurrencyCode(),
                        'total_paid'           => $order->getTotalInvoiced(),
                        'commission'           => $commission,
                        'customer_name'        => $order->getCustomerFirstname().' '.$order->getCustomerLastname(),
                        'customer_email'       => $order->getCustomerEmail(),
                        'carrier'              => $order->getCarrierLengow(),
                        'carrier_method'       => $order->getCarrierMethodLengow(),
                        'carrier_tracking'     => $order->getCarrierTrackingLengow(),
                        'sent_marketplace'     => $send_by_marketplace ,
                        'created_at'           => $order->getCreatedAt(),
                        'updated_at'           => $order->getUpdateAt(),
                        'message'              => $order->getMessageLengow(),
                        'extra'                => $order->getXmlNodeLengow()
                    ));
                    unset($old_order);
                }
            }
        }
    }
}
