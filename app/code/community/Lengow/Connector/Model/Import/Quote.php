<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Model_Import_Quote extends Mage_Sales_Model_Quote
{
    /**
     * @var array row total Lengow
     */
    protected $_lengow_products = array();

    /**
     * Add products from API to current quote
     *
     * @param mixed                                     $products
     * @param Lengow_Connector_Model_Import_Marketplace $marketplace
     * @param string                                    $marketplace_sku
     * @param boolean                                   $log_output
     * @param boolean                                   $price_include_tax
     *
     * @return Lengow_Connector_Model_Import_Quote
     */
    public function addLengowProducts($products, $marketplace, $marketplace_sku, $log_output, $price_include_tax = true)
    {
        $order_lineid = '';
        $first = true;
        foreach ($products as $product_line) {
            if ($first || empty($order_lineid) || $order_lineid != (string)$product_line->marketplace_order_line_id) {
                $first = false;
                $order_lineid = (string)$product_line->marketplace_order_line_id;
                // check whether the product is canceled
                if ($product_line->marketplace_status != null) {
                    $state_product = $marketplace->getStateLengow((string)$product_line->marketplace_status);
                    if ($state_product == 'canceled' || $state_product == 'refused') {
                        $product_id = (!is_null($product_line->merchant_product_id->id)
                            ? (string)$product_line->merchant_product_id->id
                            : (string)$product_line->marketplace_product_id
                        );
                        Mage::helper('lengow_connector/data')->log(
                            'Import',
                            Mage::helper('lengow_connector/data')->setLogMessage(
                                'log.import.product_state_canceled',
                                array(
                                    'product_id'    => $product_id,
                                    'state_product' => $state_product
                                )
                            ),
                            $log_output,
                            $marketplace_sku
                        );
                        continue;
                    }
                }
                $product = $this->_findProduct($product_line, $marketplace_sku, $log_output);
                if ($product) {
                    // get unit price with tax
                    $price = (float)($product_line->amount / $product_line->quantity);
                    // save total row Lengow for each product
                    $this->_lengow_products[(string)$product->getId()] = array(
                        'sku'           => (string)$product->getSku(),
                        'title'         => (string)$product_line->title,
                        'amount'        => (float)$product_line->amount,
                        'price_unit'    => $price,
                        'quantity'      => (int)$product_line->quantity,
                        'order_line_id' => $order_lineid,
                    );
                    // if price not include tax -> get shipping cost without tax
                    if (!$price_include_tax) {
                        $basedOn = Mage::getStoreConfig(
                            Mage_Tax_Model_Config::CONFIG_XML_PATH_BASED_ON,
                            $this->getStore()
                        );
                        $country_id = ($basedOn == 'shipping')
                            ? $this->getShippingAddress()->getCountryId()
                            : $this->getBillingAddress()->getCountryId();
                        $taxCalculator = Mage::getModel('tax/calculation');
                        $taxRequest = new Varien_Object();
                        $taxRequest->setCountryId($country_id)
                            ->setCustomerClassId($this->getCustomer()->getTaxClassId())
                            ->setProductClassId($product->getTaxClassId());
                        $tax_rate = (float)$taxCalculator->getRate($taxRequest);
                        $tax = (float)$taxCalculator->calcTaxAmount($price, $tax_rate, true);
                        $price = $price - $tax;
                    }
                    $product->setPrice($price);
                    $product->setSpecialPrice($price);
                    $product->setFinalPrice($price);
                    // option "import with product's title from Lengow"
                    $product->setName((string)$product_line->title);
                    // add item to quote
                    $quote_item = Mage::getModel('lengow/import_quote_item')
                        ->setProduct($product)
                        ->setQty((int)$product_line->quantity)
                        ->setConvertedPrice($price);
                    $this->addItem($quote_item);
                }
            }
        }
    }

    /**
     * Find product in Magento based on API data
     *
     * @param mixed   $product_line product data
     * @param string  $marketplace_sku
     * @param boolean $log_output
     *
     * @return Mage_Catalog_Model_Product product found to be added
     */
    protected function _findProduct($product_line, $marketplace_sku, $log_output)
    {
        $found = false;
        $product = false;
        $product_model = Mage::getModel('catalog/product');
        $product_ids = array(
            'merchant_product_id'    => $product_line->merchant_product_id->id,
            'marketplace_product_id' => $product_line->marketplace_product_id
        );
        $product_field = $product_line->merchant_product_id->field != null
            ? strtolower((string)$product_line->merchant_product_id->field)
            : false;
        // search product foreach value
        foreach ($product_ids as $attribute_name => $attribute_value) {
            // remove _FBA from product id
            $attribute_value = preg_replace('/_FBA$/', '', $attribute_value);
            if (empty($attribute_value)) {
                continue;
            }
            // search by field if exists
            if ($product_field) {
                $attributeModel = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $product_field);
                if ($attributeModel->getAttributeId()) {
                    $collection = Mage::getResourceModel('catalog/product_collection')
                        ->setStoreId($this->getStore()->getStoreId())
                        ->addAttributeToSelect($product_field)
                        ->addAttributeToFilter($product_field, $attribute_value)
                        ->setPage(1, 1)
                        ->getData();
                    if (is_array($collection) && count($collection) > 0) {
                        $product = $product_model->load($collection[0]['entity_id']);
                    }
                }
            }
            // search by id or sku
            if (!$product || !$product->getId()) {
                if (preg_match('/^[0-9]*$/', $attribute_value)) {
                    $product = $product_model->load((integer)$attribute_value);
                }
                if (!$product || !$product->getId()) {
                    $attribute_value = str_replace('\_', '_', $attribute_value);
                    $product = $product_model->load($product_model->getIdBySku($attribute_value));
                }
            }
            if ($product && $product->getId()) {
                $found = true;
                Mage::helper('lengow_connector/data')->log(
                    'Import',
                    Mage::helper('lengow_connector/data')->setLogMessage('log.import.product_be_found', array(
                        'product_id'      => $product->getId(),
                        'attribute_name'  => $attribute_name,
                        'attribute_value' => $attribute_value
                    )),
                    $log_output,
                    $marketplace_sku
                );
                break;
            }
        }
        if (!$found) {
            $product_id = (!is_null($product_line->merchant_product_id->id)
                ? (string)$product_line->merchant_product_id->id
                : (string)$product_line->marketplace_product_id
            );
            throw new Lengow_Connector_Model_Exception(
                Mage::helper('lengow_connector/data')->setLogMessage('lengow_log.exception.product_not_be_found', array(
                    'product_id' => $product_id
                ))
            );
        } elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            throw new Lengow_Connector_Model_Exception(
                Mage::helper('lengow_connector/data')->setLogMessage('lengow_log.exception.product_is_a_parent', array(
                    'product_id' => $product->getId()
                ))
            );
        }
        return $product;
    }

    /**
     * Get Lengow Products
     *
     * @param string $product_id product id
     *
     * @return string
     */
    public function getLengowProducts($product_id = null)
    {
        if (is_null($product_id)) {
            return $this->_lengow_products;
        } else {
            return $this->_lengow_products[$product_id];
        }
    }
}
