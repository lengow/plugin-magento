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
 * Model export catalog product
 */
class Lengow_Connector_Model_Export_Catalog_Product extends Mage_Catalog_Model_Product
{
    /**
     * Config model export
     *
     * @var Lengow_Connector_Helper_Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * Initialize resources
     */
    protected function _construct()
    {
        $this->_init('catalog/product');
        $this->_configHelper = Mage::helper('lengow_connector/config');
    }

    /**
     * Get Shipping info
     *
     * @param Mage_Catalog_Model_Product $productInstance Magento product instance
     * @param integer $storeId Magento store id
     *
     * @throws Exception
     *
     * @return array
     */
    public function getShippingInfo($productInstance, $storeId)
    {
        $data = array(
            'shipping_method' => '',
            'shipping_cost' => '',
        );
        $carrier = $this->_configHelper->get(Lengow_Connector_Helper_Config::DEFAULT_EXPORT_CARRIER_ID, $storeId);
        if (empty($carrier)) {
            return $data;
        }
        $carrierTab = explode('_', $carrier);
        $data['shipping_method'] = ucfirst($carrierTab[1]);
        $countryCode = $this->_configHelper->get(
            Lengow_Connector_Helper_Config::DEFAULT_EXPORT_SHIPPING_COUNTRY,
            $storeId
        );
        $shippingPrice = $this->_getShippingPrice($productInstance, $carrier, $countryCode);
        if (!$shippingPrice) {
            $shippingPrice = $this->_configHelper->get(
                Lengow_Connector_Helper_Config::DEFAULT_EXPORT_SHIPPING_PRICE,
                $storeId
            );
        }
        $data['shipping_cost'] = $shippingPrice;
        return $data;
    }

