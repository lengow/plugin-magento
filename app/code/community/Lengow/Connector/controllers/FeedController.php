<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_FeedController extends Mage_Core_Controller_Front_Action
{
    /**
     * Exports products for each store
     */
    public function indexAction()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');
        // compatibility old versions
        $selection = $this->getRequest()->getParam('selected_products', null);
        $outOfStock = $this->getRequest()->getParam('product_out_of_stock', null);
        $productIds = $this->getRequest()->getParam('ids_product', null);
        $productTypes = $this->getRequest()->getParam('product_type', null);
        // get params data
        $mode = $this->getRequest()->getParam('mode');
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
        //get store data
        $storeCode = $this->getRequest()->getParam('code', null);
        if ($storeCode) {
            $storeId = (int) Mage::getModel('core/store')->load($storeCode, 'code')->getId();
        } else {
            $storeId = (integer) $this->getRequest()->getParam('store', Mage::app()->getStore()->getId());
        }
        if ($locale = $this->getRequest()->getParam('locale', null)) {
            // changing locale works!
            Mage::app()->getLocale()->setLocale($locale);
            // needed to add this
            Mage::app()->getTranslator()->setLocale($locale);
            // translation now works
            Mage::app()->getTranslator()->init('frontend', true);
        }
        $helper = Mage::helper('lengow_connector');
        $security = Mage::helper('lengow_connector/security');
        if ($security->checkIp()) {
            try {
                // config store
                Mage::app()->getStore()->setCurrentStore($storeId);
                // launch export process
                $export = Mage::getModel(
                    'lengow/export',
                    array(
                        'store_id'           => $storeId,
                        'format'             => $format,
                        'mode'               => $mode,
                        'get_params'         => $getParams,
                        'product_types'      => $productTypes,
                        'product_status'     => $productStatus,
                        'out_of_stock'       => $outOfStock,
                        'selection'          => $selection,
                        'stream'             => $stream,
                        'limit'              => $limit,
                        'offset'             => $offset,
                        'product_ids'        => $productIds,
                        'currency'           => $currency,
                        'legacy_fields'      => $legacyFields,
                        'update_export_date' => $updateExportDate,
                        'log_output'         => $logOutput,
                    )
                );
                $export->exec();
            } catch (Exception $e) {
                $errorMessage = '[Magento error] "'.$e->getMessage().'" '.$e->getFile().' line '.$e->getLine();
                $helper->log('Export', $errorMessage);
                $this->getResponse()->setHeader('HTTP/1.1', '500 Internal Server Error');
                $this->getResponse()->setBody($errorMessage);
            }
        } else {
            $this->getResponse()->setHeader('HTTP/1.1', '403 Forbidden');
            $this->getResponse()->setBody(
                Mage::helper('lengow_connector')->__(
                    'log.export.unauthorised_ip',
                    array('ip' => Mage::helper('core/http')->getRemoteAddr())
                )
            );
        }
    }
}
