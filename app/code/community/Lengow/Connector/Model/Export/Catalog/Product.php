<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Model_Export_Catalog_Product extends Mage_Catalog_Model_Product
{
    /**
     * Config model export
     *
     * @var object
     */
    protected $_config_helper = true;

    /**
     * Initialize resources
     */
    protected function _construct()
    {
        $this->_init('catalog/product');
        $this->_config_helper = Mage::helper('lengow_connector/config');
    }

    /**
     * Get Shipping info
     *
     * @param Mage_Catalog_Model_Product $product_instance
     * @param integer                    $store_id
     *
     * @return array
     */
    public function getShippingInfo($product_instance, $store_id)
    {
        $datas = array();
        $datas['shipping_method'] = '';
        $datas['shipping_cost'] = '';
        $carrier = $this->_config_helper->get('shipping_method', $store_id);
        if (empty($carrier)) {
            return $datas;
        }
        $carrierTab = explode('_', $carrier);
        list($carrierCode, $methodCode) = $carrierTab;
        $datas['shipping_method'] = ucfirst($methodCode);
        $countryCode = $this->_config_helper->get('shipping_country', $store_id);
        $shippingPrice = $this->_getShippingPrice($product_instance, $carrier, $countryCode);
        if (!$shippingPrice) {
            $shippingPrice = $this->_config_helper->get('shipping_price', $store_id);
        }
        $datas['shipping_cost'] = $shippingPrice;
        return $datas;
    }

    /**
     * Get shipping price
     *
     * @param Mage_Catalog_Model_Product $product_instance
     * @param string $carrierValue
     * @param string $countryCode
     *
     * @return mixed
     */
    public function _getShippingPrice($product_instance, $carrierValue, $countryCode = 'FR')
    {
        $carrierTab = explode('_', $carrierValue);
        list($carrierCode, $methodCode) = $carrierTab;
        $shipping = Mage::getModel('shipping/shipping');
        $methodModel = $shipping->getCarrierByCode($carrierCode);
        if ($methodModel) {
            $result = $methodModel->collectRates($this->_getShippingRateRequest(
                $product_instance,
                $countryCode = 'FR'
            ));
            if ($result != null) {
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
     * @param Mage_Catalog_Model_Product $product_instance
     * @param string                     $countryCode
     *
     * @return Mage_Shipping_Model_Rate_Request
     */
    protected function _getShippingRateRequest($product_instance, $countryCode = 'FR')
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
        $request->setPackageValue($product_instance->getPrice());
        $request->setPackageValueWithDiscount($product_instance->getFinalPrice());
        $request->setPackageWeight($product_instance->getWeight());
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
     * @param Mage_Catalog_Model_Product $product_instance
     * @param integer                    $store_id
     * @param Mage_Catalog_Model_Product $configurable_instance
     *
     * @return array
     */
    public function getPrices($product_instance, $store_id, $configurable_instance = null)
    {
        $store = Mage::app()->getStore($store_id);
        $config = Mage::helper('tax')->priceIncludesTax($store);
        $calculator = Mage::getSingleton('tax/calculation');
        $taxClassId = $product_instance->getTaxClassId();
        $request = $calculator->getRateRequest(null, null, null, $store);
        $taxPercent = $calculator->getRate($request->setProductClassId($taxClassId));
        /* @var $configurable_instance Mage_Catalog_Model_Product */
        if ($configurable_instance) {
            $price = $configurable_instance->getPrice();
            $finalPrice = $configurable_instance->getFinalPrice();
            $configurablePrice = 0;
            $configurableOldPrice = 0;
            $attributes = $configurable_instance->getTypeInstance(true)
                                                ->getConfigurableAttributes($configurable_instance);
            $attributes = Mage::helper('core')->decorateArray($attributes);
            if ($attributes) {
                foreach ($attributes as $attribute) {
                    $productAttribute = $attribute->getProductAttribute();
                    $productAttributeId = $productAttribute->getId();
                    $attributeValue = $product_instance->getData($productAttribute->getAttributeCode());
                    if (count($attribute->getPrices()) > 0) {
                        foreach ($attribute->getPrices() as $priceChange) {
                            if (is_array($price)
                                && array_key_exists('value_index', $price)
                                && $price['value_index'] == $attributeValue
                            ) {
                                $configurableOldPrice += (float)($priceChange['is_percent']
                                    ? (((float)$priceChange['pricing_value']) * $price / 100)
                                    : $priceChange['pricing_value']);
                                $configurablePrice += (float)($priceChange['is_percent']
                                    ? (((float)$priceChange['pricing_value']) * $finalPrice / 100)
                                    : $priceChange['pricing_value']);
                            }
                        }
                    }
                }
            }
            $configurable_instance->setConfigurablePrice($configurablePrice);
            $configurable_instance->setParentId(true);
            Mage::dispatchEvent(
                'catalog_product_type_configurable_price',
                array('product' => $configurable_instance)
            );
            $configurablePrice = $configurable_instance->getConfigurablePrice();
            $price = $product_instance->getPrice() + $configurableOldPrice;
            $final_price = $product_instance->getFinalPrice() + $configurablePrice;
        } else {
            if ($product_instance->getTypeId() == 'grouped') {
                $price = 0;
                $final_price = 0;
                $childs = Mage::getModel('catalog/product_type_grouped')->getChildrenIds($product_instance->getId());
                $childs = $childs[Mage_Catalog_Model_Product_Link::LINK_TYPE_GROUPED];
                foreach ($childs as $value) {
                    $product = Mage::getModel('lengow/export_catalog_product')->load($value);
                    $price += $product->getPrice();
                    $final_price += $product->getFinalPrice();
                }
            } else {
                $price = $product_instance->getPrice();
                $final_price = $product_instance->getFinalPrice();
            }
        }
        if (!$config) {
            $price_excluding_tax = $price;
            $price_including_tax = $price + $calculator->calcTaxAmount($price, $taxPercent, false);
            $final_price_excluding_tax = $final_price;
            $final_price_including_tax = $final_price + $calculator->calcTaxAmount($final_price, $taxPercent, false);
        } else {
            $price_excluding_tax = Mage::helper('tax')->getPrice($product_instance, $price);
            $price_including_tax = $price;
            $final_price_excluding_tax = Mage::helper('tax')->getPrice($product_instance, $final_price);
            $final_price_including_tax = $final_price;
        }
        // get currency for convert
        if (!$this->getCurrentCurrencyCode()) {
            $toCurrency = $store->getCurrentCurrency();
        } else {
            $toCurrency = Mage::getModel('directory/currency')->load($this->getCurrentCurrencyCode());
        }
        $datas = array();
        $datas['currency'] = $toCurrency->getCode();
        // get prices with or without convertion
        if ($this->getOriginalCurrency() == $toCurrency->getCode()) {
            $discount_amount = $price_including_tax - $final_price_including_tax;
            $datas['price_excl_tax'] = round($final_price_excluding_tax, 2);
            $datas['price_incl_tax'] = round($final_price_including_tax, 2);
            $datas['price_before_discount_excl_tax'] = round($price_excluding_tax, 2);
            $datas['price_before_discount_incl_tax'] = round($price_including_tax, 2);
        } else {
            $discount_amount = Mage::helper('directory')->currencyConvert(
                $price_including_tax,
                $this->getOriginalCurrency(),
                $toCurrency
            ) - Mage::helper('directory')->currencyConvert(
                $final_price_including_tax,
                $this->getOriginalCurrency(),
                $this->getCurrentCurrencyCode()
            );
            $datas['price_excl_tax'] = round(Mage::helper('directory')->currencyConvert(
                $final_price_excluding_tax,
                $this->getOriginalCurrency(),
                $this->getCurrentCurrencyCode()
            ), 2);
            $datas['price_incl_tax'] = round(Mage::helper('directory')->currencyConvert(
                $final_price_including_tax,
                $this->getOriginalCurrency(),
                $this->getCurrentCurrencyCode()
            ), 2);
            $datas['price_before_discount_excl_tax'] = round(Mage::helper('directory')->currencyConvert(
                $price_excluding_tax,
                $this->getOriginalCurrency(),
                $this->getCurrentCurrencyCode()
            ), 2);
            $datas['price_before_discount_incl_tax'] = round(Mage::helper('directory')->currencyConvert(
                $price_including_tax,
                $this->getOriginalCurrency(),
                $this->getCurrentCurrencyCode()
            ), 2);
        }
        $datas['discount_amount'] = $discount_amount > 0 ? round($discount_amount, 2) : '0';
        $datas['discount_percent'] = $discount_amount > 0
            ? round(($discount_amount * 100) / $price_including_tax, 0)
            : '0';
        $datas['discount_start_date'] = $product_instance->getSpecialFromDate();
        $datas['discount_end_date'] = $product_instance->getSpecialToDate();
        // retrieving promotions
        $dateTs = Mage::app()->getLocale()->storeTimeStamp($product_instance->getStoreId());
        if (method_exists(Mage::getResourceModel('catalogrule/rule'), 'getRulesFromProduct')) {
            $promo = Mage::getResourceModel('catalogrule/rule')->getRulesFromProduct(
                $dateTs,
                $product_instance->getStoreId(),
                1,
                $product_instance->getId()
            );
        } elseif (method_exists(Mage::getResourceModel('catalogrule/rule'), 'getRulesForProduct')) {
            $promo = Mage::getResourceModel('catalogrule/rule')->getRulesForProduct(
                $dateTs,
                $product_instance->getStoreId(),
                $product_instance->getId()
            );
        }
        if (count($promo)) {
            $promo = $promo[0];
            if (isset($promo['from_time'])) {
                $from = $promo['from_time'];
            } else {
                $from = $promo['from_date'];
            }

            if (isset($promo['to_time'])) {
                $to = $promo['to_time'];
            } else {
                $to = $promo['to_date'];
            }
            $datas['discount_start_date'] = date('Y-m-d H:i:s', strtotime($from));
            $datas['discount_end_date'] = is_null($to) ? '' : date('Y-m-d H:i:s', strtotime($to));
        }
        return $datas;
    }

    /**
     * Get categories and breadcrumb
     *
     * @param Mage_Catalog_Model_Product $product_instance
     * @param Mage_Catalog_Model_Product $parent_instance
     * @param integer                    $store_id
     * @param array                      $categoryCache
     *
     * @return array
     */
    public function getCategories($product_instance, $parent_instance, $store_id, &$categoryCache = array())
    {
        $id_root_category = Mage::app()->getStore($store_id)->getRootCategoryId();
        if ($product_instance->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE
            && isset($parent_instance)
        ) {
            $categories = $parent_instance->getCategoryCollection()
                ->addPathsFilter('1/'.$id_root_category.'/')
                ->exportToArray();
        } else {
            $categories = $product_instance->getCategoryCollection()
                ->addPathsFilter('1/'.$id_root_category.'/')
                ->exportToArray();
        }
        if (isset($categoryCache[key(end($categories))])) {
            return $categoryCache[key(end($categories))];
        }
        //old config value #levelcategory
        $max_level = 5;
        $current_level = 0;
        $category_buffer = false;
        foreach ($categories as $category) {
            if ($category['level'] > $current_level) {
                $current_level = $category['level'];
                $category_buffer = $category;
            }
            if ($current_level > $max_level) {
                break;
            }
        }
        if (isset($category) && $category['path'] != '') {
            $categories = explode('/', $category_buffer['path']);
        } else {
            $categories = array();
        }
        $datas = array();
        $datas['category'] = '';
        $datas['category_url'] = '';
        for ($i = 1; $i <= $max_level; $i++) {
            $datas['category_sub_'.($i)] = '';
            $datas['category_url_sub_'.($i)] = '';
        }
        $i = 0;
        $ariane = array();
        foreach ($categories as $cid) {
            $c = Mage::getModel('catalog/category')
                ->setStoreId($store_id)
                ->load($cid);
            if ($c->getId() != 1) {
                // No root category
                if ($i == 0) {
                    $datas['category'] = $c->getName();
                    $datas['category_url'] = $c->getUrl();
                    $ariane[] = $c->getName();
                } elseif ($i <= $max_level) {
                    $ariane[] = $c->getName();
                    $datas['category_sub_'.$i] = $c->getName();
                    $datas['category_url_sub_'.$i] = $c->getUrl();
                }
                $i++;
            }
            if (method_exists($c, 'clearInstance')) {
                $c->clearInstance();
            }
        }
        $datas['category_breadcrum'] = implode(' > ', $ariane);
        $maxDimension = count($categories) - 1;
        if ($maxDimension >= 0) {
            $categoryCache[$categories[count($categories) - 1]] = $datas;
        }
        unset($categories, $category, $ariane);
        return $datas;
    }

    /**
     * Merge images child with images' parents
     *
     * @param array $images       of child's product
     * @param array $parentimages of parent's product
     *
     * @return array images merged
     */
    public function getImages($images, $parentimages = false)
    {
        if ($parentimages !== false) {
            $images = array_merge($parentimages, $images);
            $_images = array();
            $_ids = array();
            foreach ($images['images'] as $image) {
                if (array_key_exists('value_id', $image) && !in_array($image['value_id'], $_ids)) {
                    $_ids[] = $image['value_id'];
                    $_images[]['file'] = $image['file'];
                }
            }
            $images = $_images;
            unset($_images, $_ids, $parentimages);
        }
        $data = array();
        // old config value #max_images
        $max_image = 10;
        for ($i = 1; $i < $max_image + 1; $i++) {
            $data['image_url_'.$i] = '';
        }
        $c = 1;
        foreach ($images as $i) {
            $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product'.$i['file'];
            $data['image_url_'.$c++] = $url;
            if ($i == $max_image + 1) {
                break;
            }
        }
        return $data;
    }
}
