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
    /**
     * @var string manual export type
     */
    const TYPE_MANUAL = 'manual';

    /**
     * @var string cron export type
     */
    const TYPE_CRON = 'cron';

    /**
     * @var string Magento cron export type
     */
    const TYPE_MAGENTO_CRON = 'magento cron';

    /**
     * @var array all available params for export
     */
    protected $_exportParams = array(
        'mode',
        'format',
        'stream',
        'offset',
        'limit',
        'selection',
        'out_of_stock',
        'product_ids',
        'product_types',
        'product_status',
        'store',
        'code',
        'currency',
        'locale',
        'legacy_fields',
        'log_output',
        'update_export_date',
        'get_params',
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
     * @var Varien_Io_File Magento varien io file instance
     */
    protected $_file;

    /**
     * @var string filename of output file
     */
    protected $_fileName = 'lengow_feed';

    /**
     * @var integer|null timestamp of output file
     */
    protected $_fileTimeStamp = null;

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
     * string  mode           Export mode => size: display only exported products, total: display all products
     * string  format         Export Format (csv|yaml|xml|json)
     * string  types          Type(s) of product
     * string  product_type   Type of export (manual, cron or magento cron)
     * string  product_status Status of product to export
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
        $storeId = isset($params['store_id']) ? (int)$params['store_id'] : false;
        $this->_store = Mage::app()->getStore($storeId);
        $this->_storeId = $this->_store->getId();
        // get format (csv by default)
        $format = isset($params['format']) ? $params['format'] : null;
        if ($format === null || !in_array($format, $this->_availableFormats)) {
            $this->_fileFormat = 'csv';
        } else {
            $this->_fileFormat = $format;
        }
        // get stream export or export in a file
        $stream = isset($params['stream']) ? (bool)$params['stream'] : null;
        if ($stream === null) {
            $this->_stream = $this->_configHelper->get('file_enable', $this->_storeId) ? false : true;
        } else {
            $this->_stream = $stream;
        }
        // get legacy fields or new fields
        $this->_legacy = isset($params['legacy_fields']) ? (bool)$params['legacy_fields'] : null;
        // update last export date or not
        $this->_updateExportDate = isset($params['update_export_date']) ? (bool)$params['update_export_date'] : true;
        // see logs or not (only when stream = 0)
        if ($this->_stream) {
            $this->_logOutput = false;
        } else {
            $this->_logOutput = isset($params['log_output']) ? (bool)$params['log_output'] : true;
        }
        // get export type
        $this->_typeExport = (isset($params['type']) ? $params['type'] : false);
        if (!$this->_typeExport) {
            $this->_typeExport = $this->_updateExportDate ? self::TYPE_CRON : self::TYPE_MANUAL;
        }
        // get configuration params
        $this->_config['product_types'] = isset($params['product_types'])
            ? $params['product_types']
            : $this->_configHelper->get('product_type', $this->_storeId);
        $this->_config['product_status'] = isset($params['product_status'])
            ? (string)$params['product_status']
            : (string)$this->_configHelper->get('product_status', $this->_storeId);
        $this->_config['out_of_stock'] = isset($params['out_of_stock'])
            ? (bool)$params['out_of_stock']
            : $this->_configHelper->get('out_stock', $this->_storeId);
        $this->_config['selection'] = isset($params['selection'])
            ? (bool)$params['selection']
            : $this->_configHelper->get('selection_enable', $this->_storeId);
        $this->_config['offset'] = isset($params['offset']) ? (int)$params['offset'] : '';
        $this->_config['limit'] = isset($params['limit']) ? (int)$params['limit'] : '';
        $this->_config['product_ids'] = isset($params['product_ids']) ? $params['product_ids'] : '';
        $this->_config['directory_path'] = Mage::getBaseDir('media') . DS . 'lengow' . DS . $this->_store->getCode() . DS;
        $this->setCurrentCurrencyCode(
            isset($params['currency']) ? $params['currency'] : Mage::app()->getStore($storeId)->getCurrentCurrencyCode()
        );
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
        if ($this->_config['limit']) {
            if ($this->_config['offset']) {
                $productCollection->getSelect()->limit($this->_config['limit'], $this->_config['offset']);
            } else {
                $productCollection->getSelect()->limit($this->_config['limit']);
            }
        }
        // ids product
        if ($this->_config['product_ids']) {
            $productIds = explode(',', $this->_config['product_ids']);
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
        $moduloExport = (int)($totalProduct / 10);
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
                    $parentInstance = $this->_getParentEntity((int)$parents[0]);
                    // exclude if parent is disabled
                    if ($parentInstance
                        && (int)$parentInstance->getStatus() === Mage_Catalog_Model_Product_Status::STATUS_DISABLED
                        && $this->_config['product_status'] == (string)Mage_Catalog_Model_Product_Status::STATUS_ENABLED
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
            $datas['quantity'] = (int)$qty->getQty();
            // we don't send qty ordered (old settings : without_product_ordering)
            $datas['quantity'] = $datas['quantity'] - (integer)$qty->getQtyOrdered();
            if ($product->getTypeId() === 'grouped') {
                $datas['quantity'] = (int)$qtyTemp;
            }
            $datas['active'] = (int)$product->getStatus() === Mage_Catalog_Model_Product_Status::STATUS_DISABLED
                ? 'Disabled'
                : 'Enabled';
            $datas = array_merge(
                $datas,
                $product->getCategories($product, $parentInstance, $this->_storeId, $this->_cacheCategory)
            );
            $datas = array_merge($datas, $product->getPrices($product, $this->_storeId));
            $datas = array_merge($datas, $product->getShippingInfo($product, $this->_storeId));
            // merge between children and parent images
            if ($this->_configHelper->get('parent_image', $this->_storeId)
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
            if ((int)$product->getVisibility() === Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE
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

            // Dispatch an event so other modules can update $datas
            $datasObject = new Varien_Object();
            $datasObject->setData($datas);
            Mage::dispatchEvent('lengow_export_products_datas', ['datas' => $datasObject]);
            $datas = $datasObject->getData();

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
                    if (!in_array($field, $this->_excludes) && !isset($productDatas[$field]) && $field !== '') {
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
            $this->_configHelper->set('last_export', Mage::getModel('core/date')->gmtTimestamp(), $this->_storeId);
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
        $productTypes = explode(',', $this->_config['product_types']);
        $productStatus = $this->_config['product_status'];
        $outOfStock = $this->_config['out_of_stock'];
        $selection = $this->_config['selection'];
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
        // filter status
        if ($productStatus === (string)Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
            $productCollection->addAttributeToFilter(
                'status',
                array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
            );
        } else {
            if ($productStatus === (string)Mage_Catalog_Model_Product_Status::STATUS_DISABLED) {
                $productCollection->addAttributeToFilter(
                    'status',
                    array('eq' => Mage_Catalog_Model_Product_Status::STATUS_DISABLED)
                );
            }
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
    public function getTotalExportedProduct()
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
     * @param array $data product datas
     * @return boolean
     */
    protected function _write($data)
    {
        if (!$this->_stream) {
            if (!$this->_file) {
                if (!$this->_initFile()) {
                    return false;
                }
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
                $fileModified = $coreDate->gmtDate('Y-m-d H:i:s', filemtime($directory . $file));
                $fileModifiedDatetime = new DateTime($fileModified);
                $fileModifiedDatetime->add(new DateInterval('P10D'));
                if ($coreDate->gmtDate('Y-m-d') > $fileModifiedDatetime->format('Y-m-d')) {
                    unlink($directory . $file);
                }
                $fileModifiedDatetime = new DateTime($fileModified);
                $fileModifiedDatetime->add(new DateInterval('PT20S'));
                if ($coreDate->gmtDate('Y-m-d H:i:s') < $fileModifiedDatetime->format('Y-m-d H:i:s')) {
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
        return ((float)$usec + (float)$sec);
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
                        if (!in_array($currencyCode, $availableCurrencies)) {
                            $availableCurrencies[] = $currencyCode;
                        }
                    }
                    $storeLanguage = Mage::getStoreConfig('general/locale/code', $store->getId());
                    if (!in_array($storeLanguage, $availableLanguages)) {
                        $availableLanguages[] = $storeLanguage;
                    }
                }
            }
        }
        foreach ($this->_exportParams as $param) {
            switch ($param) {
                case 'mode':
                    $authorizedValue = array('size', 'total');
                    $type = 'string';
                    $example = 'size';
                    break;
                case 'format':
                    $authorizedValue = $this->_availableFormats;
                    $type = 'string';
                    $example = 'csv';
                    break;
                case 'store':
                    $authorizedValue = $availableStores;
                    $type = 'integer';
                    $example = 1;
                    break;
                case 'code':
                    $authorizedValue = $availableCodes;
                    $type = 'string';
                    $example = 'french';
                    break;
                case 'currency':
                    $authorizedValue = $availableCurrencies;
                    $type = 'string';
                    $example = 'EUR';
                    break;
                case 'locale':
                    $authorizedValue = $availableLanguages;
                    $type = 'string';
                    $example = 'fr_FR';
                    break;
                case 'offset':
                case 'limit':
                    $authorizedValue = 'all integers';
                    $type = 'integer';
                    $example = 100;
                    break;
                case 'product_ids':
                    $authorizedValue = 'all integers';
                    $type = 'string';
                    $example = '101,108,215';
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
