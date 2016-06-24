<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Block_Tag_Simple extends Mage_Core_Block_Template
{
    /**
     * Prepare and return block's html output
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (Mage::app()->getRequest()->getActionName() == 'success') {
            $order_id = Mage::getSingleton('checkout/session')->getLastOrderId();
            $order = Mage::getModel('sales/order')->load($order_id);
            $this->setData(
                'account_id',
                Mage::helper('lengow_connector/config')->get('account_id', Mage::app()->getStore())
            );
            $this->setData('order_ref', $order_id);
            $this->setData('amount', $order->getGrandTotal());
            $this->setData('currency', $order->getOrderCurrencyCode());
            $this->setData('payment_method', $order->getPayment()->getMethodInstance()->getCode());
            $this->setData('cart', Mage::getModel('lengow/tracker')->getIdsProducts($order));
            $this->setData('newbiz', 1);
            $this->setData('secure', 0);
            $this->setData('valid', 1);
            $this->setTemplate('lengow/tracker/simpletag.phtml');
        }
        return $this;
    }
}