    /**
     * Get shipping price
     *
     * @param Mage_Catalog_Model_Product $productInstance Magento product instance
     * @param string $carrierValue Magento carrier value
     * @param string $countryCode country iso code
     *
     * @throws Exception
     *
     * @return float|false
     */
    public function _getShippingPrice($productInstance, $carrierValue, $countryCode = 'FR')
    {
        $carrierTab = explode('_', $carrierValue);
        $shipping = Mage::getModel('shipping/shipping');
        $methodModel = $shipping->getCarrierByCode($carrierTab[0]);
        if ($methodModel) {
            $result = $methodModel->collectRates(
                $this->_getShippingRateRequest($productInstance, $countryCode)
            );
            if ($result !== null && $result !== false) {
                if ($result->getError()) {
                    Mage::logException(new Exception($result->getError()));
                } else {
                    foreach ($result->getAllRates() as $rate) {
                        return $rate->getPrice();
                    }
                }
            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * Get Shipping rate request
     *
     * @param Mage_Catalog_Model_Product $productInstance Magento product instance
     * @param string $countryCode country iso code
     *
     * @throws Exception
     *
     * @return Mage_Shipping_Model_Rate_Request
     */
    protected function _getShippingRateRequest($productInstance, $countryCode = 'FR')
    {
        /** @var $request Mage_Shipping_Model_Rate_Request */
        $request = Mage::getModel('shipping/rate_request');
        $storeId = $request->getStoreId();
        if (!$request->getOrig()) {
            $request->setCountryId($countryCode)
                ->setRegionId('')
                ->setCity('')
                ->setPostcode('');
        }
        $item = Mage::getModel('sales/quote_item');
        $item->setStoreId($storeId);
        $item->setOptions($this->getCustomOptions())
            ->setProduct($this);
        $request->setAllItems(array($item));
        $request->setDestCountryId($countryCode);
        $request->setDestRegionId('');
        $request->setDestRegionCode('');
        $request->setDestPostcode('');
        $request->setPackageValue($productInstance->getPrice());
        $request->setPackageValueWithDiscount($productInstance->getFinalPrice());
        $request->setPackageWeight($productInstance->getWeight());
        $request->setFreeMethodWeight(0);
        $request->setPackageQty(1);
        $request->setStoreId(Mage::app()->getStore()->getId());
        $request->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
        $request->setBaseCurrency(Mage::app()->getStore()->getBaseCurrency());
        $request->setPackageCurrency(Mage::app()->getStore()->getCurrentCurrency());
        return $request;
    }

    /**
     * Get price
     *
     * @param Mage_Catalog_Model_Product $productInstance Magento product instance
     * @param integer $storeId Magento store id
     *
     * @throws Exception
     *
     * @return array
     */
    public function getPrices($productInstance, $storeId)
    {
        $store = Mage::app()->getStore($storeId);
        $config = Mage::helper('tax')->priceIncludesTax($store);
        $calculator = Mage::getSingleton('tax/calculation');
        $taxClassId = $productInstance->getTaxClassId();
        $request = $calculator->getRateRequest(null, null, null, $store);
        $taxPercent = $calculator->getRate($request->setProductClassId($taxClassId));
        if ($productInstance->getTypeId() === 'grouped') {
            $price = 0;
            $finalPrice = 0;
            $children = Mage::getModel('catalog/product_type_grouped')->getChildrenIds($productInstance->getId());
            $children = $children[Mage_Catalog_Model_Product_Link::LINK_TYPE_GROUPED];
            foreach ($children as $value) {
                $product = Mage::getModel('lengow/export_catalog_product')->load($value);
                $price += $product->getPrice();
                $finalPrice += $product->getFinalPrice();
            }
        } else {
            $price = $productInstance->getPrice();
            $finalPrice = $productInstance->getFinalPrice();
        }
        if (!$config) {
            $priceExcludingTax = $price;
            $priceIncludingTax = $price + $calculator->calcTaxAmount($price, $taxPercent, false);
            $finalPriceExcludingTax = $finalPrice;
            $finalPriceIncludingTax = $finalPrice + $calculator->calcTaxAmount($finalPrice, $taxPercent, false);
        } else {
            $priceExcludingTax = Mage::helper('tax')->getPrice($productInstance, $price);
            $priceIncludingTax = $price;
            $finalPriceExcludingTax = Mage::helper('tax')->getPrice($productInstance, $finalPrice);
            $finalPriceIncludingTax = $finalPrice;
        }
        // get currency for convert
        if (!$this->getCurrentCurrencyCode()) {
            $toCurrency = $store->getCurrentCurrency();
        } else {
            $toCurrency = Mage::getModel('directory/currency')->load($this->getCurrentCurrencyCode());
        }
        $data = array();
        $data['currency'] = $toCurrency->getCode();
        // get prices with or without conversion
        if ($this->getOriginalCurrency() === $toCurrency->getCode()) {
            $discountAmount = $priceIncludingTax - $finalPriceIncludingTax;
            $data['price_excl_tax'] = round($finalPriceExcludingTax, 2);
            $data['price_incl_tax'] = round($finalPriceIncludingTax, 2);
            $data['price_before_discount_excl_tax'] = round($priceExcludingTax, 2);
            $data['price_before_discount_incl_tax'] = round($priceIncludingTax, 2);
        } else {
            $discountAmount = Mage::helper('directory')->currencyConvert(
                    $priceIncludingTax,
                    $this->getOriginalCurrency(),
                    $toCurrency
                ) - Mage::helper('directory')->currencyConvert(
                    $finalPriceIncludingTax,
                    $this->getOriginalCurrency(),
                    $this->getCurrentCurrencyCode()
                );
            $data['price_excl_tax'] = round(
                Mage::helper('directory')->currencyConvert(
                    $finalPriceExcludingTax,
                    $this->getOriginalCurrency(),
                    $this->getCurrentCurrencyCode()
                ),
                2
            );
            $data['price_incl_tax'] = round(
                Mage::helper('directory')->currencyConvert(
                    $finalPriceIncludingTax,
                    $this->getOriginalCurrency(),
                    $this->getCurrentCurrencyCode()
                ),
                2
            );
            $data['price_before_discount_excl_tax'] = round(
                Mage::helper('directory')->currencyConvert(
                    $priceExcludingTax,
                    $this->getOriginalCurrency(),
                    $this->getCurrentCurrencyCode()
                ),
                2
            );
            $data['price_before_discount_incl_tax'] = round(
                Mage::helper('directory')->currencyConvert(
                    $priceIncludingTax,
                    $this->getOriginalCurrency(),
                    $this->getCurrentCurrencyCode()
                ),
                2
            );
        }
        $data['discount_amount'] = $discountAmount > 0 ? round($discountAmount, 2) : '0';
        $data['discount_percent'] = $discountAmount > 0
            ? round(($discountAmount * 100) / $priceIncludingTax, 0)
            : '0';
        $data['discount_start_date'] = $productInstance->getSpecialFromDate();
        $data['discount_end_date'] = $productInstance->getSpecialToDate();
        // retrieving promotions
        $dateTs = Mage::app()->getLocale()->storeTimeStamp($productInstance->getStoreId());
        $promo = Mage::getResourceModel('catalogrule/rule')->getRulesFromProduct(
            (int) $dateTs,
            $store->getWebsiteId(),
            1,
            $productInstance->getId()
        );
        if (!empty($promo)) {
            $from = isset($promo[0]['from_time']) ? $promo[0]['from_time'] : $promo[0]['from_date'];
            $from = !is_numeric($from) ? strtotime($from) : $from;
            $to = isset($promo[0]['to_time']) ? $promo[0]['to_time'] : $promo[0]['to_date'];
            $to = !is_numeric($to) ? strtotime($to) : $to;
            $data['discount_start_date'] = $from ? date(Lengow_Connector_Helper_Data::DATE_FULL, $from) : '';
            $data['discount_end_date'] = $to ? date(Lengow_Connector_Helper_Data::DATE_FULL, $to) : '';
        }
        return $data;
    }

    /**
     * Get categories and breadcrumb
     *
     * @param Mage_Catalog_Model_Product $productInstance Magento product instance
     * @param Mage_Catalog_Model_Product $parentInstance Magento product instance for parent
     * @param integer $storeId Magento store id
     * @param array $categoryCache category cache
     *
     * @throws Exception
     *
     * @return array
     */
    public function getCategories($productInstance, $parentInstance, $storeId, &$categoryCache = array())
    {
        $idRootCategory = Mage::app()->getStore($storeId)->getRootCategoryId();
        if (isset($parentInstance)
            && (int) $productInstance->getVisibility() === Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE
        ) {
            $categories = $parentInstance->getCategoryCollection()
                ->addPathsFilter('1/' . $idRootCategory . '/')
                ->exportToArray();
        } else {
            $categories = $productInstance->getCategoryCollection()
                ->addPathsFilter('1/' . $idRootCategory . '/')
                ->exportToArray();
        }
        // old config value #levelcategory
        $maxLevel = 5;
        $currentLevel = 0;
        $categoryBuffer = false;
        foreach ($categories as $category) {
            if ($category['level'] > $currentLevel) {
                $currentLevel = $category['level'];
                $categoryBuffer = $category;
            }
            if ($currentLevel > $maxLevel) {
                break;
            }
        }
        // use category cache if category already exists
        if (isset($category) && $category['entity_id'] !== '') {
            if (isset($categoryCache[$category['entity_id']])) {
                return $categoryCache[$category['entity_id']];
            }
        }
        if (isset($category) && $category['path'] !== '') {
            $categories = explode('/', $categoryBuffer['path']);
        } else {
            $categories = array();
        }
        $data = array(
            'category' => '',
            'category_url' => '',
        );
        for ($i = 1; $i <= $maxLevel; $i++) {
            $data['category_sub_' . ($i)] = '';
            $data['category_url_sub_' . ($i)] = '';
        }
        $i = 0;
        $ariane = array();
        foreach ($categories as $cid) {
            $c = Mage::getModel('catalog/category')
                ->setStoreId($storeId)
                ->load($cid);
            if ((int) $c->getId() !== 1) {
                // no root category
                if ($i === 0) {
                    $data['category'] = $c->getName();
                    $data['category_url'] = $c->getUrl();
                    $ariane[] = $c->getName();
                } elseif ($i <= $maxLevel) {
                    $ariane[] = $c->getName();
                    $data['category_sub_' . $i] = $c->getName();
                    $data['category_url_sub_' . $i] = $c->getUrl();
                }
                $i++;
            }
            if (method_exists($c, 'clearInstance')) {
                $c->clearInstance();
            }
        }
        $data['category_breadcrumb'] = implode(' > ', $ariane);
        $maxDimension = count($categories) - 1;
        if ($maxDimension >= 0) {
            $categoryCache[$categories[count($categories) - 1]] = $data;
        }
        unset($categories, $category, $ariane);
        return $data;
    }

    /**
     * Merge images child with images' parents
     *
     * @param array $images images of child's product
     * @param array $parentImages images of parent's product
     *
     * @return array images merged
     */
    public function getImages($images, $parentImages = array())
    {
        $data = array();
        if (!empty($parentImages) && isset($parentImages['images'])) {
            $images = array_merge($parentImages['images'], $images);
            $tempImages = array();
            $files = array();
            foreach ($images as $image) {
                if (array_key_exists('value_id', $image) && !in_array($image['file'], $files, true)) {
                    $files[] = $image['file'];
                    $tempImages[]['file'] = $image['file'];
                }
            }
            $images = $tempImages;
            unset($tempImages, $files, $parentImages);
        }
        // old config value #maxImage
        for ($i = 1; $i < 11; $i++) {
            $data['image_url_' . $i] = '';
        }
        $counter = 1;
        foreach ($images as $image) {
            $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $image['file'];
            $data['image_url_' . $counter] = $url;
            if ($counter === 10) {
                break;
            }
            $counter++;
        }
        return $data;
    }
}
