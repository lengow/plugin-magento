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
 * @subpackage  Block
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Block adminhtml order tab
 */
class Lengow_Connector_Block_Adminhtml_Order_Tab
    extends Mage_Adminhtml_Block_Sales_Order_Abstract
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     * Construct
     */
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

    /**
     * Get tab label
     *
     * @return string
     */
    public function getTabLabel()
    {
        return Mage::helper('sales')->__('Lengow');
    }

    /**
     * Get tab title
     *
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('sales')->__('Lengow');
    }

    /**
     * Can show tab
     *
     * @return boolean
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Tab is hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Get fields array
     *
     * @return array
     */
    public function getFields()
    {
        $fields = array();
        $order = $this->getOrder();
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector');
        /** @var Lengow_Connector_Model_Import_Order $orderLengow */
        $orderLengow = Mage::getModel('lengow/import_order');
        $orderLengowId = $orderLengow->getLengowOrderIdWithOrderId($order->getData('entity_id'));
        // get all Lengow data
        if ($orderLengowId) {
            $orderLengow = $orderLengow->load($orderLengowId);
            $marketplaceSku = $orderLengow->getData('marketplace_sku');
            $marketplaceLabel = $orderLengow->getData('marketplace_label');
            $feedId = $orderLengow->getData('feed_id');
            $deliveryAddressId = $orderLengow->getData('delivery_address_id');
            $currency = $orderLengow->getData('currency');
            $totalPaid = $orderLengow->getData('total_paid');
            $commission = $orderLengow->getData('commission');
            $customerName = $orderLengow->getData('customer_name');
            $customerEmail = $orderLengow->getData('customer_email');
            $carrier = $orderLengow->getData('carrier');
            $carrierMethod = $orderLengow->getData('carrier_method');
            $carrierTracking = $orderLengow->getData('carrier_tracking');
            $carrierIdRelay = $orderLengow->getData('carrier_id_relay');
            $sentMarketplace = (bool)$orderLengow->getData('sent_marketplace');
            $importedAt = $helper->getDateInCorrectFormat(strtotime($orderLengow->getData('created_at')));
            $message = $orderLengow->getData('message');
            $extra = $orderLengow->getData('extra');
        } else {
            $marketplaceSku = $order->getData('order_id_lengow');
            $marketplaceLabel = $order->getData('marketplace_lengow');
            $feedId = $order->getData('feed_id_lengow');
            $deliveryAddressId = $order->getData('delivery_address_id_lengow');
            $currency = $order->getData('base_currency_code');
            $totalPaid = $order->getData('total_paid_lengow');
            $commission = $order->getData('fees_lengow');
            $customerName = $order->getData('customer_firstname') . ' ' . $order->getData('customer_lastname');
            $customerEmail = $order->getData('customer_email');
            $carrier = $order->getData('carrier_lengow');
            $carrierMethod = $order->getData('carrier_method_lengow');
            $carrierTracking = $order->getData('carrier_tracking_lengow');
            $carrierIdRelay = $order->getData('carrier_id_relay_lengow');
            $sentMarketplace = false;
            $importedAt = $helper->getDateInCorrectFormat(strtotime($order->getData('carrier_id_relay_lengow')));
            $message = $order->getData('created_at');
            $extra = $order->getData('xml_node_lengow');
        }
        $sentMarketplace = $sentMarketplace ? $helper->__('global.just_yes') : $helper->__('global.just_no');
        // construct fields list
        $fields[] = array('label' => $helper->__('order.table.marketplace_sku'), 'value' => $marketplaceSku);
        $fields[] = array('label' => $helper->__('order.table.marketplace_name'), 'value' => $marketplaceLabel);
        if ($feedId !== 0) {
            $fields[] = array('label' => $helper->__('order.screen.feed_id'), 'value' => $feedId);
        } else {
            $fields[] = array(
                'label' => $helper->__('order.screen.delivery_address_id'),
                'value' => $deliveryAddressId,
            );
        }
        $fields[] = array('label' => $helper->__('order.table.total_paid'), 'value' => $totalPaid);
        $fields[] = array('label' => $helper->__('order.screen.commission'), 'value' => $commission);
        $fields[] = array('label' => $helper->__('order.screen.currency'), 'value' => $currency);
        $fields[] = array('label' => $helper->__('order.table.customer_name'), 'value' => $customerName);
        $fields[] = array('label' => $helper->__('order.screen.customer_email'), 'value' => $customerEmail);
        $fields[] = array('label' => $helper->__('order.screen.carrier'), 'value' => $carrier);
        $fields[] = array('label' => $helper->__('order.screen.carrier_method'), 'value' => $carrierMethod);
        $fields[] = array('label' => $helper->__('order.screen.carrier_tracking'), 'value' => $carrierTracking);
        $fields[] = array('label' => $helper->__('order.screen.carrier_id_relay'), 'value' => $carrierIdRelay);
        $fields[] = array('label' => $helper->__('order.screen.sent_marketplace'), 'value' => $sentMarketplace);
        $fields[] = array('label' => $helper->__('order.screen.message'), 'value' => $message);
        $fields[] = array('label' => $helper->__('order.screen.imported_at'), 'value' => $importedAt);
        $fields[] = array(
            'label' => $helper->__('order.screen.extra'),
            'value' => '<textarea disabled="disabled">' . $extra . '</textarea>',
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
        return (bool)$this->getOrder()->getData('from_lengow');
    }

    /**
     * Check if Magento order is follow by Lengow
     *
     * @return boolean
     */
    public function isFollowByLengow()
    {
        return (bool)$this->getOrder()->getData('follow_by_lengow');
    }

    /**
     * Check if can resend action order
     *
     * @return boolean
     */
    public function canReSendAction()
    {
        $order = $this->getOrder();
        if (Mage::getModel('lengow/import_action')->getActiveActionByOrderId($order->getData('entity_id'))) {
            return false;
        }
        $magentoStatus = $order->getData('status');
        if ($magentoStatus === 'complete' || $magentoStatus === 'cancel') {
            /** @var Lengow_Connector_Model_Import_Order $orderLengow */
            $orderLengow = Mage::getModel('lengow/import_order');
            $orderLengowId = $orderLengow->getLengowOrderIdWithOrderId($order->getData('entity_id'));
            if ($orderLengowId) {
                $orderLengow = $orderLengow->load($orderLengowId);
                $orderProcessStateClosed = $orderLengow->getOrderProcessState(
                    Lengow_Connector_Model_Import_Order::STATE_CLOSED
                );
                if ((int)$orderLengow->getData('order_process_state') !== $orderProcessStateClosed) {
                    return true;
                }
            } else {
                return true;
            }
        }
        return false;
    }
}
