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
 * Model export
 */
class Lengow_Connector_Model_Export extends Varien_Object
{
    /* Export GET params */
    const PARAM_TOKEN = 'token';
    const PARAM_MODE = 'mode';
    const PARAM_FORMAT = 'format';
    const PARAM_STREAM = 'stream';
    const PARAM_OFFSET = 'offset';
    const PARAM_LIMIT = 'limit';
    const PARAM_TYPE = 'type';
    const PARAM_SELECTION = 'selection';
    const PARAM_OUT_OF_STOCK = 'out_of_stock';
    const PARAM_PRODUCT_IDS = 'product_ids';
    const PARAM_PRODUCT_TYPES = 'product_types';
    const PARAM_INACTIVE = 'inactive';
    const PARAM_STORE = 'store';
    const PARAM_STORE_ID = 'store_id';
    const PARAM_CODE = 'code';
    const PARAM_CURRENCY = 'currency';
    const PARAM_LANGUAGE = 'language';
    const PARAM_LEGACY_FIELDS = 'legacy_fields';
    const PARAM_LOG_OUTPUT = 'log_output';
    const PARAM_UPDATE_EXPORT_DATE = 'update_export_date';
    const PARAM_GET_PARAMS = 'get_params';

    /* Legacy export GET params for old versions */
    const PARAM_LEGACY_SELECTION = 'selected_products';
    const PARAM_LEGACY_OUT_OF_STOCK = 'product_out_of_stock';
    const PARAM_LEGACY_PRODUCT_IDS = 'ids_product';
    const PARAM_LEGACY_PRODUCT_TYPES = 'product_type';
    const PARAM_LEGACY_INACTIVE = 'product_status';
    const PARAM_LEGACY_LANGUAGE = 'locale';

    /* Export types */
    const TYPE_MANUAL = 'manual';
    const TYPE_CRON = 'cron';
    const TYPE_MAGENTO_CRON = 'magento cron';

    /**
     * @var array all available params for export
     */
    protected $_exportParams = array(
        self::PARAM_MODE,
        self::PARAM_FORMAT,
        self::PARAM_STREAM,
        self::PARAM_OFFSET,
        self::PARAM_LIMIT,
        self::PARAM_SELECTION,
        self::PARAM_OUT_OF_STOCK,
        self::PARAM_PRODUCT_IDS,
        self::PARAM_PRODUCT_TYPES,
        self::PARAM_INACTIVE,
        self::PARAM_STORE,
        self::PARAM_CODE,
        self::PARAM_CURRENCY,
        self::PARAM_LANGUAGE,
        self::PARAM_LEGACY_FIELDS,
        self::PARAM_LOG_OUTPUT,
        self::PARAM_UPDATE_EXPORT_DATE,
        self::PARAM_GET_PARAMS,
    );

    /**
     * @var array default fields
     */
    protected $_defaultFields;

    /**
     * @var array new fields for v3
     */
    protected $_newFields = array(
        'id' => 'id',
        'sku' => 'sku',
        'name' => 'name',
        'child_name' => 'child_name',
        'quantity' => 'quantity',
        'status' => 'active',
        'category' => 'category_breadcrumb',
        'url' => 'url',
        'price_excl_tax' => 'price_excl_tax',
        'price_incl_tax' => 'price_incl_tax',
        'price_before_discount_excl_tax' => 'price_before_discount_excl_tax',
        'price_before_discount_incl_tax' => 'price_before_discount_incl_tax',
        'discount_amount' => 'discount_amount',
        'discount_percent' => 'discount_percent',
        'discount_start_date' => 'discount_start_date',
        'discount_end_date' => 'discount_end_date',
        'shipping_method' => 'shipping_method',
        'shipping_cost' => 'shipping_cost',
        'currency' => 'currency',
        'image_default' => 'image_default',
        'image_url_1' => 'image_url_1',
        'image_url_2' => 'image_url_2',
        'image_url_3' => 'image_url_3',
        'image_url_4' => 'image_url_4',
        'image_url_5' => 'image_url_5',
        'image_url_6' => 'image_url_6',
        'image_url_7' => 'image_url_7',
        'image_url_8' => 'image_url_8',
        'image_url_9' => 'image_url_9',
        'image_url_10' => 'image_url_10',
        'type' => 'type',
        'parent_id' => 'parent_id',
        'variation' => 'variation',
        'language' => 'language',
        'description' => 'description',
        'description_html' => 'description_html',
        'description_short' => 'description_short',
        'description_short_html' => 'description_short_html',
    );

