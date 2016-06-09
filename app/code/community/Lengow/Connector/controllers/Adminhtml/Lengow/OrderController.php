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
                        $informations = $this->getInformations();
                        $informations['messages'] = $this->getMessages($results);
                        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($informations));
                        break;
                    case 're_import':
                        $order_lengow_id = Mage::app()->getRequest()->getParam('order_lengow_id');
                        if (!is_null($order_lengow_id)) {
                            $order_lengow = Mage::getModel('lengow/import_order');
                            $result = $order_lengow->reImportOrder((int)$order_lengow_id);
                            $informations = $this->getInformations();
                            $informations['import_order'] = $result;
                            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($informations));
                        }
                        break;
                    case 're_send':
                        $order_lengow_id = Mage::app()->getRequest()->getParam('order_lengow_id');
                        if (!is_null($order_lengow_id)) {
                            $order_lengow = Mage::getModel('lengow/import_order');
                            $result = $order_lengow->reSendOrder((int)$order_lengow_id);
                            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                        }
                        break;
                    case 'migrate_order':
                        $order = Mage::getModel('lengow/import_order');
                        $order->migrateOldOrder();
                        break;
                    case 'load_information':
                        $informations = $this->getInformations();
                        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($informations));
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
        $order_lengow_ids = $this->getRequest()->getParam('order');
        if (count($order_lengow_ids) > 0) {
            $order_lengow = Mage::getModel('lengow/import_order');
            foreach ($order_lengow_ids as $order_lengow_id) {
                $order_lengow->reImportOrder((int)$order_lengow_id);
            }
        }
    }

    public function massReSendAction()
    {
        $order_lengow_ids = $this->getRequest()->getParam('order');
        if (count($order_lengow_ids) > 0) {
            $order_lengow = Mage::getModel('lengow/import_order');
            foreach ($order_lengow_ids as $order_lengow_id) {
                $order_lengow->reSendOrder((int)$order_lengow_id);
            }
        }
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
        // if global error return this
        if (isset($results['error'][0])) {
            $messages[] = $helper->decodeLogMessage($results['error'][0]);
            return $messages;
        }
        if (isset($results['order_new']) && $results['order_new'] > 0) {
            $messages[]= $helper->__('lengow_log.error.nb_order_imported', array(
                'nb_order' => $results['order_new']
            ));
        }
        if (isset($results['order_update']) && $results['order_update'] > 0) {
            $messages[]= $helper->__('lengow_log.error.nb_order_updated', array(
                'nb_order' => $results['order_update']
            ));
        }
        if (isset($results['order_error']) && $results['order_error'] > 0) {
            $messages[]= $helper->__('lengow_log.error.nb_order_with_error', array(
                'nb_order' => $results['order_error']
            ));
        }
        if (count($messages) == 0) {
            $messages[]= $helper->__('lengow_log.error.no_notification');
        }
        if (isset($results['error'])) {
            foreach ($results['error'] as $store_id => $values) {
                if ((int)$store_id > 0) {
                    $store = Mage::getModel('core/store')->load($store_id);
                    $store_name = $store->getName().' ('.$store->getId().') : ';
                    if (is_array($values)) {
                        $messages[] = $store_name.join(', ', $helper->decodeLogMessage($values));
                    } else {
                        $messages[] = $store_name.$helper->decodeLogMessage($values);
                    }
                }
            }
        }
        return $messages;
    }

    public function getInformations()
    {
        $informations = array();
        $helper = Mage::helper('lengow_connector');
        $last_import = Mage::helper('lengow_connector/import')->getLastImport();
        $last_import_date = $helper->getDateInCorrectFormat(time());
        $order = Mage::getModel('lengow/import_order');
        $informations['order_with_error'] = $helper->__('order.screen.order_with_error', array(
            'nb_order' => $order->countOrderWithError(),
        ));
        $informations['order_to_be_sent'] = $helper->__('order.screen.order_to_be_sent', array(
            'nb_order' => $order->countOrderToBeSent(),
        ));
        if ($last_import['type'] != 'none') {
            $informations['last_importation'] = $helper->__('order.screen.last_order_importation', array(
                'last_importation' => '<b>'.$last_import_date.'</b>'
            ));
        } else {
            $informations['last_importation'] = $helper->__('order.screen.no_order_importation');
        }
        return $informations;
    }
}
