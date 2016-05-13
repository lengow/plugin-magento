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

    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu('lengowtab');
        return $this;
    }

    public function indexAction()
    {
        if ($this->getRequest()->getParam('isAjax')) {
            $action = Mage::app()->getRequest()->getParam('action');
            $shopId = Mage::app()->getRequest()->getParam('shop_id');
            if ($action) {
                switch ($action) {
                    case 'change_option_selected':
                        $state = Mage::app()->getRequest()->getParam('state');
                        Mage::helper('lengow_connector/config')->set('selection_enable', $state, $shopId);
                        if ($state == '1') {
                            echo "lengow_jquery('#productGrid').show();";
                        } else {
                            echo "lengow_jquery('#productGrid').hide();";
                        }
                        break;
                    case 'change_option_product_out_of_stock':
                        $state = Mage::app()->getRequest()->getParam('state');
                        Mage::helper('lengow_connector/config')->set('out_stock', $state, $shopId);
                        break;
                    case 'change_option_type':
                        $values = Mage::app()->getRequest()->getParam('types');
                        Mage::helper('lengow_connector/config')->set('product_type', $values, $shopId);
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

    public function massPublishAction()
    {

        $product_ids = (array)$this->getRequest()->getParam('product');
        $store_id = (integer)$this->getRequest()->getParam('store', Mage::app()->getStore()->getId());
        $publish = (integer)$this->getRequest()->getParam('publish');

        try {
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
                //need to set default value if not set
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
            $this->_getSession()->addSuccess(
                Mage::helper('lengow_connector')->__('Total of '.count($product_ids).' record(s) were successfully updated')
            );
        } catch (Mage_Core_Model_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addException(
                $e,
                $e->getMessage().$this->__('There was an error while updating product(s) publication')
            );
        }
    }

    protected function _getSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('lengow_connector/product');
    }
}
