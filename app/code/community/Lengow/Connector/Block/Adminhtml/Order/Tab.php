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
            $marketplaceSku = $orderLengow->getData(Lengow_Connector_Model_Import_Order::FIELD_MARKETPLACE_SKU);
            $marketplaceLabel = $orderLengow->getData(Lengow_Connector_Model_Import_Order::FIELD_MARKETPLACE_LABEL);
            $feedId = (int) $orderLengow->getData(Lengow_Connector_Model_Import_Order::FIELD_FEED_ID);
            $deliveryAddressId = $orderLengow->getData(Lengow_Connector_Model_Import_Order::FIELD_DELIVERY_ADDRESS_ID);
            $currency = $orderLengow->getData(Lengow_Connector_Model_Import_Order::FIELD_CURRENCY);
            $totalPaid = $orderLengow->getData(Lengow_Connector_Model_Import_Order::FIELD_TOTAL_PAID);
            $commission = $orderLengow->getData(Lengow_Connector_Model_Import_Order::FIELD_COMMISSION);
            $customerName = $orderLengow->getData(Lengow_Connector_Model_Import_Order::FIELD_CUSTOMER_NAME);
            $customerEmail = $orderLengow->getData(Lengow_Connector_Model_Import_Order::FIELD_CUSTOMER_EMAIL);
            $carrier = $orderLengow->getData(Lengow_Connector_Model_Import_Order::FIELD_CARRIER);
            $carrierMethod = $orderLengow->getData(Lengow_Connector_Model_Import_Order::FIELD_CARRIER_METHOD);
            $carrierTracking = $orderLengow->getData(Lengow_Connector_Model_Import_Order::FIELD_CARRIER_TRACKING);
            $carrierIdRelay = $orderLengow->getData(Lengow_Connector_Model_Import_Order::FIELD_CARRIER_RELAY_ID);
            $isExpress = $orderLengow->isExpress();
            $isDeliveredByMarketplace = $orderLengow->isDeliveredByMarketplace();
            $isBusiness = $orderLengow->isBusiness();
            $importedAt = $helper->getDateInCorrectFormat(
                strtotime($orderLengow->getData(Lengow_Connector_Model_Import_Order::FIELD_CREATED_AT))
            );
            $message = $orderLengow->getData(Lengow_Connector_Model_Import_Order::FIELD_MESSAGE);
            $extra = $orderLengow->getData(Lengow_Connector_Model_Import_Order::FIELD_EXTRA);
            $vatNumber = $orderLengow->getData(Lengow_Connector_Model_Import_Order::FIELD_CUSTOMER_VAT_NUMBER);
        } else {
            $marketplaceSku = $order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_MARKETPLACE_SKU);
            $marketplaceLabel = $order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_MARKETPLACE_NAME);
            $feedId = (int) $order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_FEED_ID);
            $deliveryAddressId = $order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_DELIVERY_ADDRESS_ID);
            $currency = $order->getData('base_currency_code');
            $totalPaid = $order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_TOTAL_PAID);
            $commission = $order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_COMMISSION);
            $customerName = $order->getData('customer_firstname') . ' ' . $order->getData('customer_lastname');
            $customerEmail = $order->getData('customer_email');
            $carrier = $order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_CARRIER);
            $carrierMethod = $order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_CARRIER_METHOD);
            $carrierTracking = $order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_CARRIER_TRACKING);
            $carrierIdRelay = $order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_CARRIER_RELAY_ID);
            $isExpress = false;
            $isDeliveredByMarketplace = false;
            $isBusiness = false;
            $importedAt = $helper->getDateInCorrectFormat(
                strtotime($order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_CARRIER_RELAY_ID))
            );
            $message = $order->getData('created_at');
            $extra = $order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_EXTRA);
            $vatNumber = null;
        }
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
        $fields[] = array(
            'label' => $helper->__('order.screen.is_express'),
            'value' => $isExpress ? $helper->__('global.just_yes') : $helper->__('global.just_no'),
        );
        $fields[] = array(
            'label' => $helper->__('order.screen.is_delivered_by_marketplace'),
            'value' => $isDeliveredByMarketplace ? $helper->__('global.just_yes') : $helper->__('global.just_no'),
        );
        $fields[] = array(
            'label' => $helper->__('order.screen.is_business'),
            'value' => $isBusiness ? $helper->__('global.just_yes') : $helper->__('global.just_no'),
        );
        $fields[] = array('label' => $helper->__('order.screen.vat_number'), 'value' => $vatNumber);
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
        return (bool) $this->getOrder()->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_FROM_LENGOW);
    }

    /**
     * Check if Magento order is follow by Lengow
     *
     * @return boolean
     */
    public function isFollowByLengow()
    {
        return (bool) $this->getOrder()->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_FOLLOW_BY_LENGOW);
    }

    /**
     * Check if can resend action order
     *
     * @return boolean
     */
    public function canReSendAction()
    {
        $order = $this->getOrder();
        if (Mage::getModel('lengow/import_action')->getActionsByOrderId($order->getData('entity_id'), true)) {
            return false;
        }
        $magentoStatus = $order->getData('status');
        if ($magentoStatus === Mage_Sales_Model_Order::STATE_COMPLETE
            || $magentoStatus === Mage_Sales_Model_Order::STATE_CANCELED
        ) {
            /** @var Lengow_Connector_Model_Import_Order $orderLengow */
            $orderLengow = Mage::getModel('lengow/import_order');
            $orderLengowId = $orderLengow->getLengowOrderIdWithOrderId($order->getData('entity_id'));
            if ($orderLengowId) {
                $orderLengow = $orderLengow->load($orderLengowId);
                $orderProcessStateClosed = $orderLengow->getOrderProcessState(
                    Lengow_Connector_Model_Import_Order::STATE_CLOSED
                );
                $orderProcessState = (int) $orderLengow->getData(
                    Lengow_Connector_Model_Import_Order::FIELD_ORDER_PROCESS_STATE
                );
                if ($orderProcessState !== $orderProcessStateClosed) {
                    return true;
                }
            } else {
                return true;
            }
        }
        return false;
    }
}
