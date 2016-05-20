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
        if ($this->getRequest()->getParam('isAjax')) {
            $action = Mage::app()->getRequest()->getParam('action');
            if ($action) {
                switch ($action) {
                    case 'import_all':
                        $params =  array('type' => 'manual');
                        $import = Mage::getModel('lengow/import', $params);
                        $results = $import->exec();
                        $messages = $this->getMessages($results);
                        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($messages));
                        break;
                    case 'migrate_order':
                        $order = Mage::getModel('lengow/import_order');
                        $order->migrateOldOrder();
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
            $this->getLayout()->createBlock('lengow/adminhtml_order_grid')->toHtml()
        );
    }

    public function massReImportAction()
    {
        $order_ids = $this->getRequest()->getParam('order');
    }

    public function massReSendAction()
    {
        $order_ids = $this->getRequest()->getParam('order');
    }

    protected function _getSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('lengow_connector/order');
    }

    public function getMessages($results)
    {
        $messages = array();
        $helper = Mage::helper('lengow_connector');
        if (isset($results['order_new'])) {
            $messages[]= $helper->__('lengow_log.error.nb_order_imported', array(
                'nb_order' => $results['order_new']
            ));
        }
        if (isset($results['order_update'])) {
            $messages[]= $helper->__('lengow_log.error.nb_order_updated', array(
                'nb_order' => $results['order_update']
            ));
        }
        if (isset($results['order_error'])) {
            $messages[]= $helper->__('lengow_log.error.nb_order_with_error', array(
                'nb_order' => $results['order_error']
            ));
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
                    $messages[] = $store_name.join(', ', $helper->decodeLogMessage($values));
                } else {
                    $messages[] = $store_name.$helper->decodeLogMessage($values);
                }
            }
        }
        return $messages;
    }
}
