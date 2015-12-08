<?php
/**
 * Lengow export controller
 *
 * @category    Lengow
 * @package     Lengow_Export
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Marketplace_FeedController extends Mage_Core_Controller_Front_Action {

    //todo: clean old log (20 days) $log->cleanLog();

    /**
     * Exports products for each store
     */
    public function indexAction()
    {
        $log = Mage::getModel('lengow/log');


        set_time_limit(0);
        ini_set('memory_limit', '1G');


//        $mode = $this->getRequest()->getParam('mode');
//        $helper = Mage::helper('lenexport/security');
//        if($helper->checkIp()) {
//            $log->log('## Start manual export ##');
//            $_configModel = Mage::getSingleton('lenexport/config');
//            try {
//                $this->loadLayout(false);
//                $this->renderLayout();
//            } catch (Exception $e) {
//                Mage::throwException($e);
//            }
//
//            // get store
//            $storeCode = $this->getRequest()->getParam('code', null);
//            if ($storeCode) //if store code is in URL
//                $id_store = (int) Mage::getModel('core/store')->load($storeCode, 'code')->getId();
//            else // if store id is in URL
//                $id_store = (integer) $this->getRequest()->getParam('store', Mage::app()->getStore()->getId());
//
//            // config store
//            $_configModel->setStore($id_store);
//            Mage::app()->getStore()->setCurrentStore($id_store);
//
//            // check if store is enable for export
//            if(Mage::getStoreConfig('lenexport/global/active_store', Mage::app()->getStore($id_store))) {
//
//                if(Mage::getStoreConfig('lenexport/performances/optimizeexport')) {
//                    $generate = Mage::getSingleton('lenexport/generateoptimize');
//                }else{
//                    $generate = Mage::getSingleton('lenexport/generate');
//                }
//                $generate->setCurrentStore($id_store);
//                $generate->setOriginalCurrency(Mage::app()->getStore()->getCurrentCurrencyCode());
//
//                // other params
//                $format = $this->getRequest()->getParam('format', null);
//                $types = $this->getRequest()->getParam('product_type', null);
//                $export_child = $this->getRequest()->getParam('export_child', null);
//                $status = $this->getRequest()->getParam('product_status', null);
//                $out_of_stock = $this->getRequest()->getParam('product_out_of_stock', null);
//                $selected_products = $this->getRequest()->getParam('selected_products', null);
//                $stream = $this->getRequest()->getParam('stream', null);
//                $limit = $this->getRequest()->getParam('limit', null);
//                $offset = $this->getRequest()->getParam('offset', null);
//                $ids_product = $this->getRequest()->getParam('ids_product', null);
//                $debug = $this->getRequest()->getParam('debug', null);
//
//                if ($locale = $this->getRequest()->getParam('locale', null)) {
//                    // changing locale works!
//                    Mage::app()->getLocale()->setLocale($locale);
//                    // needed to add this
//                    Mage::app()->getTranslator()->setLocale($locale);
//                    // translation now works
//                    Mage::app()->getTranslator()->init('frontend', true);
//                }
//                if($currency = $this->getRequest()->getParam('currency', null)) {
//                    $generate->setCurrentCurrencyCode($currency);
//                }
//                $log->log('Start manual export in store ' . Mage::app()->getStore($id_store)->getName() . '(' . $id_store . ')');
//
//                if(Mage::getStoreConfig('lenexport/performances/optimizeexport')) {
//                    $generate->exec($id_store, $format, array(
//                        "mode" => $mode,
//                        "types" => $types,
//                        "status" => $status,
//                        "export_child" => $export_child,
//                        "out_of_stock" => $out_of_stock,
//                        "selected_products" => $selected_products,
//                        "stream" => $stream,
//                        "limit" => $limit,
//                        "offset" => $offset,
//                        "product_ids" => $ids_product,
//                        "debug" => $debug,
//                    ));
//                }else{
//                    $generate->exec($id_store, $mode, $format, $types, $status, $export_child, $out_of_stock, $selected_products, $stream, $limit, $offset, $ids_product);
//                }
//            } else {
//                $log->log('Stop manual export - Store ' . Mage::app()->getStore($id_store)->getName() . '(' . $id_store . ') is disabled');
//                header('Content-Type: text/html; charset=utf-8');
//                echo 'Stop manual export - Store ' . Mage::app()->getStore($id_store)->getName() . '(' . $id_store . ') is disabled';
//                flush();
//            }
//            $log->log('## End manual export ##');
//        } else {
//            echo $this->__('Unauthorised IP : %s', $_SERVER['REMOTE_ADDR']);
//        }
    }
}