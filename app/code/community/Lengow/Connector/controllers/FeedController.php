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
        //get params data
        $mode = $this->getRequest()->getParam('mode');
        $format = $this->getRequest()->getParam('format', null);
        $types = $this->getRequest()->getParam('product_type', null);
        $export_child = $this->getRequest()->getParam('export_child', null);
        $status = $this->getRequest()->getParam('product_status', null);
        $out_of_stock = $this->getRequest()->getParam('product_out_of_stock', null);
        $selected_products = $this->getRequest()->getParam('selected_products', null);
        $stream = $this->getRequest()->getParam('stream', null);
        $limit = $this->getRequest()->getParam('limit', null);
        $offset = $this->getRequest()->getParam('offset', null);
        $ids_product = $this->getRequest()->getParam('ids_product', null);
        $currency = $this->getRequest()->getParam('currency', null);
        $update_export_date = $this->getRequest()->getParam('update_export_date', null);
        //get store data
        $storeCode = $this->getRequest()->getParam('code', null);
        if ($storeCode) {
            $storeId = (int) Mage::getModel('core/store')->load($storeCode, 'code')->getId();
        } else {
            $storeId = (integer) $this->getRequest()->getParam('store', Mage::app()->getStore()->getId());
        }
        $storeName = Mage::app()->getStore($storeId)->getName();
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
                $export = Mage::getModel('lengow/export', array(
                    'store_id'           => $storeId,
                    'format'             => $format,
                    'mode'               => $mode,
                    'types'              => $types,
                    'status'             => $status,
                    'out_of_stock'       => $out_of_stock,
                    'selected_products'  => $selected_products,
                    'stream'             => $stream,
                    'limit'              => $limit,
                    'offset'             => $offset,
                    'product_ids'        => $ids_product,
                    'currency'           => $currency,
                    'update_export_date' => $update_export_date,
                ));
                $export->exec();
            } catch (Exception $e) {
                $error_message = '[Magento error] "'.$e->getMessage().'" '.$e->getFile().' line '.$e->getLine();
                $helper->log('Export', $error_message);
                $this->getResponse()->setHeader('HTTP/1.1', '500 Internal Server Error');
                $this->getResponse()->setBody($error_message);
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
