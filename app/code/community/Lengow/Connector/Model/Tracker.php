<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Model_Tracker extends Varien_Object
{
    /**
     * Return list of order's items id
     *
     * @param Mage_Sales_Model_Order $order Magento order
     *
     * @return string
     */
    public function getIdsProducts($quote)
    {
        if ($quote instanceof Mage_Sales_Model_Order || $quote instanceof Mage_Sales_Model_Quote) {
            $quote_items = $quote->getAllVisibleItems();
            $ids = array();
            foreach ($quote_items as $item) {
                if ($item->hasProduct()) {
                    $product = $item->getProduct();
                } else {
                    $product = Mage::getModel('catalog/product')->load($item->getProductId());
                }
                $identifier =  Mage::helper('lengow_connector/config')->get('tracking_id');
                $ids[] = $product->getData($identifier);
            }
            return implode('|', $ids);
        }
        return false;
    }
}
