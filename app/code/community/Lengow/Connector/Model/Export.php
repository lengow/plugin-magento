<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Model_Export extends Varien_Object
{
    /**
     * All available params for export
     */
    protected $_export_params = array(
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
        'get_params'
    );

    /**
     * Default fields
     */
    protected $_default_fields;

    /**
     * New fields for v3
     */
    protected $_new_fields = array(
        'id'                             => 'id',
        'sku'                            => 'sku',
        'name'                           => 'name',
        'child_name'                     => 'child_name',
        'quantity'                       => 'quantity',
        'status'                         => 'active',
        'category'                       => 'category_breadcrum',
        'url'                            => 'url',
        'price_excl_tax'                 => 'price_excl_tax',
        'price_incl_tax'                 => 'price_incl_tax',
        'price_before_discount_excl_tax' => 'price_before_discount_excl_tax',
        'price_before_discount_incl_tax' => 'price_before_discount_incl_tax',
        'discount_amount'                => 'discount_amount',
        'discount_percent'               => 'discount_percent',
        'discount_start_date'            => 'discount_start_date',
        'discount_end_date'              => 'discount_end_date',
        'shipping_method'                => 'shipping_method',
        'shipping_cost'                  => 'shipping_cost',
        'currency'                       => 'currency',
        'image_default'                  => 'image_default',
        'image_url_1'                    => 'image_url_1',
        'image_url_2'                    => 'image_url_2',
        'image_url_3'                    => 'image_url_3',
        'image_url_4'                    => 'image_url_4',
        'image_url_5'                    => 'image_url_5',
        'image_url_6'                    => 'image_url_6',
        'image_url_7'                    => 'image_url_7',
        'image_url_8'                    => 'image_url_8',
        'image_url_9'                    => 'image_url_9',
        'image_url_10'                   => 'image_url_10',
        'type'                           => 'type',
        'parent_id'                      => 'parent_id',
        'variation'                      => 'variation',
        'language'                       => 'language',
        'description'                    => 'description',
        'description_html'               => 'description_html',
        'description_short'              => 'description_short',
        'description_short_html'         => 'description_short_html',
    );

    /**
     * Legacy fields for retro-compatibility
     */
    protected $_legacy_fields = array(
        'sku'                   => 'sku',
        'product_id'            => 'id',
        'qty'                   => 'quantity',
        'status'                => 'active',
        'category-breadcrumb'   => 'category_breadcrum',
        'category'              => 'category',
        'category-url'          => 'category_url',
        'category-sub-1'        => 'category_sub_1',
        'category-url-sub-1'    => 'category_url_sub_1',
        'category-sub-2'        => 'category_sub_2',
        'category-url-sub-2'    => 'category_url_sub_2',
        'category-sub-3'        => 'category_sub_3',
        'category-url-sub-3'    => 'category_url_sub_3',
        'category-sub-4'        => 'category_sub_4',
        'category-url-sub-4'    => 'category_url_sub_4',
        'category-sub-5'        => 'category_sub_5',
        'category-url-sub-5'    => 'category_url_sub_5',
        'price-ttc'             => 'price_incl_tax',
        'price-before-discount' => 'price_before_discount_incl_tax',
        'discount-amount'       => 'discount_amount',
        'discount-percent'      => 'discount_percent',
        'start-date-discount'   => 'discount_start_date',
        'end-date-discount'     => 'discount_end_date',
        'shipping-name'         => 'shipping_method',
        'shipping-price'        => 'shipping_cost',
        'image-url-1'           => 'image_url_1',
        'image-url-2'           => 'image_url_2',
        'image-url-3'           => 'image_url_3',
        'image-url-4'           => 'image_url_4',
        'image-url-5'           => 'image_url_5',
        'image-url-6'           => 'image_url_6',
        'image-url-7'           => 'image_url_7',
        'image-url-8'           => 'image_url_8',
        'image-url-9'           => 'image_url_9',
        'image-url-10'          => 'image_url_10',
        'product-url'           => 'url',
        'name'                  => 'name',
        'description'           => 'description_html',
        'short_description'     => 'description_short_html',
        'parent_id'             => 'parent_id',
        'product_type'          => 'type',
        'product_variation'     => 'variation',
        'image_default'         => 'image_default',
        'child_name'            => 'child_name',
    );

    /**
     * Excludes attributes for export
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
     * Available formats for export
     */
    protected $_available_formats = array(
        'csv',
        'json',
        'yaml',
        'xml'
    );

    /**
     * boolean is the export output stream ?
     */
    protected $_stream;

    /**
     * mixed use legacy fields or not
     */
    protected $_legacy;

    /**
     * object
     */
    protected $_store;

    /**
     * integer Store Id
     */
    protected $_store_id;

    /**
     * object
     */
    protected $_file;

    /**
     * string filename of output file
     */
    protected $_fileName = 'lengow_feed';

    /**
     * integer timestamp of output file
     */
    protected $_fileTimeStamp = null;

    /**
     * array all config options
     */
    protected $_config = array();

    /**
     * object
     */
    protected $_config_helper;

    /**
     * string file format for export
     */
    protected $_fileFormat;

    /**
     * boolean update export date or not
     */
    protected $_update_export_date;

    /**
     * @var string type import (manual, cron or magento cron)
     */
    protected $_type_export;

    /**
     * @var boolean display log messages
     */
    protected $_log_output = false;

    /**
     * object
     */
    protected $_helper;

    /**
     * Export cache
     */
    protected $_cacheParentProducts = array();
    protected $_clear_parent_cache = 0;
    protected $_cacheCategory = array();

    /**
     * Construct the export manager
     * @param array params optional options
     * integer $store_id       ID of store
     * integer $limit          The number of product to be exported
     * integer $offset         From what product export
     * string  $mode           The mode of export = size : display only count of products to export
     * string  $format         Export Format (csv|yaml|xml|json)
     * string  $types          Type(s) of product
     * string  $product_type   Type of export (manual, cron or magento cron)
     * string  $product_status Status of product to export
     * string  $currency       Currency for export
     * string  $product_ids    Ids product to export
     * boolean $out_of_stock   Export product in stock and out stock (1) | Export Only in stock product (0)
     * boolean $selection      Export selected product (1) | Export all products (0)
     * boolean $stream         Display file when call script (1) | Save File (0)
     * boolean $legacy_fields  Export with legacy fields (1) | Export with new fields (0)
     * boolean $log_output     See logs (only when stream = 0) (1) | no logs (0)
     */
    public function __construct($params)
    {
        // Get helpers
        $this->_config_helper = Mage::helper('lengow_connector/config');
        $this->_helper = Mage::helper('lengow_connector/data');
        // Get store and store id
        $storeId = isset($params['store_id']) ? (int)$params['store_id'] : false;
        $this->_store = Mage::app()->getStore($storeId);
        $this->_store_id = $this->_store->getId();
        // Get format (csv by default)
        $format = isset($params['format']) ? $params['format'] : null;
        if (is_null($format) || !in_array($format, $this->_available_formats)) {
            $this->_fileFormat = 'csv';
        } else {
            $this->_fileFormat = $format;
        }
        // Get stream export or export in a file
        $stream = isset($params['stream']) ? (boolean)$params['stream'] : null;
        if (is_null($stream)) {
            $this->_stream = $this->_config_helper->get('file_enable', $this->_store_id) ? false : true;
        } else {
            $this->_stream = $stream;
        }
        // Get legacy fields or new fields
        $this->_legacy = isset($params['legacy_fields']) ? (boolean)$params['legacy_fields'] : null;
        // Update last export date or not
        $this->_update_export_date = isset($params['update_export_date']) ? (bool)$params['update_export_date'] : true;
        // See logs or not (only when stream = 0)
        if ($this->_stream) {
            $this->_log_output = false;
        } else {
            $this->_log_output = isset($params['log_output']) ? (bool)$params['log_output'] : true;
        }
        // Get export type
        $this->_type_export = (isset($params['type']) ? $params['type'] : false);
        if (!$this->_type_export) {
            $this->_type_export = $this->_update_export_date ? 'cron' : 'manual';
        }
        // Get configuration params
        $this->_config['mode'] = isset($params['mode']) ? $params['mode'] : '';
        $this->_config['get_params'] = isset($params['get_params']) ? (boolean)$params['get_params'] : false;
        $this->_config['product_types'] = isset($params['product_types'])
            ? $params['product_types']
            : $this->_config_helper->get('product_type', $this->_store_id);
        $this->_config['product_status'] = isset($params['product_status'])
            ? (string)$params['product_status']
            : (string)$this->_config_helper->get('product_status', $this->_store_id);
        $this->_config['out_of_stock'] = isset($params['out_of_stock'])
            ? (boolean)$params['out_of_stock']
            : $this->_config_helper->get('out_stock', $this->_store_id);
        $this->_config['selection'] = isset($params['selection'])
            ? (boolean)$params['selection']
            : $this->_config_helper->get('selection_enable', $this->_store_id);
        $this->_config['offset'] = isset($params['offset']) ? (int)$params['offset'] : '';
        $this->_config['limit'] = isset($params['limit']) ? (int)$params['limit'] : '';
        $this->_config['product_ids'] = isset($params['product_ids']) ? $params['product_ids'] : '';
        $this->_config['directory_path'] = Mage::getBaseDir('media').DS.'lengow'.DS.$this->_store->getCode().DS;
        $this->setOriginalCurrency(isset($params['currency'])
            ? $params['currency']
            : Mage::app()->getStore($storeId)->getCurrentCurrencyCode()
        );
    }

    /**
     * Execute export
     */
    public function exec()
    {
        // get params option
        if ($this->_config['get_params']) {
            echo $this->getExportParams();
            exit();
        }
        // start chrono
        $time_start = $this->_microtimeFloat();
        // clean logs > 20 days
        $this->_helper->cleanLog();
        //check if export is already launch
        if ($this->_isAlreadyLaunch()) {
            $this->_helper->log('Export', $this->_helper->__('log.export.feed_already_launch'), $this->_log_output);
            exit();
        }
        // Get products list to export
        $productCollection = $this->_getQuery();
        $tempProductCollection = $productCollection;
        $tempProductCollection->getSelect()->columns('COUNT(DISTINCT e.entity_id) As total');
        // Get total expoted product or total product
        if ($this->_config['mode'] == 'size') {
            echo $tempProductCollection->getFirstItem()->getTotal();
            exit();
        } elseif ($this->_config['mode'] == 'total') {
            echo $this->getTotalProduct();
            exit();
        }
        // Limit & Offset
        if ($this->_config['limit']) {
            if ($this->_config['offset']) {
                $productCollection->getSelect()->limit($this->_config['limit'], $this->_config['offset']);
            } else {
                $productCollection->getSelect()->limit($this->_config['limit']);
            }
        }
        // Ids product
        if ($this->_config['product_ids']) {
            $productIds = explode(',', $this->_config['product_ids']);
            $productCollection->addAttributeToFilter('entity_id', array('in' => $productIds));
        }
        $this->_helper->log(
            'Export',
            $this->_helper->__('log.export.start', array('type' => $this->_type_export)),
            $this->_log_output
        );
        $this->_helper->log(
            'Export',
            $this->_helper->__(
                'log.export.start_for_store',
                array(
                    'store_name' => $this->_store->getName(),
                    'store_id'   => $this->_store_id
                )
            ),
            $this->_log_output
        );
        // set legacy fields option
        $this->_setLegacyFields();
        // Gestion des attributs à exporter
        $attributes_to_export = $this->_config_helper->getSelectedAttributes($this->_store_id);
        $this->_attrs = array();
        // set feed format
        $feed = Mage::getModel('Lengow_Connector_Model_Export_Feed_'.ucfirst($this->_fileFormat));
        $first = true;
        $last = false;
        $pi = 1;
        $productCollection->getSelect()->distinct(true)->group('entity_id');
        // Get all product ids
        $products = $productCollection->getData();
        $total_product = count($products);
        $this->_helper->log(
            'Export',
            $this->_helper->__('log.export.nb_product_found', array('nb_product' => $total_product)),
            $this->_log_output
        );
        // modulo for export counter
        $modulo_export = (int)($total_product / 10);
        $modulo_export = $modulo_export < 50 ? 50 : $modulo_export;
        // Product counter
        $count_simple = 0;
        $count_simple_disabled = 0;
        $count_configurable = 0;
        $count_grouped = 0;
        $count_virtual = 0;
        $count_downloadable = 0;
        // Generate data
        foreach ($products as $p) {
            $datas = array();
            $parent = false;
            $pi++;
            if ($total_product < $pi) {
                $last = true;
            }
            $product = Mage::getModel('lengow/export_catalog_product')
                ->setStoreId($this->_store_id)
                ->setOriginalCurrency($this->getOriginalCurrency())
                ->setCurrentCurrencyCode($this->getCurrentCurrencyCode())
                ->load($p['entity_id']);
            $data = $product->getData();
            // Load first parent if exist
            $parents = null;
            $parent_instance = null;
            $configurable_instance = null;
            $parent_id = null;
            $product_type = 'simple';
            $variation_name = '';
            // Configurable products
            if ($product->getTypeId() == 'configurable') {
                $count_configurable++;
                $product_type = 'parent';
                $product_temp = $product;
                $variations = $product_temp
                    ->setOriginalCurrency($this->getOriginalCurrency())
                    ->setCurrentCurrencyCode($this->getCurrentCurrencyCode())
                    ->setStoreId($this->_store_id)
                    ->getTypeInstance(true)
                    ->getConfigurableAttributesAsArray($product);
                if ($variations) {
                    foreach ($variations as $variation) {
                        $variation_name .= $variation['frontend_label'] . ',';
                    }
                    $variation_name = rtrim($variation_name, ',');
                }
            }
            // Virtual product
            if ($product->getTypeId() == 'virtual') {
                $count_virtual++;
                $product_type = 'virtual';
            }
            // Downloadable products
            if ($product->getTypeId() == 'downloadable') {
                $count_downloadable++;
                $product_type = 'downloadable';
            }
            // Grouped products
            if ($product->getTypeId() == 'grouped') {
                $count_grouped++;
                $product_type = 'grouped';
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
                    $product_temporary = Mage::getModel('catalog/product')
                        ->setOriginalCurrency($this->getOriginalCurrency())
                        ->setCurrentCurrencyCode($this->getCurrentCurrencyCode())
                        ->setStoreId($this->_store_id)
                        ->load($childrenId);
                    $qtys[] = $product_temporary->getData('stock_item')->getQty();
                    unset($product_temporary);
                }
                $qty_temp = min($qtys) > 0 ? min($qtys) : 0;
            }
            // Simple Products
            if ($product->getTypeId() == 'simple') {
                $count_simple++;
                $parents = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($p['entity_id']);
                if (!empty($parents)) {
                    $parent_instance = $this->_getParentEntity((int)$parents[0]);
                    // Exclude if parent is disabled
                    if ($parent_instance
                        && $parent_instance->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_DISABLED
                    ) {
                        $count_simple_disabled++;
                        if (method_exists($product, 'clearInstance')) {
                            $product->clearInstance();
                            if ($parent != null) {
                                $parent->clearInstance();
                            }
                            if ($parent_instance != null) {
                                $parent_instance->clearInstance();
                            }
                        }
                        unset($datas);
                        continue;
                    }
                    if ($parent_instance
                        && $parent_instance->getId()
                        && $parent_instance->getTypeId() == 'configurable'
                    ) {
                        $parent_id = $parent_instance->getId();
                        $variations = $parent_instance->getTypeInstance(true)
                            ->getConfigurableAttributesAsArray($parent_instance);
                        if ($variations) {
                            foreach ($variations as $variation) {
                                $variation_name .= $variation['frontend_label'].',';
                            }
                            $variation_name = rtrim($variation_name, ',');
                        }
                        $product_type = 'child';
                    }
                }
            }
            $parents = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild(
                $parent_id ? $parent_id : $p['entity_id']
            );
            if (!empty($parents)) {
                $temp_instance = Mage::getModel('catalog/product')
                    ->setOriginalCurrency($this->getOriginalCurrency())
                    ->setCurrentCurrencyCode($this->getCurrentCurrencyCode())
                    ->setStoreId($this->_store_id)
                    ->getCollection()
                    ->addAttributeToFilter('type_id', 'grouped')
                    ->addAttributeToFilter('entity_id', array('in' => $parents))
                    ->getFirstItem();
                $parent_instance = $this->_getParentEntity($temp_instance->getId());
            }
            $qty = $product->getData('stock_item');
            // Default data
            $datas['sku'] = $product->getSku();
            $datas['id'] = $product->getId();
            $datas['quantity'] = (integer)$qty->getQty();
            //we dont send qty ordered (old settings : without_product_ordering)
            $datas['quantity'] = $datas['quantity'] - (integer)$qty->getQtyOrdered();
            if ($product->getTypeId() == 'grouped') {
                $datas['quantity'] = (integer)$qty_temp;
            }
            $datas['active'] = $product->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_DISABLED
                ? 'Disabled'
                : 'Enabled';
            $datas = array_merge(
                $datas,
                $product->getCategories($product, $parent_instance, $this->_store_id, $this->_cacheCategory)
            );
            $datas = array_merge(
                $datas,
                $product->getPrices($product, $this->_store_id, $configurable_instance)
            );
            $datas = array_merge($datas, $product->getShippingInfo($product, $this->_store_id));
            // Images, gestion de la fusion parent / enfant
            if ($this->_config_helper->get('parent_image', $this->_store_id) &&
                isset($parent_instance) && $parent_instance !== false) {
                $datas = array_merge(
                    $datas,
                    $product->getImages($data['media_gallery']['images'], $parent_instance->getData('media_gallery'))
                );
            } else {
                $datas = array_merge($datas, $product->getImages($data['media_gallery']['images']));
            }
            if ($product->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE
                && isset($parent_instance)
            ) {
                $datas['url'] = $parent_instance->getUrlInStore()
                    ? $parent_instance->getUrlInStore()
                    : $parent_instance->getProductUrl();
                $datas['name'] = $this->_helper->cleanData($parent_instance->getName());
                $datas['description'] = $this->_helper->cleanData($parent_instance->getDescription(), false);
                $datas['description_html'] = $this->_helper->cleanData($parent_instance->getDescription());
                $datas['description_short'] = $this->_helper->cleanData($parent_instance->getShortDescription(), false);
                $datas['description_short_html'] = $this->_helper->cleanData($parent_instance->getShortDescription());
            } else {
                $datas['url'] = $product->getUrlInStore() ? $product->getUrlInStore() : $product->getProductUrl();
                $datas['name'] = $this->_helper->cleanData($product->getName());
                $datas['description'] = $this->_helper->cleanData($product->getDescription(), false);
                $datas['description_html'] = $this->_helper->cleanData($product->getDescription());
                $datas['description_short'] = $this->_helper->cleanData($product->getShortDescription(), false);
                $datas['description_short_html'] = $this->_helper->cleanData($product->getShortDescription());
            }
            $datas['parent_id'] = $parent_id;
            // Product variation
            $datas['type'] = $product_type;
            $datas['variation'] = $variation_name;
            $datas['image_default'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)
                .'catalog/product'.$product->getImage();
            $datas['child_name'] = $this->_helper->cleanData($product->getName());
            $datas['language'] = Mage::app()->getLocale()->getLocaleCode();
            // get correct feed
            $product_datas = array();
            foreach ($this->_default_fields as $key => $value) {
                $product_datas[$key] = $datas[$value];
            }
            unset($datas);
            // Selected attributes to export with Frond End value of current store
            if (!empty($attributes_to_export)) {
                foreach ($attributes_to_export as $field => $attr) {
                    if (!in_array($field, $this->_excludes) && !isset($product_datas[$field]) && $field != '') {
                        if ($product->getData($field) === null) {
                            $product_datas[$attr] = '';
                        } else {
                            if (is_array($product->getData($field))) {
                                $product_datas[$attr] = implode(',', $product->getData($field));
                            } else {
                                $product_datas[$attr] = $this->_helper->cleanData(
                                    $product->getResource()->getAttribute($field)->getFrontend()->getValue($product)
                                );
                            }
                        }
                    }
                }
            }
            // Get the maximum of character for yaml format
            $max_character = 0;
            foreach ($product_datas as $key => $value) {
                if (strlen($key) > $max_character) {
                    $max_character = strlen($key);
                }
            }
            // Get header of feed
            if ($first) {
                $fields_header = array();
                foreach ($product_datas as $name => $value) {
                    $fields_header[] = $name;
                }
                // Get content type if streamed feed
                if ($this->_stream) {
                    header('Content-Type: '.$feed->getContentType().'; charset=UTF-8');
                    if ($this->_fileFormat == 'csv') {
                        header('Content-Disposition: attachment; filename=feed.csv');
                    }
                }
                $feed->setFields($fields_header);
                $this->_write($feed->makeHeader());
                $first = false;
            }
            $this->_write(
                $feed->makeData(
                    $product_datas,
                    array(
                        'last'          => $last,
                        'max_character' => $max_character
                    )
                )
            );
            // Save 10 logs maximum in database
            if ($pi % $modulo_export == 0) {
                $this->_helper->log(
                    'Export',
                    $this->_helper->__('log.export.count_product', array('product_count' => $pi))
                );
            }
            if (!$this->_stream && $this->_log_output) {
                if ($pi % 50 == 0) {
                    $count_message = $this->_helper->__('log.export.count_product', array('product_count' => $pi));
                    echo '[Export] '.$count_message.'<br />';
                }
                flush();
            }
            if (method_exists($product, 'clearInstance')) {
                $product->clearInstance();
            }
            unset($product_datas);
        }
        $this->_write($feed->makeFooter());
        // Product counter
        $total_simple = $count_simple - $count_simple_disabled;
        $total = $count_configurable + $count_grouped + $count_downloadable + $count_virtual + $total_simple;
        $this->_helper->log(
            'Export',
            $this->_helper->__(
                'log.export.total_product_exported',
                array(
                    'nb_product'      => $total,
                    'nb_simple'       => $total_simple,
                    'nb_configurable' => $count_configurable,
                    'nb_grouped'      => $count_grouped,
                    'nb_virtual'      => $count_virtual,
                    'nb_downloadable' => $count_downloadable,
                )
            ),
            $this->_log_output
        );
        // Warning for simple product associated with configurable products disabled
        if ($count_simple_disabled > 0) {
            $this->_helper->log(
                'Export',
                $this->_helper->__(
                    'log.export.error_configurable_product_disabled',
                    array('nb_product' => $count_simple_disabled)
                ),
                $this->_log_output
            );
        }
        // Link generation
        if (!$this->_stream) {
            $this->_copyFile();
            $url_file = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)
                .'lengow'.DS.$this->_store->getCode().DS.$this->_fileName.'.'.$this->_fileFormat;
            $this->_helper->log(
                'Export',
                $this->_helper->__(
                    'log.export.generate_feed_available_here',
                    array(
                        'store_name' => $this->_store->getName(),
                        'store_id'   => $this->_store_id,
                        'feed_url'   => $url_file
                    )
                ),
                $this->_log_output
            );
        }
        // Update last export date
        if ($this->_update_export_date) {
            $this->_config_helper->set('last_export', Mage::getModel('core/date')->gmtTimestamp(), $this->_store_id);
        }
        $time_end = $this->_microtimeFloat();
        $time = $time_end - $time_start;
        $this->_helper->log(
            'Export',
            $this->_helper->__('log.export.memory_usage', array('memory' => round(memory_get_usage() / 1000000, 2))),
            $this->_log_output
        );
        $this->_helper->log(
            'Export',
            $this->_helper->__('log.export.execution_time', array('seconds' => round($time, 2))),
            $this->_log_output
        );
        $this->_helper->log(
            'Export',
            $this->_helper->__('log.export.end', array('type' => $this->_type_export)),
            $this->_log_output
        );
    }

    /**
     * Set or not legacy fields to export
     */
    protected function _setLegacyFields()
    {
        if (is_null($this->_legacy)) {
            $result = Mage::getModel('lengow/connector')->queryApi(
                'get',
                '/v3.0/subscriptions',
                $this->_store_id
            );
            if (isset($result->legacy)) {
                $this->_legacy = (bool)$result->legacy;
            } else {
                $this->_legacy = false;
            }
        }
        $this->_default_fields = $this->_legacy ? $this->_legacy_fields : $this->_new_fields ;
    }

    /**
     * Temporary store Parent Identity
     *
     * @param integer $parent_id Parent Entity Id
     *
     * @return object Catalog/product
     */
    protected function _getParentEntity($parent_id)
    {
        $this->_clear_parent_cache++;
        if (!isset($this->_cacheParentProducts[$parent_id])) {
            $parent = Mage::getModel('lengow/export_catalog_product')
                ->setStoreId($this->_store_id)
                ->setOriginalCurrency($this->getOriginalCurrency())
                ->setCurrentCurrencyCode($this->getCurrentCurrencyCode())
                ->load($parent_id);
            $this->_cacheParentProducts[$parent_id] = $parent;
        }
        if ($this->_clear_parent_cache > 300) {
            if (method_exists($this->_cacheParentProducts[0], 'clearInstance')) {
                $maxStoreParent = count($this->_cacheParentProducts);
                for ($i = 0; $i < $maxStoreParent; $i++) {
                    $this->_cacheParentProducts[0]->clearInstance();
                }
            }
            $this->_clear_parent_cache = 0;
            $this->_cacheParentProducts = null;
        }
        return $this->_cacheParentProducts[$parent_id];
    }

    /**
     * Get products collection for export
     *
     * @return array
     */
    public function _getQuery()
    {
        // Filter
        $product_types = explode(',', $this->_config['product_types']);
        $product_status = $this->_config['product_status'];
        $out_of_stock = $this->_config['out_of_stock'];
        $selection = $this->_config['selection'];
        // Search product to export
        $productCollection = Mage::getModel('catalog/product')
            ->getCollection()
            ->setStoreId($this->_store_id)
            ->addStoreFilter($this->_store_id)
            ->addAttributeToFilter('type_id', array('in' => $product_types))
            ->joinField(
                'store_id',
                Mage::getConfig()->getTablePrefix().'catalog_category_product_index',
                'store_id',
                'product_id=entity_id',
                '{{table}}.store_id = '.$this->_store_id,
                'left'
            );
        // Filter status
        if ($product_status === (string)Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
            $productCollection->addAttributeToFilter(
                'status',
                array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
            );
        } else {
            if ($product_status === (string)Mage_Catalog_Model_Product_Status::STATUS_DISABLED) {
                $productCollection->addAttributeToFilter(
                    'status',
                    array('eq' => Mage_Catalog_Model_Product_Status::STATUS_DISABLED)
                );
            }
        }
        // Export only selected products
        if ($selection) {
            $productCollection->addAttributeToFilter('lengow_product', 1);
        }
        $productCollection->joinTable(
            'cataloginventory/stock_item',
            'product_id=entity_id',
            array('qty' => 'qty', 'is_in_stock' => 'is_in_stock'),
            $this->_getOutOfStockSQL($out_of_stock),
            'inner'
        );
        // Filter to hide products
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($productCollection);
        return $productCollection;
    }

    /**
     * Get total available products
     *
     * @return string
     **/
    public function getTotalProduct()
    {
        // Search product to export
        $productCollection = Mage::getModel('catalog/product')
            ->getCollection()
            ->setStoreId($this->_store_id)
            ->addStoreFilter($this->_store_id)
            ->joinField(
                'store_id',
                Mage::getConfig()->getTablePrefix().'catalog_category_product_index',
                'store_id',
                'product_id=entity_id',
                '{{table}}.store_id = '.$this->_store_id,
                'left'
            )
            ->addAttributeToFilter('type_id', array('nlike' => 'bundle'));
        $productCollection = clone $productCollection;
        $productCollection->getSelect()->columns('COUNT(DISTINCT e.entity_id) As total');
        return $productCollection->getFirstItem()->getTotal();
    }

    /**
     * Get total exported products
     *
     * @return string
     **/
    public function getTotalExportedProduct()
    {
        $productCollection = $this->_getQuery();
        $productCollection->getSelect()->columns('COUNT(DISTINCT e.entity_id) As total');
        return $productCollection->getFirstItem()->getTotal();
    }

    /**
     * Filter out of stock product
     *
     * @param boolean $out_of_stock
     *
     * @return string
     **/
    protected function _getOutOfStockSQL($out_of_stock = false)
    {
        // Filter product without stock
        if (!$out_of_stock) {
            $config = (int)Mage::getStoreConfigFlag(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_MANAGE_STOCK);
            $sql = '({{table}}.`is_in_stock` = 1) '
                .' OR IF({{table}}.`use_config_manage_stock` = 1, '.$config.', {{table}}.`manage_stock`) = 0';
            unset($config);
            return $sql;
        }
    }

    /**
     * File generation
     *
     * @param array $data
     */
    protected function _write($data)
    {
        if ($this->_stream == false) {
            if (!$this->_file) {
                $this->_initFile();
            }
            $this->_file->streamLock();
            $this->_file->streamWrite($data);
            $this->_file->streamUnlock();
        } else {
            echo $data;
            flush();
        }
    }

    /**
     * Create File for export
     */
    protected function _initFile()
    {
        if (!$this->_createDirectory()) {
            exit();
        }
        $this->_fileTimeStamp = time();
        $this->_file = new Varien_Io_File;
        $this->_file->cd($this->_config['directory_path']);
        $this->_file->streamOpen($this->_fileName.'.'.$this->_fileTimeStamp.'.'.$this->_fileFormat, 'w+');
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
                'Export',
                $this->_helper->__(
                    'log.export.error_folder_not_created',
                    array('folder_path' => $this->_config['directory_path'])
                ),
                $this->_log_output
            );
            return false;
        }
        return true;
    }

    /**
     * Is Feed Already Launch
     *
     * @return boolean
     */
    protected function _isAlreadyLaunch()
    {
        $directory = $this->_config['directory_path'];
        if (!$this->_createDirectory()) {
            exit();
        }
        try {
            $listFiles = array_diff(scandir($directory), array('..', '.'));
        } catch (Exception $e) {
            $this->_helper->log(
                'Export',
                $this->_helper->__(
                    'log.export.error_folder_not_writable',
                    array('folder_path' => $this->_config['directory_path'])
                ),
                $this->_log_output
            );
            exit();
        }
        foreach ($listFiles as $file) {
            if (preg_match('/^' . $this->_fileName . '\.[\d]{10}/', $file)) {
                $fileModified = date('Y-m-d H:i:s', filemtime($directory . $file));
                $fileModifiedDatetime = new DateTime($fileModified);
                $fileModifiedDatetime->add(new DateInterval('P10D'));
                if (date('Y-m-d') > $fileModifiedDatetime->format('Y-m-d')) {
                    unlink($directory.$file);
                }
                $fileModifiedDatetime = new DateTime($fileModified);
                $fileModifiedDatetime->add(new DateInterval('PT20S'));
                if (date('Y-m-d H:i:s') < $fileModifiedDatetime->format('Y-m-d H:i:s')) {
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
        $file_path = $this->_config['directory_path'];
        copy(
            $file_path.$this->_fileName.'.'.$this->_fileTimeStamp.'.'.$this->_fileFormat,
            $file_path . $this->_fileName.'.'.$this->_fileFormat
        );
        unlink($file_path.$this->_fileName.'.'.$this->_fileTimeStamp.'.'.$this->_fileFormat);
    }

    /**
     * get microtime float
     */
    protected function _microtimeFloat()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    /**
     * get export Url
     */
    public function getExportUrl()
    {
        return Mage::getUrl('lengow/feed', array('store' => $this->_store_id));
    }

    /**
     * Get all export available parameters
     *
     * @return string
     */
    public function getExportParams()
    {
        $params = array();
        $available_stores = array();
        $available_codes = array();
        $available_currencies = array();
        $available_languages = array();
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $available_stores[] = $store->getId();
                    $available_codes[] = $store->getCode();
                    $currency_codes = $store->getAvailableCurrencyCodes();
                    foreach ($currency_codes as $currency_code) {
                        if (!in_array($currency_code, $available_currencies)) {
                            $available_currencies[] = $currency_code;
                        }
                    }
                    $store_language = Mage::getStoreConfig('general/locale/code', $store->getId());
                    if (!in_array($store_language, $available_languages)) {
                        $available_languages[] = $store_language;
                    }
                }
            }
        }
        foreach ($this->_export_params as $param) {
            switch ($param) {
                case 'mode':
                    $authorized_value = array('size', 'total');
                    $type             = 'string';
                    $example          = 'size';
                    break;
                case 'format':
                    $authorized_value = $this->_available_formats;
                    $type             = 'string';
                    $example          = 'csv';
                    break;
                case 'store':
                    $authorized_value = $available_stores;
                    $type             = 'integer';
                    $example          = 1;
                    break;
                case 'code':
                    $authorized_value = $available_codes;
                    $type             = 'string';
                    $example          = 'french';
                    break;
                case 'currency':
                    $authorized_value = $available_currencies;
                    $type             = 'string';
                    $example          = 'EUR';
                    break;
                case 'locale':
                    $authorized_value = $available_languages;
                    $type             = 'string';
                    $example          = 'fr_FR';
                    break;
                case 'offset':
                case 'limit':
                    $authorized_value = 'all integers';
                    $type             = 'integer';
                    $example          = 100;
                    break;
                case 'product_ids':
                    $authorized_value = 'all integers';
                    $type             = 'string';
                    $example          = '101,108,215';
                    break;
                default:
                    $authorized_value = array(0, 1);
                    $type             = 'integer';
                    $example          = 1;
                    break;
            }
            $params[$param] = array(
                'authorized_values' => $authorized_value,
                'type'              => $type,
                'example'           => $example
            );
        }
        
        return json_encode($params);
    }
}
