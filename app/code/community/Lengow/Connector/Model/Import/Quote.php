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
     * @var array row total Lengow
     */
    protected $_lengowProducts = array();

    /**
     * Add products from API to current quote
     *
     * @param mixed                                     $products        Lengow products list
     * @param Lengow_Connector_Model_Import_Marketplace $marketplace     Lengow marketplace instance
     * @param string                                    $marketplaceSku  marketplace sku
     * @param boolean                                   $logOutput       see log or not
     * @param boolean                                   $priceIncludeTax price include tax
     *
     * @return Lengow_Connector_Model_Import_Quote
     */
    public function addLengowProducts($products, $marketplace, $marketplaceSku, $logOutput, $priceIncludeTax = true)
    {
        $orderLineId = '';
        $first = true;
        foreach ($products as $productLine) {
            if ($first || empty($orderLineId) || $orderLineId != (string)$productLine->marketplace_order_line_id) {
                $first = false;
                $orderLineId = (string)$productLine->marketplace_order_line_id;
                // check whether the product is canceled
                if ($productLine->marketplace_status != null) {
                    $stateProduct = $marketplace->getStateLengow((string)$productLine->marketplace_status);
                    if ($stateProduct == 'canceled' || $stateProduct == 'refused') {
                        $productId = (!is_null($productLine->merchant_product_id->id)
                            ? (string)$productLine->merchant_product_id->id
                            : (string)$productLine->marketplace_product_id
                        );
                        Mage::helper('lengow_connector/data')->log(
                            'Import',
                            Mage::helper('lengow_connector/data')->setLogMessage(
                                'log.import.product_state_canceled',
                                array(
                                    'product_id'    => $productId,
                                    'state_product' => $stateProduct
                                )
                            ),
                            $logOutput,
                            $marketplaceSku
                        );
                        continue;
                    }
                }
                $product = $this->_findProduct($productLine, $marketplaceSku, $logOutput);
                if ($product) {
                    // get unit price with tax
                    $price = (float)($productLine->amount / $productLine->quantity);
                    // save total row Lengow for each product
                    $this->_lengowProducts[(string)$product->getId()] = array(
                        'sku'           => (string)$product->getSku(),
                        'title'         => (string)$productLine->title,
                        'amount'        => (float)$productLine->amount,
                        'price_unit'    => $price,
                        'quantity'      => (int)$productLine->quantity,
                        'order_line_id' => $orderLineId,
                    );
                    // if price not include tax -> get shipping cost without tax
                    if (!$priceIncludeTax) {
                        $basedOn = Mage::getStoreConfig(
                            Mage_Tax_Model_Config::CONFIG_XML_PATH_BASED_ON,
                            $this->getStore()
                        );
                        $countryId = ($basedOn == 'shipping')
                            ? $this->getShippingAddress()->getCountryId()
                            : $this->getBillingAddress()->getCountryId();
                        $taxCalculator = Mage::getModel('tax/calculation');
                        $taxRequest = new Varien_Object();
                        $taxRequest->setCountryId($countryId)
                            ->setCustomerClassId($this->getCustomer()->getTaxClassId())
                            ->setProductClassId($product->getTaxClassId());
                        $taxRate = (float)$taxCalculator->getRate($taxRequest);
                        $tax = (float)$taxCalculator->calcTaxAmount($price, $taxRate, true);
                        $price = $price - $tax;
                    }
                    $product->setPrice($price);
                    $product->setSpecialPrice($price);
                    $product->setFinalPrice($price);
                    // option "import with product's title from Lengow"
                    $product->setName((string)$productLine->title);
                    // add item to quote
                    $quoteItem = Mage::getModel('lengow/import_quote_item')
                        ->setProduct($product)
                        ->setQty((int)$productLine->quantity)
                        ->setConvertedPrice($price);
                    $this->addItem($quoteItem);
                }
            }
        }
    }

    /**
     * Find product in Magento based on API data
     *
     * @param mixed   $productLine    product datas
     * @param string  $marketplaceSku marketplace sku
     * @param boolean $logOutput      see log or not
     *
     * @throws Lengow_Connector_Model_Exception product not be found / product is a parent
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function _findProduct($productLine, $marketplaceSku, $logOutput)
    {
        $found = false;
        $product = false;
        $productModel = Mage::getModel('catalog/product');
        $productIds = array(
            'merchant_product_id'    => $productLine->merchant_product_id->id,
            'marketplace_product_id' => $productLine->marketplace_product_id
        );
        $productField = $productLine->merchant_product_id->field != null
            ? strtolower((string)$productLine->merchant_product_id->field)
            : false;
        // search product foreach value
        foreach ($productIds as $attributeName => $attributeValue) {
            // remove _FBA from product id
            $attributeValue = preg_replace('/_FBA$/', '', $attributeValue);
            if (empty($attributeValue)) {
                continue;
            }
            // search by field if exists
            if ($productField) {
                $attributeModel = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $productField);
                if ($attributeModel->getAttributeId()) {
                    $collection = Mage::getResourceModel('catalog/product_collection')
                        ->setStoreId($this->getStore()->getStoreId())
                        ->addAttributeToSelect($productField)
                        ->addAttributeToFilter($productField, $attributeValue)
                        ->setPage(1, 1)
                        ->getData();
                    if (is_array($collection) && count($collection) > 0) {
                        $product = $productModel->load($collection[0]['entity_id']);
                    }
                }
            }
            // search by id or sku
            if (!$product || !$product->getId()) {
                if (preg_match('/^[0-9]*$/', $attributeValue)) {
                    $product = $productModel->load((integer)$attributeValue);
                }
                if (!$product || !$product->getId()) {
                    $attributeValue = str_replace('\_', '_', $attributeValue);
                    $product = $productModel->load($productModel->getIdBySku($attributeValue));
                }
            }
            if ($product && $product->getId()) {
                $found = true;
                Mage::helper('lengow_connector/data')->log(
                    'Import',
                    Mage::helper('lengow_connector/data')->setLogMessage(
                        'log.import.product_be_found',
                        array(
                            'product_id'      => $product->getId(),
                            'attribute_name'  => $attributeName,
                            'attribute_value' => $attributeValue
                        )
                    ),
                    $logOutput,
                    $marketplaceSku
                );
                break;
            }
        }
        if (!$found) {
            $productId = (!is_null($productLine->merchant_product_id->id)
                ? (string)$productLine->merchant_product_id->id
                : (string)$productLine->marketplace_product_id
            );
            throw new Lengow_Connector_Model_Exception(
                Mage::helper('lengow_connector/data')->setLogMessage(
                    'lengow_log.exception.product_not_be_found',
                    array('product_id' => $productId)
                )
            );
        } elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            throw new Lengow_Connector_Model_Exception(
                Mage::helper('lengow_connector/data')->setLogMessage(
                    'lengow_log.exception.product_is_a_parent',
                    array('product_id' => $product->getId())
                )
            );
        }
        return $product;
    }

    /**
     * Get Lengow Products
     *
     * @param string $productId Magento product id
     *
     * @return array
     */
    public function getLengowProducts($productId = null)
    {
        if (is_null($productId)) {
            return $this->_lengowProducts;
        } else {
            return $this->_lengowProducts[$productId];
        }
    }
}