    /**
     * @var array legacy fields for retro-compatibility
     */
    protected $_legacyFields = array(
        'sku' => 'sku',
        'product_id' => 'id',
        'qty' => 'quantity',
        'status' => 'active',
        'category-breadcrumb' => 'category_breadcrumb',
        'category' => 'category',
        'category-url' => 'category_url',
        'category-sub-1' => 'category_sub_1',
        'category-url-sub-1' => 'category_url_sub_1',
        'category-sub-2' => 'category_sub_2',
        'category-url-sub-2' => 'category_url_sub_2',
        'category-sub-3' => 'category_sub_3',
        'category-url-sub-3' => 'category_url_sub_3',
        'category-sub-4' => 'category_sub_4',
        'category-url-sub-4' => 'category_url_sub_4',
        'category-sub-5' => 'category_sub_5',
        'category-url-sub-5' => 'category_url_sub_5',
        'price-ttc' => 'price_incl_tax',
        'price-before-discount' => 'price_before_discount_incl_tax',
        'discount-amount' => 'discount_amount',
        'discount-percent' => 'discount_percent',
        'start-date-discount' => 'discount_start_date',
        'end-date-discount' => 'discount_end_date',
        'shipping-name' => 'shipping_method',
        'shipping-price' => 'shipping_cost',
        'image-url-1' => 'image_url_1',
        'image-url-2' => 'image_url_2',
        'image-url-3' => 'image_url_3',
        'image-url-4' => 'image_url_4',
        'image-url-5' => 'image_url_5',
        'image-url-6' => 'image_url_6',
        'image-url-7' => 'image_url_7',
        'image-url-8' => 'image_url_8',
        'image-url-9' => 'image_url_9',
        'image-url-10' => 'image_url_10',
        'product-url' => 'url',
        'name' => 'name',
        'description' => 'description_html',
        'short_description' => 'description_short_html',
        'parent_id' => 'parent_id',
        'product_type' => 'type',
        'product_variation' => 'variation',
        'image_default' => 'image_default',
        'child_name' => 'child_name',
    );

    /**
     * @var array excludes attributes for export
     */
    protected $_excludes = array(
        'media_gallery',
        'tier_price',
        'short_description',
        'description',
        'quantity',
        'price',
        'lengow_product',
        'status',
    );

    /**
     * @var array available formats for export
     */
    protected $_availableFormats = array(
        'csv',
        'json',
        'yaml',
        'xml',
    );
    /**
     * @var array available product types for export
     */
    protected $_availableProductTypes = array(
        'configurable',
        'simple',
        'downloadable',
        'grouped',
        'virtual',
    );

    /**
     * @var boolean is the export output stream ?
     */
    protected $_stream;

    /**
     * @var boolean use legacy fields or not
     */
    protected $_legacy;

    /**
     * @var Mage_Core_Model_Store Magento store instance
     */
    protected $_store;

    /**
     * @var integer Magento store id
     */
    protected $_storeId;

    /**
     * @var Varien_Io_File Magento Varien io file instance
     */
    protected $_file;

    /**
     * @var string filename of output file
     */
    protected $_fileName = 'lengow_feed';

    /**
     * @var integer|null timestamp of output file
     */
    protected $_fileTimeStamp;

    /**
     * @var array all config options
     */
    protected $_config = array();

    /**
     * @var string file format for export
     */
    protected $_fileFormat;

    /**
     * @var boolean update export date or not
     */
    protected $_updateExportDate;

    /**
     * @var string type import (manual, cron or magento cron)
     */
    protected $_typeExport;

    /**
     * @var boolean display log messages
     */
    protected $_logOutput = false;

    /**
     * @var Lengow_Connector_Helper_Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var Lengow_Connector_Helper_Data Lengow helper instance
     */
    protected $_helper;

    /**
     * @var array cache parent products
     */
    protected $_cacheParentProducts = array();

    /**
     * @var integer clear parent cache
     */
    protected $_clearParentCache = 0;

    /**
     * @var array cache category
     */
    protected $_cacheCategory = array();

