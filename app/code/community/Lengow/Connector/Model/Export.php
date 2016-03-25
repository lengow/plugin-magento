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
     * Default fields.
     */
    public static $DEFAULT_FIELDS = array(
        'sku' => 'sku',
        'entity_id' => 'product-id',
        'parent-id' => 'parent-id',
        'qty' => 'qty',
        'name' => 'name',
        'description' => 'description',
        'short_description' => 'short_description',
        'price-ttc' => 'price-ttc',
        'shipping-name' => 'shipping-name',
        'image-url-1' => 'image-url-1',
        'product-url' => 'product-url'
    );

    //(boolean) is the export output stream ?
    protected $_stream;
    //(store) store object
    protected $_store;
    protected $_storeId;
    //(file) output file object
    protected $_file;
    //(string) filename of output file
    protected $_fileName = 'lengow_feed';
    //(int) timestamp of output file
    protected $_fileTimeStamp = null;


    protected $_config = array();
    protected $_config_helper;
    protected $_fileFormat;
    protected $_helper;
    protected $cacheParentProducts = array();
    protected $_clear_parent_cache = 0;
    protected $cacheCategory = array();
    protected $_excludes = array(
        'media_gallery',
        'tier_price',
        'short_description',
        'description',
        'quantity'
    );


    /**
     * Construct generator
     * Set models
     */
    public function __construct($params)
    {

        $this->_config_helper = Mage::helper('lengow_connector/config');

        $storeId = isset($params['store_id']) ? (int)$params['store_id'] : false;
        if ($storeId == 0) {
            throw new Exception('Cant export catalog without store');
        } else {
            $this->_store = Mage::app()->getStore($storeId);
            if (!$this->_store) {
                throw new Exception('Cant load that store');
            }
            $this->_storeId = $this->_store->getId();
        }

        $this->_config['format'] = isset($params['format']) ? $params['format'] : 'csv';
        $this->_config['mode'] = isset($params['mode']) ? $params['mode'] : '';
        $this->_config['types'] = isset($params['types']) ?
            $params['types'] : $this->_config_helper->get('product_type', $this->_storeId);
        $this->_config['status'] = isset($params['status']) ?
            (string)$params['status'] : (string)$this->_config_helper->get('product_status', $this->_storeId);
        $this->_config['out_of_stock'] = isset($params['out_of_stock']) ?
            (boolean)$params['out_of_stock'] : $this->_config_helper->get('out_stock', $this->_storeId);
        $this->_config['selected_products'] = isset($params['selected_products']) ?
            (boolean)$params['selected_products'] : $this->_config_helper->get('selection_enable', $this->_storeId);

        $this->_config['offset'] = isset($params['offset']) ? (int)$params['offset'] : '';
        $this->_config['limit'] = isset($params['limit']) ? (int)$params['limit'] : '';
        $this->_config['product_ids'] = isset($params['product_ids']) ? $params['product_ids'] : '';
        $this->_config['debug'] = isset($params['debug']) ? (boolean)$params['debug'] : false;
        $this->_config['directory_path'] = Mage::getBaseDir('media').DS.'lengow'.DS.$this->_store->getCode().DS;

        $this->setOriginalCurrency(isset($params['currency']) ?
            $params['currency'] : Mage::app()->getStore($storeId)->getCurrentCurrencyCode());

        $stream = isset($params['stream']) ? (boolean)$params['stream'] : null;
        if (is_null($stream)) {
            $this->_stream = $this->_config_helper->get('file_enabled', $this->_storeId) ? false : true;
        } else {
            $this->_stream = $stream;
        }

        $this->_helper = Mage::helper('lengow_connector/data');
        $this->_fileFormat = $this->_config['format'];
    }

    /**
    * Make the feed
    *
    * @param integer $id_store ID of store
    * @param varchar $mode The mode of export
    *       size : display only count of products to export
    *       full : export simple product + configured product
    *       xxx,yyy : export xxx type product + yyy type product
    * @param varchar $format Format of export
    * @param varchar $types Type(s) of product
    * @param varchar $status Status of product to export
    * @param boolean $out_of_stock Export product out of stock
    * @param boolean $selected_products Export selected product
    * @param boolean $stream Export in file or not
    * @param integer $limit The number of product to be exported
    * @param integer $offset From what product export
    * @param array $ids_product Ids product to export
    *
    * @return Mage_Catalog_Model_Product
    */
    public function exec()
    {

        $time_start = $this->microtime_float();

        if ($this->_isAlreadyLaunch()) {
            Mage::helper('lengow_connector')->log('Export', 'Feed already launch');
            if (!$this->_stream) {
                echo date('Y-m-d h:i:s') . ' - FEED ALREADY LAUNCH<br />';
            }
            exit();
        }

        // Get products list to export
        $productCollection = $this->_getQuery();

        $tempProductCollection = clone $productCollection;
        $tempProductCollection->getSelect()->columns('COUNT(DISTINCT e.entity_id) As total');

        if ($this->_config['mode'] == 'size') {
            echo $tempProductCollection->getFirstItem()->getTotal();
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

        Mage::helper('lengow_connector')->log(
            'Export',
            Mage::helper('lengow_connector')->__('log.export.start_for_shop', array(
                'name_shop' => $this->_store->getName(),
                'id_shop' => $this->_storeId
            )),
            !$this->_stream
        );

        // Gestion des attributs à exporter
        $attributes_to_export = $this->_config_helper->getSelectedAttributes($this->_storeId);
        $this->_attrs = array();
        $feed = Mage::getModel('Lengow_Connector_Model_Export_Feed_' . ucfirst($this->_fileFormat));
        $first = true;
        $last = false;
        $pi = 1;

        $productCollection->getSelect()->distinct(true)->group('entity_id');
        $products = $productCollection->getData();
        $total_product = count($products);
        Mage::helper('lengow_connector')->log(
            'Export',
            Mage::helper('lengow_connector')->__('log.export.nb_product_found', array(
                'nb_product' => $total_product
            )),
            !$this->_stream
        );

        // Product counter
        $count_simple = 0;
        $count_simple_disabled = 0;
        $count_configurable = 0;
        $count_bundle = 0;
        $count_grouped = 0;
        $count_virtual = 0;
        // Generate data
        foreach ($products as $p) {
            $array_data = array();
            $parent = false;
            $pi++;
            if ($total_product < $pi) {
                $last = true;
            }
            $product = Mage::getModel('lengow/export_catalog_product')
                ->setStoreId($this->_storeId)
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
            if ($product->getTypeId() == 'configurable') {
                $count_configurable++;
                $product_type = 'parent';
                $product_temp = $product;
                $variations = $product_temp
                    ->setOriginalCurrency($this->getOriginalCurrency())
                    ->setCurrentCurrencyCode($this->getCurrentCurrencyCode())
                    ->setStoreId($this->_storeId)
                    ->getTypeInstance(true)
                    ->getConfigurableAttributesAsArray($product);
                if ($variations) {
                    foreach ($variations as $variation) {
                        $variation_name .= $variation['frontend_label'] . ',';
                    }
                    $variation_name = rtrim($variation_name, ',');
                }
            }
            if ($product->getTypeId() == 'virtual') {
                $count_virtual++;
                $product_type = 'virtual';
            }
            if ($product->getTypeId() == 'grouped' || $product->getTypeId() == 'bundle') {
                if ($product->getTypeId() == 'bundle') {
                    $count_bundle++;
                    $product_type = 'bundle';
                } else {
                    $count_grouped++;
                    $product_type = 'grouped';
                }
                // get quantity for bundle or grouped products
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
                        ->setStoreId($this->_storeId)
                        ->load($childrenId);
                    $qtys[] = $product_temporary->getData('stock_item')->getQty();
                    unset($product_temporary);
                }
                $qty_temp = min($qtys) > 0 ? min($qtys) : 0;
            }
            if ($product->getTypeId() == 'simple') {
                $count_simple++;
                $parents = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($p['entity_id']);
                if (!empty($parents)) {

                    $parent_instance = $this->_getParentEntity((int)$parents[0]);

                    // Exclude if parent is disabled
                    if ($parent_instance && $parent_instance->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_DISABLED) {
                        $count_simple_disabled++;
                        if (!$this->_stream) {
                            if ($pi % 20 == 0) {
                                echo date('Y-m-d h:i:s') . ' - Export ' . $pi . ' products<br />';
                            }
                            flush();
                        }
                        if (method_exists($product, 'clearInstance')) {
                            $product->clearInstance();
                            if ($parent != null) {
                                $parent->clearInstance();
                            }
                            if ($parent_instance != null) {
                                $parent_instance->clearInstance();
                            }
                        }

                        unset($array_data);
                        continue;
                    }
                    if ($parent_instance && $parent_instance->getId() && $parent_instance->getTypeId() == 'configurable') {
                        $parent_id = $parent_instance->getId();

                        $variations = $parent_instance->getTypeInstance(true)
                            ->getConfigurableAttributesAsArray($parent_instance);
                        if ($variations) {
                            foreach ($variations as $variation) {
                                $variation_name .= $variation['frontend_label'] . ',';
                            }
                            $variation_name = rtrim($variation_name, ',');
                        }
                        $product_type = 'child';
                    }
                }
            }
            $parents = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($parent_id ? $parent_id : $p['entity_id']);
            if (!empty($parents)) {
                $temp_instance = Mage::getModel('catalog/product')
                    ->setOriginalCurrency($this->getOriginalCurrency())
                    ->setCurrentCurrencyCode($this->getCurrentCurrencyCode())
                    ->setStoreId($this->_storeId)
                    ->getCollection()
                    ->addAttributeToFilter('type_id', 'grouped')
                    ->addAttributeToFilter('entity_id', array('in' => $parents))
                    ->getFirstItem();

                $parent_instance = $this->_getParentEntity($temp_instance->getId());
            }
            $qty = $product->getData('stock_item');
            // Default data
            $array_data['sku'] = $product->getSku();
            $array_data['product_id'] = $product->getId();
            $array_data['qty'] = (integer)$qty->getQty();
            //we dont send qty ordered (old settings : without_product_ordering)
            $array_data['qty'] = $array_data['qty'] - (integer)$qty->getQtyOrdered();

            if ($product->getTypeId() == 'grouped' || $product->getTypeId() == 'bundle') {
                $array_data['qty'] = (integer)$qty_temp;
            }
            $array_data['status'] = $product->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_DISABLED ?
                'Disabled' : 'Enabled';
            $array_data = array_merge(
                $array_data,
                $product->getCategories($product, $parent_instance, $this->_storeId, $this->cacheCategory)
            );
            $array_data = array_merge(
                $array_data,
                $product->getPrices($product, $configurable_instance, $this->_storeId)
            );
            $array_data = array_merge($array_data, $product->getShippingInfo($product, $this->_storeId));
            // Images, gestion de la fusion parent / enfant
            if ($this->_config_helper->get('parent_image', $this->_storeId) &&
                isset($parent_instance) && $parent_instance !== false) {
                $array_data = array_merge(
                    $array_data,
                    $product->getImages($data['media_gallery']['images'], $parent_instance->getData('media_gallery'))
                );
            } else {
                $array_data = array_merge($array_data, $product->getImages($data['media_gallery']['images']));
            }
            if ($product->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE && isset($parent_instance)) {
                $array_data['product-url'] = $parent_instance->getUrlInStore() ? $parent_instance->getUrlInStore() : $parent_instance->getProductUrl();
                $array_data['name'] = $this->_helper->cleanData($parent_instance->getName());
                $array_data['description'] = $this->_helper->cleanData($parent_instance->getDescription());
                $array_data['short_description'] = $this->_helper->cleanData($parent_instance->getShortDescription());
            } else {
                $array_data['product-url'] = $product->getUrlInStore() ? $product->getUrlInStore() : $product->getProductUrl();
                $array_data['name'] = $this->_helper->cleanData($product->getName());
                $array_data['description'] = $this->_helper->cleanData($product->getDescription());
                $array_data['short_description'] = $this->_helper->cleanData($product->getShortDescription());
            }
            $array_data['parent_id'] = $parent_id;
            // Product variation
            $array_data['product_type'] = $product_type;
            $array_data['product_variation'] = $variation_name;
            $array_data['image_default'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
            $array_data['child_name'] = $this->_helper->cleanData($product->getName());
            // Selected attributes to export with Frond End value of current shop
            if (!empty($attributes_to_export)) {
                foreach ($attributes_to_export as $field => $attr) {
                    if (!in_array($field, $this->_excludes) && !isset($array_data[$field])) {
                        if ($product->getData($field) === null) {
                            $array_data[$attr] = '';
                        } else {
                            if (is_array($product->getData($field))) {
                                $array_data[$attr] = implode(',', $product->getData($field));
                            } else {
                                $array_data[$attr] = $this->_helper->cleanData($product->getResource()->getAttribute($field)->getFrontend()->getValue($product));
                            }
                        }
                    }
                }
            }
            // Get header of feed
            if ($first) {
                $fields_header = array();
                foreach ($array_data as $name => $value) {
                    $fields_header[] = $name;
                }
                // Get content type if streamed feed
                if ($this->_stream) {
                    header('Content-Type: ' . $feed->getContentType() . '; charset=utf-8');
                }
                $feed->setFields($fields_header);
                $this->_write($feed->makeHeader());
                $first = false;
            }
            $this->_write($feed->makeData($array_data, array('last' => $last)));
            if (!$this->_stream) {
                if ($pi % 20 == 0) {
                    echo date('Y-m-d h:i:s') . ' - Export ' . $pi . ' products<br />';
                }
                flush();
            }
            // Fix Sébastien Ledan
            if (method_exists($product, 'clearInstance')) {
                $product->clearInstance();
            }
            unset($array_data);
        }
        $this->_write($feed->makeFooter());
        // Product counter and warning
        $total_simple = $count_simple - $count_simple_disabled;
        $total = $count_configurable + $count_grouped + $count_bundle + $count_virtual + $total_simple;
        $message_count = 'Export ' . $total . ' product' . ($total_product > 1 ? 's ' : '') . ' ('
            . $total_simple . ' simple product' . ($total_simple > 1 ? 's ' : '') . ', '
            . $count_configurable . ' configurable product' . ($count_configurable > 1 ? 's ' : '') . ', '
            . $count_bundle . ' bundle product' . ($count_bundle > 1 ? 's ' : '') . ', '
            . $count_grouped . ' grouped product' . ($count_grouped > 1 ? 's ' : '') . ', '
            . $count_virtual . ' virtual product' . ($count_virtual > 1 ? 's ' : '') . ')';
        Mage::helper('lengow_connector')->log($message_count);
        if ($count_simple_disabled > 1) {
            if ($count_simple_disabled == 1) {
                $message_warning = 'WARNING ! 1 simple product is associated with a disabled configurable product';
            } else {
                $message_warning = 'WARNING ! ' . $count_simple_disabled . ' simple products are associated with configurable products disabled';
            }
            Mage::helper('lengow_connector')->log($message_warning);
            if (!$this->_stream) {
                echo date('Y-m-d h:i:s') . ' - ' . $message_warning . '<br />';
                flush();
            }
        }
        if (!$this->_stream) {
            echo date('Y-m-d h:i:s') . ' - ' . $message_count . '<br />';
            flush();
            $this->_copyFile();
            $url_file = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'lengow'.DS.$this->_store->getCode().
                DS.$this->_fileName.'.'.$this->_fileFormat;
            echo Mage::helper('lengow_connector')->__('log.export.your_feed_available_here', array(
                'feed_url' => $url_file
            ));
            Mage::helper('lengow_connector')->log('Export',
                Mage::helper('lengow_connector')->__('log.export.your_feed_available_here', array(
                    'name_shop' => $this->_store->getName(),
                    'id_shop' => $this->_storeId,
                    'feed_url' => $url_file
                ))
            );
        }

        $time_end = $this->microtime_float();
        $time = $time_end - $time_start;
        if (!$this->_stream) {
            echo "<br/>".Mage::helper('lengow_connector')->__('log.export.memory_usage')
                ." : ". round(memory_get_usage() / 1000000,2)." Mb";
            echo "<br/>".Mage::helper('lengow_connector')->__('log.export.execution_time', array(
                    "seconds" => round($time,2)
                ))."<br/>";
        }
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
        if (!isset($this->cacheParentProducts[$parent_id])) {
            $parent = Mage::getModel('lengow/export_catalog_product')
                ->setStoreId($this->_storeId)
                ->setOriginalCurrency($this->getOriginalCurrency())
                ->setCurrentCurrencyCode($this->getCurrentCurrencyCode())
                ->load($parent_id);
            $this->cacheParentProducts[$parent_id] = $parent;
        }
        if ($this->_clear_parent_cache > 300) {
            if (method_exists($this->cacheParentProducts[0], 'clearInstance')) {
                $maxStoreParent = count($this->cacheParentProducts);
                for ($i = 0; $i < $maxStoreParent; $i++) {
                    $this->cacheParentProducts[0]->clearInstance();
                }
            }
            $this->_clear_parent_cache = 0;
            $this->cacheParentProducts = null;
        }
        return $this->cacheParentProducts[$parent_id];
    }

    /**
     * Get products collection for export
     *
     * @return array
     */
    public function _getQuery(){
        // Filter
        $_types = explode(',', $this->_config['types']);
        $status = $this->_config['status'];
        $out_of_stock = $this->_config['out_of_stock'];
        $selected_products = $this->_config['selected_products'];

        // Search product to export
        $productCollection = Mage::getModel('catalog/product')
            ->getCollection()
            ->setStoreId($this->_storeId)
            ->addStoreFilter($this->_storeId)
            ->addAttributeToFilter('type_id', array('in' => $_types))
            ->joinField('store_id', Mage::getConfig()->getTablePrefix() . 'catalog_category_product_index', 'store_id',
                'product_id=entity_id', '{{table}}.store_id = ' . $this->_storeId, 'left');
        // Filter status
        if ($status === (string)Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
            $productCollection->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED));
        } else {
            if ($status === (string)Mage_Catalog_Model_Product_Status::STATUS_DISABLED) {
                $productCollection->addAttributeToFilter('status',
                    array('eq' => Mage_Catalog_Model_Product_Status::STATUS_DISABLED));
            }
        }
        // Export only selected products
        if ($selected_products) {
            $productCollection->addAttributeToFilter('lengow_product', 1);
        }
        $productCollection->joinTable('cataloginventory/stock_item', 'product_id=entity_id',
            array('qty' => 'qty', 'is_in_stock' => 'is_in_stock'), $this->_getOutOfStockSQL($out_of_stock), 'inner');

        // Filter to hide products
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($productCollection);
        return $productCollection;
    }

    public function getTotalProduct()
    {
        // Search product to export
        $productCollection = Mage::getModel('catalog/product')
            ->getCollection()
            ->setStoreId($this->_storeId)
            ->addStoreFilter($this->_storeId)
            ->joinField('store_id', Mage::getConfig()->getTablePrefix() . 'catalog_category_product_index', 'store_id',
                'product_id=entity_id', '{{table}}.store_id = ' . $this->_storeId, 'left');
        $productCollection = clone $productCollection;
        $productCollection->getSelect()->columns('COUNT(DISTINCT e.entity_id) As total');
        return $productCollection->getFirstItem()->getTotal();
    }

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
                . ' OR IF({{table}}.`use_config_manage_stock` = 1, ' . $config . ', {{table}}.`manage_stock`) = 0';
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
        $this->_file->streamOpen($this->_fileName . '.' . $this->_fileTimeStamp . '.' . $this->_fileFormat, 'w+');
    }

    protected function _createDirectory()
    {
        try {
            $file = new Varien_Io_File;
            $file->checkAndCreateFolder($this->_config['directory_path']);
        } catch (Exception $e) {
            Mage::helper('lengow_connector')->log('can\'t create folder ' . $this->_config['directory_path'] . '');
            if ($this->_debug) {
                $this->_log('can\'t create folder ' . $this->_config['directory_path']);
            }
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
            Mage::helper('lengow_connector')->log('Can\'t access folder ' . $this->_config['directory_path']);
            if ($this->_debug) {
                $this->_log('Can\'t access folder ' . $this->_config['directory_path']);
            }
            exit();
        }
        foreach ($listFiles as $file) {
            if (preg_match('/^' . $this->_fileName . '\.[\d]{10}/', $file)) {
                $fileModified = date('Y-m-d H:i:s', filemtime($directory . $file));
                $fileModifiedDatetime = new DateTime($fileModified);
                $fileModifiedDatetime->add(new DateInterval('P10D'));

                if (date('Y-m-d') > $fileModifiedDatetime->format('Y-m-d')) {
                    unlink($directory . $file);
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
        copy($file_path . $this->_fileName . '.' . $this->_fileTimeStamp . '.' . $this->_fileFormat,
            $file_path . $this->_fileName . '.' . $this->_fileFormat);
        unlink($file_path . $this->_fileName . '.' . $this->_fileTimeStamp . '.' . $this->_fileFormat);
    }

    /**
     * get microtime float
     */
    protected function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    protected function stop($time_start, $string)
    {
        $time_end = $this->microtime_float();
        $time = $time_end - $time_start;
        if ($time < 0.0001) {
            $time = 0;
        }
        echo round($time, 4) . " &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; $string  secondes <br/>";
    }

    public function getExportUrl()
    {
        return Mage::getUrl('lengow/feed', array('store' => $this->_storeId));
    }
}
