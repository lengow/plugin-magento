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
                        $shopId = Mage::app()->getRequest()->getParam('id_shop');
                        if ($state !== null) {
                            Mage::helper('lengow_connector/config')->set('selection_enable', $state, $shopId);
                            $this->getResponse()->setBody($state);
                        }
                        break;
                    case 'change_option_product_out_of_stock':
                        $shopId = Mage::app()->getRequest()->getParam('id_shop');
                        $state = Mage::app()->getRequest()->getParam('state');
                        if ($state !== null) {
                            Mage::helper('lengow_connector/config')->set('out_stock', $state, $shopId);
                        }
                        break;
                    case 'check_shop':
                        $shopId = Mage::app()->getRequest()->getParam('id_shop');
                        $sync = Mage::getModel('lengow/connector')->getConnectorByStore($shopId);
                        $checkShop = array();
                        $checkShop['result'] = $sync;
                        if ($sync == true) {
                            $checkShop['message'] = Mage::helper('lengow_connector')
                                ->__('product.screen.lengow_shop_sync');
                            $checkShop['id'] = 'lengow_shop_sync';
                        } else {
                            $checkShop['message'] = Mage::helper('lengow_connector')
                                ->__('product.screen.lengow_shop_no_sync');
                            $checkShop['link_title'] = Mage::helper('lengow_connector')
                                ->__('product.screen.sync_your_shop');
                            $checkShop['link_href'] = Mage::helper('adminhtml')->getUrl('adminhtml/lengow_home/').'?isSync=true';
                            $checkShop['id'] = 'lengow_shop_no_sync';
                        }
                        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($checkShop));
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
        $product_ids = (array)$this->getRequest()->getParam('product');
        $store_id = (integer)$this->getRequest()->getParam('store', Mage::app()->getStore()->getId());
        // set default store if store_id is global
        if ($store_id == 0) {
            $store_id = Mage::app()->getWebsite(true)->getDefaultGroup()->getDefaultStoreId();
        }
        $publish = (integer)$this->getRequest()->getParam('publish');
        //update all attribute in one query
        $product_action = Mage::getSingleton('catalog/product_action');
        if ($store_id != 0) {
            $defaultStoreProductToUpdate = array();
            foreach ($product_ids as $product_id) {
                $lengow_product_value = Mage::getResourceModel('catalog/product')->getAttributeRawValue(
                    $product_id,
                    'lengow_product',
                    0
                );
                if ($lengow_product_value === false) {
                    $defaultStoreProductToUpdate[] = $product_id;
                }
            }
            // need to set default value if not set
            if (count($defaultStoreProductToUpdate) > 0) {
                $product_action->updateAttributes($defaultStoreProductToUpdate, array('lengow_product' => 0), 0);
            }
            if ($store_id != 0) {
                //set value for other store
                $product_action->updateAttributes($product_ids, array('lengow_product' => $publish), $store_id);
            }
        } else {
            $product_action->updateAttributes($product_ids, array('lengow_product' => $publish), $store_id);
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
