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
 * Model import quote
 */
class Lengow_Connector_Model_Import_Quote extends Mage_Sales_Model_Quote
{
    /**
     * Add products from API to current quote
     *
     * @param array $products Lengow products list
     * @param boolean $priceIncludeTax price include tax
     */
    public function addLengowProducts($products, $priceIncludeTax = true)
    {
        foreach ($products as $product) {
            $magentoProduct = $product['magento_product'];
            if ($magentoProduct->getId()) {
                $price = $product['price_unit'];
                // if price not include tax -> get shipping cost without tax
                if (!$priceIncludeTax) {
                    $basedOn = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_BASED_ON, $this->getStore());
                    $countryId = $basedOn === 'shipping'
                        ? $this->getShippingAddress()->getCountryId()
                        : $this->getBillingAddress()->getCountryId();
                    $taxCalculator = Mage::getModel('tax/calculation');
                    $taxRequest = new Varien_Object();
                    $taxRequest->setCountryId($countryId)
                        ->setCustomerClassId($this->getCustomer()->getTaxClassId())
                        ->setProductClassId($magentoProduct->getTaxClassId());
                    $taxRate = (float)$taxCalculator->getRate($taxRequest);
                    $tax = (float)$taxCalculator->calcTaxAmount($price, $taxRate, true);
                    $price = $price - $tax;
                }
                $magentoProduct->setPrice($price);
                $magentoProduct->setSpecialPrice($price);
                $magentoProduct->setFinalPrice($price);
                // option "import with product's title from Lengow"
                $magentoProduct->setName($product['title']);
                // add item to quote
                $quoteItem = Mage::getModel('lengow/import_quote_item')
                    ->setProduct($magentoProduct)
                    ->setQty($product['quantity'])
                    ->setConvertedPrice($price);
                $this->addItem($quoteItem);
            }
        }
    }
}
