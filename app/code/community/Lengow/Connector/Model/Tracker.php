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
            $products_cart = array();
            foreach ($quote_items as $item) {
                if ($item->hasProduct()) {
                    $product = $item->getProduct();
                } else {
                    $product = Mage::getModel('catalog/product')->load($item->getProductId());
                }
                $quantity = (int) $item->getQtyOrdered();
                $price = round((float)$item->getRowTotalInclTax() / $quantity, 2);
                $identifier =  Mage::helper('lengow_connector/config')->get('tracking_id');
                $product_datas = array(
                    'product_id' => $product->getData($identifier),
                    'price'      => $price,
                    'quantity'   => $quantity
                );
                $products_cart[] = $product_datas;
            }
            return json_encode($products_cart);
        }
        return false;
    }
}
