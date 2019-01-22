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
 * Adminhtml lengow productController
 */
class Lengow_Connector_Adminhtml_Lengow_ProductController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Init action
     *
     * @return Lengow_Connector_Adminhtml_Lengow_ProductController
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
        if (Mage::helper('lengow_connector/sync')->pluginIsBlocked()) {
            $this->_redirect('adminhtml/lengow_home/index');
        } else {
            if ($this->getRequest()->getParam('isAjax')) {
                $action = Mage::app()->getRequest()->getParam('action');
                if ($action) {
                    switch ($action) {
                        case 'change_option_selected':
                            $state   = Mage::app()->getRequest()->getParam('state');
                            $storeId = Mage::app()->getRequest()->getParam('store_id');
                            if ($state !== null) {
                                Mage::helper('lengow_connector/config')->set('selection_enable', $state, $storeId);
                                Mage::app()->getCacheInstance()->cleanType('config');
                                $this->getResponse()->setBody($state);
                            }
                            break;
                    }
                }
            } else {
                $this->_initAction()->renderLayout();
            }
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
     *
     * @return Mage_Adminhtml_Model_Session|Mage_Core_Model_Abstract
     */
    protected function _getSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }

    /**
     * Is allowed
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('lengowtab/product');
    }
}
