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
 * @subpackage  controllers
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * FeedController
 */
class Lengow_Connector_FeedController extends Mage_Core_Controller_Front_Action
{
    /**
     * Exports products for each store
     */
    public function indexAction()
    {
        /**
         * List params
         * string  mode               Number of products exported
         * string  format             Format of exported files ('csv','yaml','xml','json')
         * boolean stream             Stream file (1) or generate a file on server (0)
         * integer offset             Offset of total product
         * integer limit              Limit number of exported product
         * boolean selection          Export product selection (1) or all products (0)
         * boolean out_of_stock       Export out of stock product (1) Export only product in stock (0)
         * string  product_ids        List of product id separate with comma (1,2,3)
         * string  product_types      Type separate with comma (simple,configurable,downloadable,grouped,virtual)
         * string  product_status     Status separate with comma (1,2)
         * boolean variation          Export product Variation (1) Export parent product only (0)
         * boolean inactive           Export inactive product (1) or not (0)
         * string  code               Export a specific store with store code
         * integer store              Export a specific store with store id
         * string  currency           Convert prices with a specific currency
         * string  locale             Translate content with a specific locale
         * boolean legacy_fields      Export feed with v2 fields (1) or v3 fields (0)
         * boolean log_output         See logs (1) or not (0)
         * boolean update_export_date Change last export date in data base (1) or not (0)
         * boolean get_params         See export parameters and authorized values in json format (1) or not (0)
         */
        set_time_limit(0);
        ini_set('memory_limit', '1G');
        // compatibility old versions
        $selection = $this->getRequest()->getParam('selected_products', null);
        $outOfStock = $this->getRequest()->getParam('product_out_of_stock', null);
        $productIds = $this->getRequest()->getParam('ids_product', null);
        $productTypes = $this->getRequest()->getParam('product_type', null);
        // get params data
        $mode = $this->getRequest()->getParam('mode');
        $token = $this->getRequest()->getParam('token');
        $getParams = $this->getRequest()->getParam('get_params');
        $format = $this->getRequest()->getParam('format', null);
        $stream = $this->getRequest()->getParam('stream', null);
        $offset = $this->getRequest()->getParam('offset', null);
        $limit = $this->getRequest()->getParam('limit', null);
        $selection = is_null($selection) ? $this->getRequest()->getParam('selection', null) : $selection;
        $outOfStock = is_null($outOfStock) ? $this->getRequest()->getParam('out_of_stock', null) : $outOfStock;
        $productIds = is_null($productIds) ? $this->getRequest()->getParam('product_ids', null) : $productIds;
        $productTypes = is_null($productTypes)
            ? $this->getRequest()->getParam('product_types', null)
            : $productTypes;
        $productStatus = $this->getRequest()->getParam('product_status', null);
        $legacyFields = $this->getRequest()->getParam('legacy_fields', null);
        $logOutput = $this->getRequest()->getParam('log_output', null);
        $currency = $this->getRequest()->getParam('currency', null);
        $updateExportDate = $this->getRequest()->getParam('update_export_date', null);
        // get store data
        $storeCode = $this->getRequest()->getParam('code', null);
        if ($storeCode) {
            $storeId = (int)Mage::getModel('core/store')->load($storeCode, 'code')->getId();
        } else {
            $storeId = (int)$this->getRequest()->getParam('store', Mage::app()->getStore()->getId());
        }
        if ($locale = $this->getRequest()->getParam('locale', null)) {
            // changing locale works!
            Mage::app()->getLocale()->setLocale($locale);
            // needed to add this
            Mage::app()->getTranslator()->setLocale($locale);
            // translation now works
            Mage::app()->getTranslator()->init('frontend', true);
        }
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector');
        /** @var Lengow_Connector_Helper_Security $securityHelper */
        $securityHelper = Mage::helper('lengow_connector/security');
        if ($securityHelper->checkWebserviceAccess($token, $storeId)) {
            try {
                // config store
                Mage::app()->getStore()->setCurrentStore($storeId);
                // launch export process
                /** @var Lengow_Connector_Model_Export $export */
                $export = Mage::getModel(
                    'lengow/export',
                    array(
                        'store_id' => $storeId,
                        'format' => $format,
                        'mode' => $mode,
                        'product_types' => $productTypes,
                        'product_status' => $productStatus,
                        'out_of_stock' => $outOfStock,
                        'selection' => $selection,
                        'stream' => $stream,
                        'limit' => $limit,
                        'offset' => $offset,
                        'product_ids' => $productIds,
                        'currency' => $currency,
                        'legacy_fields' => $legacyFields,
                        'update_export_date' => $updateExportDate,
                        'log_output' => $logOutput,
                    )
                );
                $export->setOriginalCurrency(Mage::app()->getStore($storeId)->getCurrentCurrencyCode());
                if ($getParams) {
                    $this->getResponse()->setBody($export->getExportParams());
                } elseif ($mode === 'size') {
                    $this->getResponse()->setBody((string)$export->getTotalExportedProduct());
                } elseif ($mode === 'total') {
                    $this->getResponse()->setBody((string)$export->getTotalProduct());
                } else {
                    $export->exec();
                }
            } catch (Exception $e) {
                $errorMessage = '[Magento error] "' . $e->getMessage()
                    . '" ' . $e->getFile() . ' line ' . $e->getLine();
                $helper->log('Export', $errorMessage);
                $this->getResponse()->setHeader('HTTP/1.1', '500 Internal Server Error');
                $this->getResponse()->setBody($errorMessage);
            }
        } else {
            if ((bool)Mage::helper('lengow_connector/config')->get('ip_enable')) {
                $errorMessage = $helper->__(
                    'log.export.unauthorised_ip',
                    array('ip' => $securityHelper->getRemoteIp())
                );
            } else {
                $errorMessage =  strlen($token) > 0
                    ? $helper->__('log.export.unauthorised_token', array('token' => $token))
                    : $helper->__('log.export.empty_token');
            }
            $this->getResponse()->setHeader('HTTP/1.1', '403 Forbidden');
            $this->getResponse()->setBody($errorMessage);
        }
    }
}
