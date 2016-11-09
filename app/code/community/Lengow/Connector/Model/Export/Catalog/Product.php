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
    protected $_configHelper = true;

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
     * @param Mage_Catalog_Model_Product $productInstance
     * @param integer                    $storeId
     *
     * @return array
     */
    public function getShippingInfo($productInstance, $storeId)
    {
        $datas = array();
        $datas['shipping_method'] = '';
        $datas['shipping_cost'] = '';
        $carrier = $this->_configHelper->get('shipping_method', $storeId);
        if (empty($carrier)) {
            return $datas;
        }
        $carrierTab = explode('_', $carrier);
        $datas['shipping_method'] = ucfirst($carrierTab[1]);
        $countryCode = $this->_configHelper->get('shipping_country', $storeId);
        $shippingPrice = $this->_getShippingPrice($productInstance, $carrier, $countryCode);
        if (!$shippingPrice) {
            $shippingPrice = $this->_configHelper->get('shipping_price', $storeId);
        }
        $datas['shipping_cost'] = $shippingPrice;
        return $datas;
    }

    /**
     * Get shipping price
     *
     * @param Mage_Catalog_Model_Product $productInstance
     * @param string                     $carrierValue
     * @param string                     $countryCode
     *
     * @return mixed
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
     * @param Mage_Catalog_Model_Product $productInstance
     * @param string                     $countryCode
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
     * @param Mage_Catalog_Model_Product $productInstance
     * @param integer                    $storeId
     * @param Mage_Catalog_Model_Product $configurableInstance
     *
     * @return array
     */
    public function getPrices($productInstance, $storeId, $configurableInstance = null)
    {
        $store = Mage::app()->getStore($storeId);
        $config = Mage::helper('tax')->priceIncludesTax($store);
        $calculator = Mage::getSingleton('tax/calculation');
        $taxClassId = $productInstance->getTaxClassId();
        $request = $calculator->getRateRequest(null, null, null, $store);
        $taxPercent = $calculator->getRate($request->setProductClassId($taxClassId));
        /* @var $configurableInstance Mage_Catalog_Model_Product */
        if ($configurableInstance) {
            $price = $configurableInstance->getPrice();
            $finalPrice = $configurableInstance->getFinalPrice();
            $configurablePrice = 0;
            $configurableOldPrice = 0;
            $attributes = $configurableInstance->getTypeInstance(true)
                ->getConfigurableAttributes($configurableInstance);
            $attributes = Mage::helper('core')->decorateArray($attributes);
            if ($attributes) {
                foreach ($attributes as $attribute) {
                    $productAttribute = $attribute->getProductAttribute();
                    $attributeValue = $productInstance->getData($productAttribute->getAttributeCode());
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
            $configurableInstance->setConfigurablePrice($configurablePrice);
            $configurableInstance->setParentId(true);
            Mage::dispatchEvent(
                'catalog_product_type_configurable_price',
                array('product' => $configurableInstance)
            );
            $configurablePrice = $configurableInstance->getConfigurablePrice();
            $price = $productInstance->getPrice() + $configurableOldPrice;
            $finalPrice = $productInstance->getFinalPrice() + $configurablePrice;
        } else {
            if ($productInstance->getTypeId() == 'grouped') {
                $price = 0;
                $finalPrice = 0;
                $childs = Mage::getModel('catalog/product_type_grouped')->getChildrenIds($productInstance->getId());
                $childs = $childs[Mage_Catalog_Model_Product_Link::LINK_TYPE_GROUPED];
                foreach ($childs as $value) {
                    $product = Mage::getModel('lengow/export_catalog_product')->load($value);
                    $price += $product->getPrice();
                    $finalPrice += $product->getFinalPrice();
                }
            } else {
                $price = $productInstance->getPrice();
                $finalPrice = $productInstance->getFinalPrice();
            }
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
        $datas = array();
        $datas['currency'] = $toCurrency->getCode();
        // get prices with or without convertion
        if ($this->getOriginalCurrency() == $toCurrency->getCode()) {
            $discountAmount = $priceIncludingTax - $finalPriceIncludingTax;
            $datas['price_excl_tax'] = round($finalPriceExcludingTax, 2);
            $datas['price_incl_tax'] = round($finalPriceIncludingTax, 2);
            $datas['price_before_discount_excl_tax'] = round($priceExcludingTax, 2);
            $datas['price_before_discount_incl_tax'] = round($priceIncludingTax, 2);
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
            $datas['price_excl_tax'] = round(
                Mage::helper('directory')->currencyConvert(
                    $finalPriceExcludingTax,
                    $this->getOriginalCurrency(),
                    $this->getCurrentCurrencyCode()
                ),
                2
            );
            $datas['price_incl_tax'] = round(
                Mage::helper('directory')->currencyConvert(
                    $finalPriceIncludingTax,
                    $this->getOriginalCurrency(),
                    $this->getCurrentCurrencyCode()
                ),
                2
            );
            $datas['price_before_discount_excl_tax'] = round(
                Mage::helper('directory')->currencyConvert(
                    $priceExcludingTax,
                    $this->getOriginalCurrency(),
                    $this->getCurrentCurrencyCode()
                ),
                2
            );
            $datas['price_before_discount_incl_tax'] = round(
                Mage::helper('directory')->currencyConvert(
                    $priceIncludingTax,
                    $this->getOriginalCurrency(),
                    $this->getCurrentCurrencyCode()
                ),
                2
            );
        }
        $datas['discount_amount'] = $discountAmount > 0 ? round($discountAmount, 2) : '0';
        $datas['discount_percent'] = $discountAmount > 0
            ? round(($discountAmount * 100) / $priceIncludingTax, 0)
            : '0';
        $datas['discount_start_date'] = $productInstance->getSpecialFromDate();
        $datas['discount_end_date'] = $productInstance->getSpecialToDate();
        // retrieving promotions
        $dateTs = Mage::app()->getLocale()->storeTimeStamp($productInstance->getStoreId());
        if (method_exists(Mage::getResourceModel('catalogrule/rule'), 'getRulesFromProduct')) {
            $promo = Mage::getResourceModel('catalogrule/rule')->getRulesFromProduct(
                $dateTs,
                $productInstance->getStoreId(),
                1,
                $productInstance->getId()
            );
        } elseif (method_exists(Mage::getResourceModel('catalogrule/rule'), 'getRulesForProduct')) {
            $promo = Mage::getResourceModel('catalogrule/rule')->getRulesForProduct(
                $dateTs,
                $productInstance->getStoreId(),
                $productInstance->getId()
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
     * @param Mage_Catalog_Model_Product $productInstance
     * @param Mage_Catalog_Model_Product $parentInstance
     * @param integer                    $storeId
     * @param array                      $categoryCache
     *
     * @return array
     */
    public function getCategories($productInstance, $parentInstance, $storeId, &$categoryCache = array())
    {
        $idRootCategory = Mage::app()->getStore($storeId)->getRootCategoryId();
        if ($productInstance->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE
            && isset($parentInstance)
        ) {
            $categories = $parentInstance->getCategoryCollection()
                ->addPathsFilter('1/'.$idRootCategory.'/')
                ->exportToArray();
        } else {
            $categories = $productInstance->getCategoryCollection()
                ->addPathsFilter('1/'.$idRootCategory.'/')
                ->exportToArray();
        }
        if (is_array($categories) && count($categories) > 0) {
            if (isset($categoryCache[key(end($categories))])) {
                return $categoryCache[key(end($categories))];
            }
        }
        //old config value #levelcategory
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
        if (isset($category) && $category['path'] != '') {
            $categories = explode('/', $categoryBuffer['path']);
        } else {
            $categories = array();
        }
        $datas = array();
        $datas['category'] = '';
        $datas['category_url'] = '';
        for ($i = 1; $i <= $maxLevel; $i++) {
            $datas['category_sub_'.($i)] = '';
            $datas['category_url_sub_'.($i)] = '';
        }
        $i = 0;
        $ariane = array();
        foreach ($categories as $cid) {
            $c = Mage::getModel('catalog/category')
                ->setStoreId($storeId)
                ->load($cid);
            if ($c->getId() != 1) {
                // No root category
                if ($i == 0) {
                    $datas['category'] = $c->getName();
                    $datas['category_url'] = $c->getUrl();
                    $ariane[] = $c->getName();
                } elseif ($i <= $maxLevel) {
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
        // old config value #maxImage
        $maxImage = 10;
        for ($i = 1; $i < $maxImage + 1; $i++) {
            $data['image_url_'.$i] = '';
        }
        $c = 1;
        foreach ($images as $i) {
            $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product'.$i['file'];
            $data['image_url_'.$c++] = $url;
            if ($i == $maxImage + 1) {
                break;
            }
        }
        return $data;
    }
}
