<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Adminhtml_Lengow_OrderController extends Mage_Adminhtml_Controller_Action
{

    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu('lengowtab');
        return $this;
    }

    public function indexAction()
    {
        $this->_initAction()->renderLayout();
        return $this;
    }

    public function importAction()
    {
        $helper = Mage::helper('lengow_connector');
        $params['type'] = 'manual';
        // Import orders
        $import = Mage::getModel('lengow/import', $params);
        $results = $import->exec();
        // Create messages
        $this->_getSession()->addSuccess($helper->__('lengow_log.error.nb_order_imported', array(
            'nb_order' => $results['order_new']
        )));
        $this->_getSession()->addSuccess($helper->__('lengow_log.error.nb_order_updated', array(
            'nb_order' => $results['order_update']
        )));
        if ($results['order_error'] > 0) {
            $this->_getSession()->addError($helper->__('lengow_log.error.nb_order_with_error', array(
                'nb_order' => $results['order_error']
            )));
        } else {
            $this->_getSession()->addSuccess($helper->__('lengow_log.error.nb_order_with_error', array(
                'nb_order' => $results['order_error']
            )));
        }
        if (isset($results['error'])) {
            foreach ($results['error'] as $store_id => $values) {
                if ((int)$store_id > 0) {
                    $store = Mage::getModel('core/store')->load($store_id);
                    $store_name = $store->getName().' ('.$store->getId().') : ';
                } else {
                    $store_name = '';
                }
                if (is_array($values)) {
                    $error_message = $store_name.join(', ', $helper->decodeLogMessage($values));
                } else {
                    $error_message = $store_name.$helper->decodeLogMessage($values);
                }
                $this->_getSession()->addError($error_message);
            }
        }
        $this->_redirect('*/*/index');
    }

    public function migrateAction()
    {
        $order = Mage::getModel('lengow/import_order');
        $order->migrateOldOrder();
        $this->_redirect('*/*/index');
    }

    public function massReImportAction()
    {
        $order_ids = $this->getRequest()->getParam('order');
        $this->_redirect('*/*/index');
    }

    public function massReSendAction()
    {
        $order_ids = $this->getRequest()->getParam('order');
        $this->_redirect('*/*/index');
    }

    /**
     * Product grid for AJAX request
     */
    public function gridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('lengow/adminhtml_order_grid')->toHtml()
        );
    }

    protected function _getSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('lengow_connector/order');
    }
}
