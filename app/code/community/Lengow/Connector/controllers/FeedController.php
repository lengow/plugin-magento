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
        $token = $this->getRequest()->getParam(Lengow_Connector_Model_Export::PARAM_TOKEN);
        // get store data
        $storeCode = $this->getRequest()->getParam(Lengow_Connector_Model_Export::PARAM_CODE, null);
        if ($storeCode) {
            $storeId = (int) Mage::getModel('core/store')->load($storeCode, 'code')->getId();
        } else {
            $storeId = (int) $this->getRequest()->getParam(
                Lengow_Connector_Model_Export::PARAM_STORE,
                Mage::app()->getStore()->getId()
            );
        }
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector');
        /** @var Lengow_Connector_Helper_Security $securityHelper */
        $securityHelper = Mage::helper('lengow_connector/security');
        if ($securityHelper->checkWebserviceAccess($token, $storeId)) {
            // compatibility old versions
            $selection = $this->getRequest()->getParam(Lengow_Connector_Model_Export::PARAM_LEGACY_SELECTION, null);
            $outOfStock = $this->getRequest()->getParam(Lengow_Connector_Model_Export::PARAM_LEGACY_OUT_OF_STOCK, null);
            $productIds = $this->getRequest()->getParam(Lengow_Connector_Model_Export::PARAM_LEGACY_PRODUCT_IDS, null);
            $productTypes = $this->getRequest()->getParam(
                Lengow_Connector_Model_Export::PARAM_LEGACY_PRODUCT_TYPES,
                null
            );
            $inactive = $this->getRequest()->getParam(Lengow_Connector_Model_Export::PARAM_LEGACY_INACTIVE, null);
            $language = $this->getRequest()->getParam(Lengow_Connector_Model_Export::PARAM_LEGACY_LANGUAGE, null);
            // get params data
            $mode = $this->getRequest()->getParam(Lengow_Connector_Model_Export::PARAM_MODE);
            $getParams = $this->getRequest()->getParam(Lengow_Connector_Model_Export::PARAM_GET_PARAMS);
            $format = $this->getRequest()->getParam(Lengow_Connector_Model_Export::PARAM_FORMAT, null);
            $stream = $this->getRequest()->getParam(Lengow_Connector_Model_Export::PARAM_STREAM, null);
            $offset = $this->getRequest()->getParam(Lengow_Connector_Model_Export::PARAM_OFFSET, null);
            $limit = $this->getRequest()->getParam(Lengow_Connector_Model_Export::PARAM_LIMIT, null);
            $selection = $selection === null
                ? $this->getRequest()->getParam(Lengow_Connector_Model_Export::PARAM_SELECTION, null)
                : $selection;
            $outOfStock = $outOfStock === null
                ? $this->getRequest()->getParam(Lengow_Connector_Model_Export::PARAM_OUT_OF_STOCK, null)
                : $outOfStock;
            $productIds = $productIds === null
                ? $this->getRequest()->getParam(Lengow_Connector_Model_Export::PARAM_PRODUCT_IDS, null)
                : $productIds;
            $productTypes = $productTypes === null
                ? $this->getRequest()->getParam(Lengow_Connector_Model_Export::PARAM_PRODUCT_TYPES, null)
                : $productTypes;
            $inactive = $inactive === null
                ? $this->getRequest()->getParam(Lengow_Connector_Model_Export::PARAM_INACTIVE, null)
                : strpos($inactive, (string) Mage_Catalog_Model_Product_Status::STATUS_DISABLED) !== false;
            $legacyFields = $this->getRequest()->getParam(Lengow_Connector_Model_Export::PARAM_LEGACY_FIELDS, null);
            $logOutput = $this->getRequest()->getParam(Lengow_Connector_Model_Export::PARAM_LOG_OUTPUT, null);
            $currency = $this->getRequest()->getParam(Lengow_Connector_Model_Export::PARAM_CURRENCY, null);
            $updateExportDate = $this->getRequest()->getParam(
                Lengow_Connector_Model_Export::PARAM_UPDATE_EXPORT_DATE,
                null
            );
            $language = $language === null
                ? $this->getRequest()->getParam(Lengow_Connector_Model_Export::PARAM_LANGUAGE, null)
                : $language;
            if ($language) {
                // changing locale works!
                Mage::app()->getLocale()->setLocale($language);
                // needed to add this
                Mage::app()->getTranslator()->setLocale($language);
                // translation now works
                Mage::app()->getTranslator()->init('frontend', true);
            }
            try {
                // config store
                Mage::app()->getStore()->setCurrentStore($storeId);
                // launch export process
                /** @var Lengow_Connector_Model_Export $export */
                $export = Mage::getModel(
                    'lengow/export',
                    array(
                        Lengow_Connector_Model_Export::PARAM_STORE_ID => $storeId,
                        Lengow_Connector_Model_Export::PARAM_FORMAT => $format,
                        Lengow_Connector_Model_Export::PARAM_PRODUCT_TYPES => $productTypes,
                        Lengow_Connector_Model_Export::PARAM_INACTIVE => $inactive,
                        Lengow_Connector_Model_Export::PARAM_OUT_OF_STOCK => $outOfStock,
                        Lengow_Connector_Model_Export::PARAM_SELECTION => $selection,
                        Lengow_Connector_Model_Export::PARAM_STREAM => $stream,
                        Lengow_Connector_Model_Export::PARAM_LIMIT => $limit,
                        Lengow_Connector_Model_Export::PARAM_OFFSET => $offset,
                        Lengow_Connector_Model_Export::PARAM_PRODUCT_IDS => $productIds,
                        Lengow_Connector_Model_Export::PARAM_CURRENCY => $currency,
                        Lengow_Connector_Model_Export::PARAM_LEGACY_FIELDS => $legacyFields,
                        Lengow_Connector_Model_Export::PARAM_UPDATE_EXPORT_DATE => $updateExportDate,
                        Lengow_Connector_Model_Export::PARAM_LOG_OUTPUT => $logOutput,
                    )
                );
                $export->setOriginalCurrency(Mage::app()->getStore($storeId)->getCurrentCurrencyCode());
                if ($getParams) {
                    $this->getResponse()->setBody($export->getExportParams());
                } elseif ($mode === 'size') {
                    $this->getResponse()->setBody((string) $export->getTotalExportProduct());
                } elseif ($mode === 'total') {
                    $this->getResponse()->setBody((string) $export->getTotalProduct());
                } else {
                    $export->exec();
                }
            } catch (Exception $e) {
                $errorMessage = '[Magento error] "' . $e->getMessage() . '" '
                    . $e->getFile() . ' line ' . $e->getLine();
                $helper->log(Lengow_Connector_Helper_Data::CODE_EXPORT, $errorMessage);
                $this->getResponse()->setHeader('HTTP/1.1', '500 Internal Server Error');
                $this->getResponse()->setBody($errorMessage);
            }
        } else {
            if (Mage::helper('lengow_connector/config')->get(Lengow_Connector_Helper_Config::AUTHORIZED_IP_ENABLED)) {
                $errorMessage = $helper->__(
                    'log.export.unauthorised_ip',
                    array('ip' => $securityHelper->getRemoteIp())
                );
            } else {
                $errorMessage =  $token !== ''
                    ? $helper->__('log.export.unauthorised_token', array('token' => $token))
                    : $helper->__('log.export.empty_token');
            }
            $this->getResponse()->setHeader('HTTP/1.1', '403 Forbidden');
            $this->getResponse()->setBody($errorMessage);
        }
    }
}
