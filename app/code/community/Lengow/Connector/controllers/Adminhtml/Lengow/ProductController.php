<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Adminhtml_Lengow_ProductController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Init action
     */
    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu('lengowtab');
        return $this;
    }

    /**
     * Index Action
     */
    public function indexAction()
    {
        if ($this->getRequest()->getParam('isAjax')) {
            $action = Mage::app()->getRequest()->getParam('action');
            if ($action) {
                switch ($action) {
                    case 'change_option_selected':
                        $state = Mage::app()->getRequest()->getParam('state');
                        $storeId = Mage::app()->getRequest()->getParam('store_id');
                        if ($state !== null) {
                            Mage::helper('lengow_connector/config')->set('selection_enable', $state, $storeId);
                            $this->getResponse()->setBody($state);
                        }
                        break;
                    case 'check_store':
                        $storeId = Mage::app()->getRequest()->getParam('store_id');
                        $sync = Mage::helper('lengow_connector/sync')->checkSyncStore($storeId);
                        $helper = Mage::helper('lengow_connector');
                        $datas = array();
                        $datas['result'] = $sync;
                        if ($sync == true) {
                            $lastExport = Mage::helper('lengow_connector/config')->get('last_export', $storeId);
                            if ($lastExport != null) {
                                $datas['message'] = $helper->__('product.screen.store_last_indexation').'<br />'.
                                    $helper->getDateInCorrectFormat($lastExport);
                            } else {
                                $datas['message'] = $helper->__('product.screen.store_not_index');
                            }
                            $datas['link_title'] = $helper->__('product.screen.lengow_store_sync');
                            $datas['id'] = 'lengow_store_sync';
                        } else {
                            $datas['message'] = $helper->__('product.screen.lengow_store_no_sync');
                            $datas['link_title'] = $helper->__('product.screen.sync_your_store');
                            $datas['link_href'] = 'http://my.lengow.io/company/store';
                            $datas['id'] = 'lengow_store_no_sync';
                        }
                        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($datas));
                        break;
                }
            }
        } else {
            $this->_initAction()->renderLayout();
            return $this;
        }
        
    }

    /**
     * Product grid for AJAX request
     */
    public function gridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('lengow/adminhtml_product_grid')->toHtml()
        );
    }

    /**
     * Mass publish product action
     */
    public function massPublishAction()
    {
        $productIds = (array)$this->getRequest()->getParam('product');
        $storeId = (integer)$this->getRequest()->getParam('store', Mage::app()->getStore()->getId());
        // set default store if storeId is global
        if ($storeId == 0) {
            $storeId = Mage::app()->getWebsite(true)->getDefaultGroup()->getDefaultStoreId();
        }
        $publish = (integer)$this->getRequest()->getParam('publish');
        //update all attribute in one query
        $productAction = Mage::getSingleton('catalog/product_action');
        if ($storeId != 0) {
            $defaultStoreProductToUpdate = array();
            foreach ($productIds as $productId) {
                $lengowProductValue = Mage::getResourceModel('catalog/product')->getAttributeRawValue(
                    $productId,
                    'lengow_product',
                    0
                );
                if ($lengowProductValue === false) {
                    $defaultStoreProductToUpdate[] = $productId;
                }
            }
            // need to set default value if not set
            if (count($defaultStoreProductToUpdate) > 0) {
                $productAction->updateAttributes($defaultStoreProductToUpdate, array('lengow_product' => 0), 0);
            }
            if ($storeId != 0) {
                //set value for other store
                $productAction->updateAttributes($productIds, array('lengow_product' => $publish), $storeId);
            }
        } else {
            $productAction->updateAttributes($productIds, array('lengow_product' => $publish), $storeId);
        }
    }

    /**
     * Get session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }

    /**
     * Is allowed
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('lengow_connector/product');
    }
}
