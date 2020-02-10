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
     * @param mixed $products Lengow products list
     * @param Lengow_Connector_Model_Import_Marketplace $marketplace Lengow marketplace instance
     * @param string $marketplaceSku marketplace sku
     * @param boolean $logOutput see log or not
     * @param boolean $priceIncludeTax price include tax
     *
     * @throws Lengow_Connector_Model_Exception product not be found / product is a parent
     */
    public function addLengowProducts($products, $marketplace, $marketplaceSku, $logOutput, $priceIncludeTax = true)
    {
        $this->_lengowProducts = $this->_getProducts($products, $marketplace, $marketplaceSku, $logOutput);
        foreach ($this->_lengowProducts as $lengowProduct) {
            $magentoProduct = $lengowProduct['magento_product'];
            if ($magentoProduct->getId()) {
                $price = $lengowProduct['price_unit'];
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
                $magentoProduct->setName($lengowProduct['title']);
                // add item to quote
                $quoteItem = Mage::getModel('lengow/import_quote_item')
                    ->setProduct($magentoProduct)
                    ->setQty($lengowProduct['quantity'])
                    ->setConvertedPrice($price);
                $this->addItem($quoteItem);
            }
        }
    }

    /**
     * Find product in Magento based on API data
     *
     * @param mixed $products all product datas
     * @param Lengow_Connector_Model_Import_Marketplace $marketplace Lengow marketplace instance
     * @param string $marketplaceSku marketplace sku
     * @param boolean $logOutput see log or not
     *
     * @throws Lengow_Connector_Model_Exception product not be found / product is a parent
     *
     * @return array
     */
    protected function _getProducts($products, $marketplace, $marketplaceSku, $logOutput)
    {
        $lengowProducts = array();
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector/data');
        foreach ($products as $product) {
            $found = false;
            $magentoProduct = false;
            $productModel = Mage::getModel('catalog/product');
            $orderLineId = (string)$product->marketplace_order_line_id;
            // check whether the product is canceled
            if ($product->marketplace_status !== null) {
                $stateProduct = $marketplace->getStateLengow((string)$product->marketplace_status);
                if ($stateProduct === Lengow_Connector_Model_Import_Order::STATE_CANCELED
                    || $stateProduct === Lengow_Connector_Model_Import_Order::STATE_REFUSED
                ) {
                    $productId = !is_null($product->merchant_product_id->id)
                        ? (string)$product->merchant_product_id->id
                        : (string)$product->marketplace_product_id;
                    $helper->log(
                        Lengow_Connector_Helper_Data::CODE_IMPORT,
                        $helper->setLogMessage(
                            'log.import.product_state_canceled',
                            array(
                                'product_id' => $productId,
                                'state_product' => $stateProduct,
                            )
                        ),
                        $logOutput,
                        $marketplaceSku
                    );
                    continue;
                }
            }
            $productIds = array(
                'merchant_product_id' => $product->merchant_product_id->id,
                'marketplace_product_id' => $product->marketplace_product_id,
            );
            $productField = $product->merchant_product_id->field !== null
                ? strtolower((string)$product->merchant_product_id->field)
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
                            $magentoProduct = $productModel->load($collection[0]['entity_id']);
                        }
                    }
                }
                // search by id or sku
                if (!$magentoProduct || !$magentoProduct->getId()) {
                    if (preg_match('/^[0-9]*$/', $attributeValue)) {
                        $magentoProduct = $productModel->load((integer)$attributeValue);
                    }
                    if (!$magentoProduct || !$magentoProduct->getId()) {
                        $attributeValue = str_replace('\_', '_', $attributeValue);
                        $magentoProduct = $productModel->load($productModel->getIdBySku($attributeValue));
                    }
                }
                if ($magentoProduct && $magentoProduct->getId()) {
                    $magentoProductId = $magentoProduct->getId();
                    // save total row Lengow for each product
                    if (array_key_exists($magentoProductId, $lengowProducts)) {
                        $lengowProducts[$magentoProductId]['quantity'] += (int)$product->quantity;
                        $lengowProducts[$magentoProductId]['amount'] += (float)$product->amount;
                        $lengowProducts[$magentoProductId]['order_line_ids'][] = $orderLineId;
                    } else {
                        $lengowProducts[$magentoProductId] = array(
                            'magento_product' => $magentoProduct,
                            'sku' => (string)$magentoProduct->getSku(),
                            'title' => (string)$product->title,
                            'amount' => (float)$product->amount,
                            'price_unit' => (float)($product->amount / $product->quantity),
                            'quantity' => (int)$product->quantity,
                            'order_line_ids' => array($orderLineId),
                        );
                    }
                    $helper->log(
                        Lengow_Connector_Helper_Data::CODE_IMPORT,
                        $helper->setLogMessage(
                            'log.import.product_be_found',
                            array(
                                'product_id' => $magentoProduct->getId(),
                                'attribute_name' => $attributeName,
                                'attribute_value' => $attributeValue,
                            )
                        ),
                        $logOutput,
                        $marketplaceSku
                    );
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $productId = !is_null($product->merchant_product_id->id)
                    ? (string)$product->merchant_product_id->id
                    : (string)$product->marketplace_product_id;
                throw new Lengow_Connector_Model_Exception(
                    $helper->setLogMessage(
                        'lengow_log.exception.product_not_be_found',
                        array('product_id' => $productId)
                    )
                );
            } elseif ($magentoProduct->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                throw new Lengow_Connector_Model_Exception(
                    $helper->setLogMessage(
                        'lengow_log.exception.product_is_a_parent',
                        array('product_id' => $magentoProduct->getId())
                    )
                );
            }
        }
        return $lengowProducts;
    }

    /**
     * Get Lengow Products
     *
     * @param string|null $productId Magento product id
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
