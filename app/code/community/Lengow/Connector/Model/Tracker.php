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
 * Model tracker
 */
class Lengow_Connector_Model_Tracker extends Varien_Object
{
    /**
     * Return list of order's items id
     *
     * @param Mage_Sales_Model_Order|Mage_Sales_Model_Quote $quote Magento order instance
     *
     * @return string|false
     */
    public function getIdsProducts($quote)
    {
        if ($quote instanceof Mage_Sales_Model_Order || $quote instanceof Mage_Sales_Model_Quote) {
            $quoteItems = $quote->getAllVisibleItems();
            $productsCart = array();
            foreach ($quoteItems as $item) {
                if ($item->hasProduct()) {
                    $product = $item->getProduct();
                } else {
                    $product = Mage::getModel('catalog/product')->load($item->getProductId());
                }
                $quantity = (int)$item->getQtyOrdered();
                $price = round((float)$item->getRowTotalInclTax() / $quantity, 2);
                $identifier = Mage::helper('lengow_connector/config')->get('tracking_id');
                $productDatas = array(
                    'product_id' => $product->getData($identifier),
                    'price' => $price,
                    'quantity' => $quantity
                );
                $productsCart[] = $productDatas;
            }
            return json_encode($productsCart);
        }
        return false;
    }
}
