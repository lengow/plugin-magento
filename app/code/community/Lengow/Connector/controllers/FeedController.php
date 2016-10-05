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
        $out_of_stock = $this->getRequest()->getParam('product_out_of_stock', null);
        $product_ids = $this->getRequest()->getParam('ids_product', null);
        $product_types = $this->getRequest()->getParam('product_type', null);
        // get params data
        $mode = $this->getRequest()->getParam('mode');
        $get_params = $this->getRequest()->getParam('get_params');
        $format = $this->getRequest()->getParam('format', null);
        $stream = $this->getRequest()->getParam('stream', null);
        $offset = $this->getRequest()->getParam('offset', null);
        $limit = $this->getRequest()->getParam('limit', null);
        $selection = is_null($selection) ? $this->getRequest()->getParam('selection', null) : $selection;
        $out_of_stock = is_null($out_of_stock) ? $this->getRequest()->getParam('out_of_stock', null) : $out_of_stock;
        $product_ids = is_null($product_ids) ? $this->getRequest()->getParam('product_ids', null) : $product_ids;
        $product_types = is_null($product_types)
            ? $this->getRequest()->getParam('product_types', null)
            : $product_types;
        $product_status = $this->getRequest()->getParam('product_status', null);
        $legacy_fields = $this->getRequest()->getParam('legacy_fields', null);
        $log_output = $this->getRequest()->getParam('log_output', null);
        $currency = $this->getRequest()->getParam('currency', null);
        $update_export_date = $this->getRequest()->getParam('update_export_date', null);
        //get store data
        $store_code = $this->getRequest()->getParam('code', null);
        if ($store_code) {
            $store_id = (int) Mage::getModel('core/store')->load($store_code, 'code')->getId();
        } else {
            $store_id = (integer) $this->getRequest()->getParam('store', Mage::app()->getStore()->getId());
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
                Mage::app()->getStore()->setCurrentStore($store_id);
                // launch export process
                $export = Mage::getModel('lengow/export', array(
                    'store_id'           => $store_id,
                    'format'             => $format,
                    'mode'               => $mode,
                    'get_params'         => $get_params,
                    'product_types'      => $product_types,
                    'product_status'     => $product_status,
                    'out_of_stock'       => $out_of_stock,
                    'selection'          => $selection,
                    'stream'             => $stream,
                    'limit'              => $limit,
                    'offset'             => $offset,
                    'product_ids'        => $product_ids,
                    'currency'           => $currency,
                    'legacy_fields'      => $legacy_fields,
                    'update_export_date' => $update_export_date,
                    'log_output'         => $log_output,
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
