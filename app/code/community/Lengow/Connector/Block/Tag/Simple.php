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
 * Block tag simple
 */
class Lengow_Connector_Block_Tag_Simple extends Mage_Core_Block_Template
{
    /**
     * Prepare and return block's html output
     *
     * @return Lengow_Connector_Block_Tag_Simple
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (Mage::app()->getRequest()->getActionName() === 'success') {
            $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
            $order = Mage::getModel('sales/order')->load($orderId);
            $this->setData('account_id', Mage::helper('lengow_connector/config')->get('account_id'));
            $cart = Mage::getModel('lengow/tracker')->getIdsProducts($order);
            $this->setData('order_ref', $orderId);
            $this->setData('amount', $order->getGrandTotal());
            $this->setData('currency', $order->getOrderCurrencyCode());
            $this->setData('payment_method', $order->getPayment()->getMethodInstance()->getCode());
            $this->setData('cart', htmlspecialchars($cart));
            $this->setData('cart_number', $order->getQuoteId());
            $this->setData('newbiz', 1);
            $this->setData('valid', 1);
            $this->setTemplate('lengow/tracker/simpletag.phtml');
        }
        return $this;
    }
}