    /**
     * Construct the export manager
     * @param array $params optional options
     * integer store_id       ID of store
     * integer limit          The number of product to be exported
     * integer offset         From what product export
     * string  format         Export Format (csv|yaml|xml|json)
     * string  type           Type of export (manual, cron or magento cron)
     * string  product_type   Type(s) of product (configurable, simple, downloadable, grouped or virtual)
     * string  inactive       Export inactive product (1) or not (0)
     * string  currency       Currency for export
     * string  product_ids    Ids product to export
     * boolean out_of_stock   Export product in stock and out stock (1) | Export Only in stock product (0)
     * boolean selection      Export selected product (1) | Export all products (0)
     * boolean stream         Display file when call script (1) | Save File (0)
     * boolean legacy_fields  Export with legacy fields (1) | Export with new fields (0)
     * boolean log_output     See logs (only when stream = 0) (1) | no logs (0)
     *
     * @throws Exception
     */
    public function __construct($params)
    {
        // get helpers
        $this->_configHelper = Mage::helper('lengow_connector/config');
        $this->_helper = Mage::helper('lengow_connector/data');
        // get store and store id
        $storeId = isset($params[self::PARAM_STORE_ID]) ? (int) $params[self::PARAM_STORE_ID] : false;
        $this->_store = Mage::app()->getStore($storeId);
        $this->_storeId = $this->_store->getId();
        // get format (csv by default)
        $format = isset($params[self::PARAM_FORMAT]) ? $params[self::PARAM_FORMAT] : null;
        if ($format === null || !in_array($format, $this->_availableFormats, true)) {
            $this->_fileFormat = 'csv';
        } else {
            $this->_fileFormat = $format;
        }
        // get stream export or export in a file
        $this->_stream = isset($params[self::PARAM_STREAM])
            ? (bool) $params[self::PARAM_STREAM]
            : ! (bool) $this->_configHelper->get(Lengow_Connector_Helper_Config::EXPORT_FILE_ENABLED, $this->_storeId);
        // get legacy fields or new fields
        $this->_legacy = isset($params[self::PARAM_LEGACY_FIELDS]) ? (bool) $params[self::PARAM_LEGACY_FIELDS] : null;
        // update last export date or not
        $this->_updateExportDate = !isset($params[self::PARAM_UPDATE_EXPORT_DATE])
            || $params[self::PARAM_UPDATE_EXPORT_DATE];
        // see logs or not (only when stream = 0)
        if ($this->_stream) {
            $this->_logOutput = false;
        } else {
            $this->_logOutput = !isset($params[self::PARAM_LOG_OUTPUT]) || $params[self::PARAM_LOG_OUTPUT];
        }
        // get export type
        $this->_typeExport = isset($params[self::PARAM_TYPE]) ? $params[self::PARAM_TYPE] : false;
        if (!$this->_typeExport) {
            $this->_typeExport = $this->_updateExportDate ? self::TYPE_CRON : self::TYPE_MANUAL;
        }
        // get configuration params
        $this->_config[self::PARAM_SELECTION] = isset($params[self::PARAM_SELECTION])
            ? (bool) $params[self::PARAM_SELECTION]
            : (bool) $this->_configHelper->get(Lengow_Connector_Helper_Config::SELECTION_ENABLED, $this->_storeId);
        $this->_config[self::PARAM_INACTIVE] = isset($params[self::PARAM_INACTIVE])
            ? (bool) $params[self::PARAM_INACTIVE]
            : (bool) $this->_configHelper->get(Lengow_Connector_Helper_Config::INACTIVE_ENABLED, $this->_storeId);
        $this->_config[self::PARAM_OUT_OF_STOCK] = isset($params[self::PARAM_OUT_OF_STOCK])
            ? (bool) $params[self::PARAM_OUT_OF_STOCK]
            : (bool) $this->_configHelper->get(Lengow_Connector_Helper_Config::OUT_OF_STOCK_ENABLED, $this->_storeId);
        $this->_config[self::PARAM_PRODUCT_TYPES] = isset($params[self::PARAM_PRODUCT_TYPES])
            ? $params[self::PARAM_PRODUCT_TYPES]
            : $this->_configHelper->get(Lengow_Connector_Helper_Config::EXPORT_PRODUCT_TYPES, $this->_storeId);
        $this->_config[self::PARAM_OFFSET] = isset($params[self::PARAM_OFFSET]) ? (int) $params[self::PARAM_OFFSET] : 0;
        $this->_config[self::PARAM_LIMIT] = isset($params[self::PARAM_LIMIT]) ? (int) $params[self::PARAM_LIMIT] : 0;
        $this->_config[self::PARAM_PRODUCT_IDS] = isset($params[self::PARAM_PRODUCT_IDS])
            ? $params[self::PARAM_PRODUCT_IDS]
            : '';
        $sep = DIRECTORY_SEPARATOR;
        $this->_config['directory_path'] = Mage::getBaseDir('media')
            . $sep . 'lengow' . $sep . $this->_store->getCode() . $sep;
        // set currency code for export
        $currencyCode = isset($params[self::PARAM_CURRENCY])
            ? $params[self::PARAM_CURRENCY]
            : Mage::app()->getStore($storeId)->getCurrentCurrencyCode();
        $this->setCurrentCurrencyCode($currencyCode);
    }

