<?php

/**
 * Lengow sync block adminhtml order tab
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Team Connector <team-connector@lengow.com>
 * @copyright   2015 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Block_Adminhtml_Order_Tab extends Mage_Adminhtml_Block_Sales_Order_Abstract implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

    protected function _construct()
    {
        $this->setTemplate('lengow/sales/order/tab/info.phtml');
    }

    /**
     * Retrieve order model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    /**
     * Retrieve source model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getSource()
    {
        return $this->getOrder();
    }


    public function getTabLabel()
    {
        return Mage::helper('sales')->__('Lengow');
    }

    public function getTabTitle()
    {
        return Mage::helper('sales')->__('Lengow');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }

    /**
     * Get fields array
     *
     * @return Array
     */
    public function getFields()
    {
        $fields = array();
        $order = $this->getOrder();
        $helper = Mage::helper('lengow_connector');
        $order_lengow = Mage::getModel('lengow/import_order');
        $order_lengow_id = $order_lengow->getLengowOrderIdWithOrderId($order->getData('entity_id'));
        // Get all Lengow informations
        if ($order_lengow_id) {
            $order_lengow = $order_lengow->load($order_lengow_id);
            $marketplace_sku = $order_lengow->getData('marketplace_sku');
            $marketplace_label = $order_lengow->getData('marketplace_label');
            $feed_id = $order_lengow->getData('feed_id');
            $delivery_address_id = $order_lengow->getData('delivery_address_id');
            $currency = $order_lengow->getData('currency');
            $total_paid = $order_lengow->getData('total_paid');
            $commission = $order_lengow->getData('commission');
            $customer_name = $order_lengow->getData('customer_name');
            $customer_email = $order_lengow->getData('customer_email');
            $carrier = $order_lengow->getData('carrier');
            $carrier_method = $order_lengow->getData('carrier_method');
            $carrier_tracking = $order_lengow->getData('carrier_tracking');
            $carrier_id_relay = $order_lengow->getData('carrier_id_relay');
            $sent_marketplace = $order_lengow->getData('sent_marketplace');
            $imported_at = $helper->getDateInCorrectFormat(strtotime($order_lengow->getData('created_at')));
            $message = $order_lengow->getData('message');
            $extra = $order_lengow->getData('extra');
        } else {
            $marketplace_sku = $order->getData('order_id_lengow');
            $marketplace_label = $order->getData('marketplace_lengow');
            $feed_id = $order->getData('feed_id_lengow');
            $delivery_address_id = $order->getData('delivery_address_id_lengow');
            $currency = $order->getData('base_currency_code');
            $total_paid = $order->getData('total_paid_lengow');
            $commission = $order->getData('fees_lengow');
            $customer_name = $order->getData('customer_firstname').' '.$order->getData('customer_lastname');
            $customer_email = $order->getData('customer_email');
            $carrier = $order->getData('carrier_lengow');
            $carrier_method = $order->getData('carrier_method_lengow');
            $carrier_tracking = $order->getData('carrier_tracking_lengow');
            $carrier_id_relay = $order->getData('carrier_id_relay_lengow');
            $sent_marketplace = 0;
            $imported_at = $helper->getDateInCorrectFormat(strtotime($order->getData('carrier_id_relay_lengow')));
            $message = $order->getData('created_at');
            $extra = $order->getData('xml_node_lengow');
        }
        $sent_marketplace = $sent_marketplace == 1 ? $helper->__('global.just_yes') : $helper->__('global.just_no');
        // Construct fields list
        $fields[] = array('label' => $helper->__('order.table.marketplace_sku'), 'value' => $marketplace_sku);
        $fields[] = array('label' => $helper->__('order.table.marketplace_name'), 'value' => $marketplace_label);
        if ($feed_id != 0) {
            $fields[] = array('label' => $helper->__('order.screen.feed_id'), 'value' => $feed_id);
        } else {
            $fields[] = array(
                'label' => $helper->__('order.screen.delivery_address_id'),
                'value' => $delivery_address_id
            );
        }
        $fields[] = array('label' => $helper->__('order.table.total_paid'), 'value' => $total_paid);
        $fields[] = array('label' => $helper->__('order.screen.commission'), 'value' => $commission);
        $fields[] = array('label' => $helper->__('order.screen.currency'), 'value' => $currency);
        $fields[] = array('label' => $helper->__('order.table.customer_name'), 'value' => $customer_name);
        $fields[] = array('label' => $helper->__('order.screen.customer_email'), 'value' => $customer_email);
        $fields[] = array('label' => $helper->__('order.screen.carrier'), 'value' => $carrier);
        $fields[] = array('label' => $helper->__('order.screen.carrier_method'), 'value' => $carrier_method);
        $fields[] = array('label' => $helper->__('order.screen.carrier_tracking'), 'value' => $carrier_tracking);
        $fields[] = array('label' => $helper->__('order.screen.carrier_id_relay'), 'value' => $carrier_id_relay);
        $fields[] = array('label' => $helper->__('order.screen.sent_marketplace'), 'value' => $sent_marketplace);
        $fields[] = array('label' => $helper->__('order.screen.message'), 'value' => $message);
        $fields[] = array('label' => $helper->__('order.screen.imported_at'), 'value' => $imported_at);
        $fields[] = array(
            'label' => $helper->__('order.screen.extra'),
            'value' => '<textarea disabled="disabled">'.$extra.'</textarea>',
        );

        return $fields;
    }

    /**
     * Check if is a Lengow order
     *
     * @return boolean
     */
    public function isLengowOrder()
    {
        return $this->getOrder()->getData('from_lengow');
    }
}