    /**
     * Execute export
     *
     * @throws Exception
     */
    public function exec()
    {
        // start timer
        $timeStart = $this->_microtimeFloat();
        // clean logs > 20 days
        $this->_helper->cleanLog();
        // check if export is already launch
        if ($this->_isAlreadyLaunch()) {
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_EXPORT,
                $this->_helper->__('log.export.feed_already_launch'),
                $this->_logOutput
            );
            return false;
        }
        // get products list to export
        $productCollection = $this->_getQuery();
        // limit & offset
        if ($this->_config[self::PARAM_LIMIT] > 0) {
            if ($this->_config[self::PARAM_OFFSET] > 0) {
                $productCollection->getSelect()->limit(
                    $this->_config[self::PARAM_LIMIT],
                    $this->_config[self::PARAM_OFFSET]
                );
            } else {
                $productCollection->getSelect()->limit($this->_config[self::PARAM_LIMIT]);
            }
        }
        // ids product
        if ($this->_config[self::PARAM_PRODUCT_IDS]) {
            $productIds = explode(',', $this->_config[self::PARAM_PRODUCT_IDS]);
            $productCollection->addAttributeToFilter('entity_id', array('in' => $productIds));
        }
        $this->_helper->log(
            Lengow_Connector_Helper_Data::CODE_EXPORT,
            $this->_helper->__('log.export.start', array('type' => $this->_typeExport)),
            $this->_logOutput
        );
        $this->_helper->log(
            Lengow_Connector_Helper_Data::CODE_EXPORT,
            $this->_helper->__(
                'log.export.start_for_store',
                array(
                    'store_name' => $this->_store->getName(),
                    'store_id' => $this->_storeId,
                )
            ),
            $this->_logOutput
        );
        // set legacy fields option
        $this->_setLegacyFields();
        // get attributes to export
        $attributesToExport = $this->_configHelper->getSelectedAttributes($this->_storeId);
        // get attribute to export from parent instead of child
        $parentFieldToExport = $this->_configHelper->getParentSelectedAttributes($this->_storeId);
        // set feed format
        /** @var Lengow_Connector_Model_Export_Feed_Abstract $feed */
        $feed = Mage::getModel('Lengow_Connector_Model_Export_Feed_' . ucfirst($this->_fileFormat));
        $first = true;
        $last = false;
        $pi = 1;
        $productCollection->getSelect()->distinct(true)->group('entity_id');
        // get all product ids
        $products = $productCollection->getData();
        $totalProduct = count($products);
        $this->_helper->log(
            Lengow_Connector_Helper_Data::CODE_EXPORT,
            $this->_helper->__('log.export.nb_product_found', array('nb_product' => $totalProduct)),
            $this->_logOutput
        );
        // modulo for export counter
        $moduloExport = (int) ($totalProduct / 10);
        $moduloExport = $moduloExport < 50 ? 50 : $moduloExport;
        // Product counter
        $countSimple = 0;
        $countSimpleDisabled = 0;
        $countConfigurable = 0;
        $countGrouped = 0;
        $countVirtual = 0;
        $countDownloadable = 0;
        // Generate data
        foreach ($products as $p) {
            $datas = array();
            $pi++;
            if ($totalProduct < $pi) {
                $last = true;
            }
            /** @var Lengow_Connector_Model_Export_Catalog_Product $product */
            $product = Mage::getModel('lengow/export_catalog_product')
                ->setStoreId($this->_storeId)
                ->setOriginalCurrency($this->getOriginalCurrency())
                ->setCurrentCurrencyCode($this->getCurrentCurrencyCode())
                ->load($p['entity_id']);
            $data = $product->getData();
            // load first parent if exist
            $parents = null;
            $parentInstance = null;
            $parentId = null;
            $productType = 'simple';
            $variationName = '';
            // configurable products
            if ($product->getTypeId() === 'configurable') {
                $countConfigurable++;
                $productType = 'parent';
                $productTemp = $product;
                $variations = $productTemp
                    ->setOriginalCurrency($this->getOriginalCurrency())
                    ->setCurrentCurrencyCode($this->getCurrentCurrencyCode())
                    ->setStoreId($this->_storeId)
                    ->getTypeInstance(true)
                    ->getConfigurableAttributesAsArray($product);
                if ($variations) {
                    foreach ($variations as $variation) {
                        $variationName .= $variation['frontend_label'] . ',';
                    }
                    $variationName = rtrim($variationName, ',');
                }
            }
            // virtual product
            if ($product->getTypeId() === 'virtual') {
                $countVirtual++;
                $productType = 'virtual';
            }
            // downloadable products
            if ($product->getTypeId() === 'downloadable') {
                $countDownloadable++;
                $productType = 'downloadable';
            }
            // grouped products
            if ($product->getTypeId() === 'grouped') {
                $countGrouped++;
                $productType = 'grouped';
                // get quantity for grouped products
                $qtys = array();
                $childrenIds = array_reduce(
                    $product->getTypeInstance(true)->getChildrenIds($product->getId()),
                    function (array $reduce, $value) {
                        return array_merge($reduce, $value);
                    },
                    array()
                );
                foreach ($childrenIds as $childrenId) {
                    $productTemporary = Mage::getModel('catalog/product')
                        ->setOriginalCurrency($this->getOriginalCurrency())
                        ->setCurrentCurrencyCode($this->getCurrentCurrencyCode())
                        ->setStoreId($this->_storeId)
                        ->load($childrenId);
                    $qtys[] = $productTemporary->getData('stock_item')->getQty();
                    unset($productTemporary);
                }
                $qtyTemp = min($qtys) > 0 ? min($qtys) : 0;
            }
            // simple Products
            if ($product->getTypeId() === 'simple') {
                $countSimple++;
                $parents = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($p['entity_id']);
                if (!empty($parents)) {
                    $parentInstance = $this->_getParentEntity((int) $parents[0]);
                    // exclude if parent is disabled
                    if ($parentInstance
                        && !$this->_config[self::PARAM_INACTIVE]
                        && (int) $parentInstance->getStatus() === Mage_Catalog_Model_Product_Status::STATUS_DISABLED
                    ) {
                        $countSimpleDisabled++;
                        if (method_exists($product, 'clearInstance')) {
                            $product->clearInstance();
                        }
                        unset($datas);
                        continue;
                    }
                    if ($parentInstance
                        && $parentInstance->getId()
                        && $parentInstance->getTypeId() === 'configurable'
                    ) {
                        $parentId = $parentInstance->getId();
                        $variations = $parentInstance->getTypeInstance(true)
                            ->getConfigurableAttributesAsArray($parentInstance);
                        if ($variations) {
                            foreach ($variations as $variation) {
                                $variationName .= $variation['frontend_label'] . ',';
                            }
                            $variationName = rtrim($variationName, ',');
                        }
                        $productType = 'child';
                    }
                }
            }
            $parents = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild(
                $parentId ? $parentId : $p['entity_id']
            );
            if (!empty($parents)) {
                $tempInstance = Mage::getModel('catalog/product')
                    ->setOriginalCurrency($this->getOriginalCurrency())
                    ->setCurrentCurrencyCode($this->getCurrentCurrencyCode())
                    ->setStoreId($this->_storeId)
                    ->getCollection()
                    ->addAttributeToFilter('type_id', 'grouped')
                    ->addAttributeToFilter('entity_id', array('in' => $parents))
                    ->getFirstItem();
                $parentInstance = $this->_getParentEntity($tempInstance->getId());
            }
            $qty = $product->getData('stock_item');
            // default data
            $datas['sku'] = $product->getSku();
            $datas['id'] = $product->getId();
            $datas['quantity'] = (int) $qty->getQty();
            // we don't send qty ordered (old settings : without_product_ordering)
            $datas['quantity'] = $datas['quantity'] - (int) $qty->getQtyOrdered();
            if ($product->getTypeId() === 'grouped') {
                $datas['quantity'] = (int) $qtyTemp;
            }
            $datas['active'] = (int) $product->getStatus() === Mage_Catalog_Model_Product_Status::STATUS_DISABLED
                ? 'Disabled'
                : 'Enabled';
            $datas = array_merge(
                $datas,
                $product->getCategories($product, $parentInstance, $this->_storeId, $this->_cacheCategory)
            );
            $datas = array_merge($datas, $product->getPrices($product, $this->_storeId));
            $datas = array_merge($datas, $product->getShippingInfo($product, $this->_storeId));
            // merge between children and parent images
            if ($this->_configHelper->get(Lengow_Connector_Helper_Config::EXPORT_PARENT_IMAGE_ENABLED, $this->_storeId)
                && isset($parentInstance)
                && $parentInstance !== false
            ) {
                $datas = array_merge(
                    $datas,
                    $product->getImages($data['media_gallery']['images'], $parentInstance->getData('media_gallery'))
                );
            } else {
                $datas = array_merge($datas, $product->getImages($data['media_gallery']['images']));
            }
            if ((int) $product->getVisibility() === Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE
                && isset($parentInstance)
            ) {
                $datas['url'] = $parentInstance->getUrlInStore()
                    ? $parentInstance->getUrlInStore()
                    : $parentInstance->getProductUrl();
                $datas['name'] = $this->_helper->cleanData($parentInstance->getName());
                $datas['description'] = $this->_helper->cleanData($parentInstance->getDescription(), false);
                $datas['description_html'] = $this->_helper->cleanData($parentInstance->getDescription());
                $datas['description_short'] = $this->_helper->cleanData($parentInstance->getShortDescription(), false);
                $datas['description_short_html'] = $this->_helper->cleanData($parentInstance->getShortDescription());
            } else {
                $datas['url'] = $product->getUrlInStore() ? $product->getUrlInStore() : $product->getProductUrl();
                $datas['name'] = $this->_helper->cleanData($product->getName());
                $datas['description'] = $this->_helper->cleanData($product->getDescription(), false);
                $datas['description_html'] = $this->_helper->cleanData($product->getDescription());
                $datas['description_short'] = $this->_helper->cleanData($product->getShortDescription(), false);
                $datas['description_short_html'] = $this->_helper->cleanData($product->getShortDescription());
            }
            $datas['parent_id'] = $parentId;
            // product variation
            $datas['type'] = $productType;
            $datas['variation'] = $variationName;
            $datas['image_default'] = ($product->getImage() !== null && $product->getImage() !== 'no_selection')
                ? Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage()
                : '';
            $datas['child_name'] = $this->_helper->cleanData($product->getName());
            $datas['language'] = Mage::app()->getLocale()->getLocaleCode();
            // get correct feed
            $productDatas = array();
            foreach ($this->_defaultFields as $key => $value) {
                $productDatas[$key] = $datas[$value];
            }
            unset($datas);
            // selected attributes to export with Frond End value of current store
            if (!empty($attributesToExport)) {
            	// load category_ids attribute
            	$product->getCategoryIds();
                foreach ($attributesToExport as $field => $attr) {
                    if (!isset($productDatas[$field]) && $field !== '' && !in_array($field, $this->_excludes, true)) {
                        // case attribute have to be retrieve from parent
                        if ($parentInstance && in_array($field, $parentFieldToExport, true)) {
                            $productRef = $parentInstance;
                        } else {
                            $productRef = $product;
                        }
                        $value = $productRef->getData($field);
                        if ( ! $value) {
                            $productDatas[$attr] = '';
                        } elseif (is_array($value)) {
                            $productDatas[$attr] = implode(',', $value);
                        } else {
                            $productDatas[$attr] = $this->_helper->cleanData(
                                $productRef->getResource()
                                           ->getAttribute($field)
                                           ->getFrontend()
                                           ->getValue($productRef)
                            );
                        }
                    }
                }
            }
            // get the maximum of character for yaml format
            $maxCharacter = 0;
            foreach ($productDatas as $key => $value) {
                if (strlen($key) > $maxCharacter) {
                    $maxCharacter = strlen($key);
                }
            }
            // get header of feed
            if ($first) {
                $fieldsHeader = array();
                foreach ($productDatas as $name => $value) {
                    $fieldsHeader[] = $name;
                }
                // get content type if streamed feed
                if ($this->_stream) {
                    header('Content-Type: ' . $feed->getContentType() . '; charset=UTF-8');
                    if ($this->_fileFormat === 'csv') {
                        header('Content-Disposition: attachment; filename=feed.csv');
                    }
                }
                $feed->setFields($fieldsHeader);
                if (!$this->_write($feed->makeHeader())) {
                    return false;
                }
                $first = false;
            }
            $this->_write(
                $feed->makeData(
                    $productDatas,
                    array(
                        'last' => $last,
                        'max_character' => $maxCharacter,
                    )
                )
            );
            // save 10 logs maximum in database
            if ($pi % $moduloExport === 0) {
                $this->_helper->log(
                    Lengow_Connector_Helper_Data::CODE_EXPORT,
                    $this->_helper->__('log.export.count_product', array('product_count' => $pi))
                );
            }
            if (!$this->_stream && $this->_logOutput) {
                if ($pi % 50 === 0) {
                    $countMessage = $this->_helper->__('log.export.count_product', array('product_count' => $pi));
                    print_r('[Export] ' . $countMessage . '<br />');
                }
                flush();
            }
            if (method_exists($product, 'clearInstance')) {
                $product->clearInstance();
            }
            unset($productDatas);
        }
        $this->_write($feed->makeFooter());
        // product counter
        $totalSimple = $countSimple - $countSimpleDisabled;
        $total = $countConfigurable + $countGrouped + $countDownloadable + $countVirtual + $totalSimple;
        $this->_helper->log(
            Lengow_Connector_Helper_Data::CODE_EXPORT,
            $this->_helper->__(
                'log.export.total_product_exported',
                array(
                    'nb_product' => $total,
                    'nb_simple' => $totalSimple,
                    'nb_configurable' => $countConfigurable,
                    'nb_grouped' => $countGrouped,
                    'nb_virtual' => $countVirtual,
                    'nb_downloadable' => $countDownloadable,
                )
            ),
            $this->_logOutput
        );
        // warning for simple product associated with configurable products disabled
        if ($countSimpleDisabled > 0) {
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_EXPORT,
                $this->_helper->__(
                    'log.export.error_configurable_product_disabled',
                    array('nb_product' => $countSimpleDisabled)
                ),
                $this->_logOutput
            );
        }
        // link generation
        if (!$this->_stream) {
            $this->_copyFile();
            $urlFile = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)
                . 'lengow' . DS . $this->_store->getCode() . DS . $this->_fileName . '.' . $this->_fileFormat;
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_EXPORT,
                $this->_helper->__(
                    'log.export.generate_feed_available_here',
                    array(
                        'store_name' => $this->_store->getName(),
                        'store_id' => $this->_storeId,
                        'feed_url' => $urlFile,
                    )
                ),
                $this->_logOutput
            );
        }
        // update last export date
        if ($this->_updateExportDate) {
            $this->_configHelper->set(
                Lengow_Connector_Helper_Config::LAST_UPDATE_EXPORT,
                Mage::getModel('core/date')->gmtTimestamp(),
                $this->_storeId
            );
        }
        $timeEnd = $this->_microtimeFloat();
        $time = $timeEnd - $timeStart;
        $this->_helper->log(
            Lengow_Connector_Helper_Data::CODE_EXPORT,
            $this->_helper->__('log.export.memory_usage', array('memory' => round(memory_get_usage() / 1000000, 2))),
            $this->_logOutput
        );
        $this->_helper->log(
            Lengow_Connector_Helper_Data::CODE_EXPORT,
            $this->_helper->__('log.export.execution_time', array('seconds' => round($time, 2))),
            $this->_logOutput
        );
        $this->_helper->log(
            Lengow_Connector_Helper_Data::CODE_EXPORT,
            $this->_helper->__('log.export.end', array('type' => $this->_typeExport)),
            $this->_logOutput
        );
    }

    /**
     * Set or not legacy fields to export
     */
    protected function _setLegacyFields()
    {
        if ($this->_legacy === null) {
            $statusAccount = Mage::helper('lengow_connector/sync')->getStatusAccount();
            if ($statusAccount && isset($statusAccount['legacy'])) {
                $this->_legacy = $statusAccount['legacy'];
            } else {
                $this->_legacy = false;
            }
        }
        $this->_defaultFields = $this->_legacy ? $this->_legacyFields : $this->_newFields;
    }

    /**
     * Temporary store Parent Identity
     *
     * @param integer $parentId Magento parent entity id
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function _getParentEntity($parentId)
    {
        if (!isset($this->_cacheParentProducts[$parentId])) {
            if ($this->_clearParentCache > 300) {
                foreach ($this->_cacheParentProducts as $parentProduct) {
                    if (method_exists($parentProduct, 'clearInstance')) {
                        $parentProduct->clearInstance();
                    }
                }
                $this->_clearParentCache = 0;
                $this->_cacheParentProducts = array();
            }
            $parent = Mage::getModel('lengow/export_catalog_product')
                ->setStoreId($this->_storeId)
                ->setOriginalCurrency($this->getOriginalCurrency())
                ->setCurrentCurrencyCode($this->getCurrentCurrencyCode())
                ->load($parentId);
            $this->_cacheParentProducts[$parentId] = $parent;
            $this->_clearParentCache++;
        }
        return $this->_cacheParentProducts[$parentId];
    }

    /**
     * Get products collection for export
     *
     * @return array
     */
    public function _getQuery()
    {
        // filters
        $selection = $this->_config[self::PARAM_SELECTION];
        $inactive = $this->_config[self::PARAM_INACTIVE];
        $outOfStock = $this->_config[self::PARAM_OUT_OF_STOCK];
        $productTypes = explode(',', $this->_config[self::PARAM_PRODUCT_TYPES]);
        // disable flat catalog on the fly
        $flatProcess = Mage::helper('catalog/product_flat')->getProcess();
        $flatProcessStatus = $flatProcess->getStatus();
        $flatProcess->setStatus(Mage_Index_Model_Process::STATUS_RUNNING);
        // search product to export
        $productCollection = Mage::getModel('catalog/product')
            ->getCollection()
            ->setStoreId($this->_storeId)
            ->addStoreFilter($this->_storeId)
            ->addAttributeToFilter('type_id', array('in' => $productTypes))
            ->joinField(
                'store_id',
                Mage::getConfig()->getTablePrefix() . 'catalog_category_product_index',
                'store_id',
                'product_id=entity_id',
                '{{table}}.store_id = ' . $this->_storeId,
                'left'
            );
        // get only enabled products
        if (!$inactive) {
            $productCollection->addAttributeToFilter(
                'status',
                array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
            );
        }
        // export only selected products
        if ($selection) {
            $productCollection->addAttributeToFilter('lengow_product', 1);
        }
        $productCollection->joinTable(
            'cataloginventory/stock_item',
            'product_id=entity_id',
            array('qty' => 'qty', 'is_in_stock' => 'is_in_stock'),
            $this->_getOutOfStockSQL($outOfStock),
            'inner'
        );
        // filter to hide products
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($productCollection);
        // enable flat catalog on the fly
        $flatProcess->setStatus($flatProcessStatus);
        return $productCollection;
    }

    /**
     * Get total available products
     *
     * @return integer
     **/
    public function getTotalProduct()
    {
        // disable flat catalog on the fly
        $flatProcess = Mage::helper('catalog/product_flat')->getProcess();
        $flatProcessStatus = $flatProcess->getStatus();
        $flatProcess->setStatus(Mage_Index_Model_Process::STATUS_RUNNING);
        // search product to export
        $productCollection = Mage::getModel('catalog/product')
            ->getCollection()
            ->setStoreId($this->_storeId)
            ->addStoreFilter($this->_storeId)
            ->joinField(
                'store_id',
                Mage::getConfig()->getTablePrefix() . 'catalog_category_product_index',
                'store_id',
                'product_id=entity_id',
                '{{table}}.store_id = ' . $this->_storeId,
                'left'
            )
            ->addAttributeToFilter('type_id', array('nlike' => 'bundle'));
        // enable flat catalog on the fly
        $flatProcess->setStatus($flatProcessStatus);
        $productCollection->getSelect()->distinct(true)->group('entity_id');
        $products = $productCollection->getData();
        return count($products);
    }

    /**
     * Get total exported products
     *
     * @return integer
     **/
    public function getTotalExportProduct()
    {
        $productCollection = $this->_getQuery();
        $productCollection->getSelect()->distinct(true)->group('entity_id');
        $products = $productCollection->getData();
        return count($products);
    }

    /**
     * Filter out of stock product
     *
     * @param boolean $outOfStock get out of stock products
     *
     * @return string
     **/
    protected function _getOutOfStockSQL($outOfStock = false)
    {
        // filter product without stock
        if (!$outOfStock) {
            $config = (int)Mage::getStoreConfigFlag(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_MANAGE_STOCK);
            $sql = '({{table}}.`is_in_stock` = 1) '
                . ' OR IF({{table}}.`use_config_manage_stock` = 1, ' . $config . ', {{table}}.`manage_stock`) = 0';
            unset($config);
            return $sql;
        }
    }

    /**
     * File generation
     *
     * @param array $data product data
     * @return boolean
     */
    protected function _write($data)
    {
        if (!$this->_stream) {
            if (!$this->_file && !$this->_initFile()) {
                return false;
            }
            $this->_file->streamLock();
            $this->_file->streamWrite($data);
            $this->_file->streamUnlock();
        } else {
            print_r($data);
            flush();
        }
        return true;
    }

    /**
     * Create File for export
     *
     * @return boolean
     */
    protected function _initFile()
    {
        if (!$this->_createDirectory()) {
            return false;
        }
        try {
            $this->_fileTimeStamp = time();
            $this->_file = new Varien_Io_File;
            $this->_file->cd($this->_config['directory_path']);
            $this->_file->streamOpen($this->_fileName . '.' . $this->_fileTimeStamp . '.' . $this->_fileFormat, 'w+');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create Directory for export
     *
     * @return boolean
     */
    protected function _createDirectory()
    {
        try {
            $file = new Varien_Io_File;
            $file->checkAndCreateFolder($this->_config['directory_path']);
        } catch (Exception $e) {
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_EXPORT,
                $this->_helper->__(
                    'log.export.error_folder_not_created',
                    array('folder_path' => $this->_config['directory_path'])
                ),
                $this->_logOutput
            );
            return false;
        }
        return true;
    }

    /**
     * Is Feed Already Launch
     *
     * @throws Exception
     *
     * @return boolean
     */
    protected function _isAlreadyLaunch()
    {
        $directory = $this->_config['directory_path'];
        if (!$this->_createDirectory()) {
            return false;
        }
        try {
            $listFiles = array_diff(scandir($directory), array('..', '.'));
        } catch (Exception $e) {
            $this->_helper->log(
                Lengow_Connector_Helper_Data::CODE_EXPORT,
                $this->_helper->__(
                    'log.export.error_folder_not_writable',
                    array('folder_path' => $this->_config['directory_path'])
                ),
                $this->_logOutput
            );
            return false;
        }
        $coreDate = Mage::getModel('core/date');
        foreach ($listFiles as $file) {
            if (preg_match('/^' . $this->_fileName . '\.[\d]{10}/', $file)) {
                $fileModified = $coreDate->gmtDate(
                    Lengow_Connector_Helper_Data::DATE_FULL,
                    filemtime($directory . $file)
                );
                $fileModifiedDatetime = new DateTime($fileModified);
                $fileModifiedDatetime->add(new DateInterval('P10D'));
                $fileModifiedDateDay = $fileModifiedDatetime->format(Lengow_Connector_Helper_Data::DATE_DAY);
                if ($coreDate->gmtDate(Lengow_Connector_Helper_Data::DATE_DAY) > $fileModifiedDateDay) {
                    unlink($directory . $file);
                }
                $fileModifiedDatetime = new DateTime($fileModified);
                $fileModifiedDatetime->add(new DateInterval('PT20S'));
                $fileModifiedDateFull = $fileModifiedDatetime->format(Lengow_Connector_Helper_Data::DATE_FULL);
                if ($coreDate->gmtDate(Lengow_Connector_Helper_Data::DATE_FULL) < $fileModifiedDateFull) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Copies the file to the correct folder
     */
    protected function _copyFile()
    {
        $filePath = $this->_config['directory_path'];
        copy(
            $filePath . $this->_fileName . '.' . $this->_fileTimeStamp . '.' . $this->_fileFormat,
            $filePath . $this->_fileName . '.' . $this->_fileFormat
        );
        unlink($filePath . $this->_fileName . '.' . $this->_fileTimeStamp . '.' . $this->_fileFormat);
    }

    /**
     * get microtime float
     */
    protected function _microtimeFloat()
    {
        list($usec, $sec) = explode(' ', microtime());
        return ((float) $usec + (float) $sec);
    }

    /**
     * Get all export available parameters
     *
     * @return string
     */
    public function getExportParams()
    {
        $params = array();
        $availableStores = array();
        $availableCodes = array();
        $availableCurrencies = array();
        $availableLanguages = array();
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $availableStores[] = $store->getId();
                    $availableCodes[] = $store->getCode();
                    $currencyCodes = $store->getAvailableCurrencyCodes();
                    foreach ($currencyCodes as $currencyCode) {
                        if (!in_array($currencyCode, $availableCurrencies, true)) {
                            $availableCurrencies[] = $currencyCode;
                        }
                    }
                    $storeLanguage = Mage::getStoreConfig('general/locale/code', $store->getId());
                    if (!in_array($storeLanguage, $availableLanguages, true)) {
                        $availableLanguages[] = $storeLanguage;
                    }
                }
            }
        }
        foreach ($this->_exportParams as $param) {
            switch ($param) {
                case self::PARAM_MODE:
                    $authorizedValue = array('size', 'total');
                    $type = 'string';
                    $example = 'size';
                    break;
                case self::PARAM_FORMAT:
                    $authorizedValue = $this->_availableFormats;
                    $type = 'string';
                    $example = 'csv';
                    break;
                case self::PARAM_STORE:
                    $authorizedValue = $availableStores;
                    $type = 'integer';
                    $example = 1;
                    break;
                case self::PARAM_CODE:
                    $authorizedValue = $availableCodes;
                    $type = 'string';
                    $example = 'french';
                    break;
                case self::PARAM_CURRENCY:
                    $authorizedValue = $availableCurrencies;
                    $type = 'string';
                    $example = 'EUR';
                    break;
                case self::PARAM_LANGUAGE:
                    $authorizedValue = $availableLanguages;
                    $type = 'string';
                    $example = 'fr_FR';
                    break;
                case self::PARAM_OFFSET:
                case self::PARAM_LIMIT:
                    $authorizedValue = 'all integers';
                    $type = 'integer';
                    $example = 100;
                    break;
                case self::PARAM_PRODUCT_IDS:
                    $authorizedValue = 'all integers';
                    $type = 'string';
                    $example = '101,108,215';
                    break;
                case self::PARAM_PRODUCT_TYPES:
                    $authorizedValue = $this->_availableProductTypes;
                    $type = 'string';
                    $example = 'configurable,simple,grouped';
                    break;
                default:
                    $authorizedValue = array(0, 1);
                    $type = 'integer';
                    $example = 1;
                    break;
            }
            $params[$param] = array(
                'authorized_values' => $authorizedValue,
                'type' => $type,
                'example' => $example,
            );
        }
        return Mage::helper('core')->jsonEncode($params);
    }
}
