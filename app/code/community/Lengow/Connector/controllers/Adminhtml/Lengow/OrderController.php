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
                    case 'import_all':
                        $params =  array('type' => 'manual');
                        $import = Mage::getModel('lengow/import', $params);
                        $results = $import->exec();
                        $informations = $this->getInformations();
                        $informations['messages'] = $this->getMessages($results);
                        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($informations));
                        break;
                    case 're_import':
                        $orderLengowId = Mage::app()->getRequest()->getParam('order_lengow_id');
                        if (!is_null($orderLengowId)) {
                            $orderLengow = Mage::getModel('lengow/import_order');
                            $result = $orderLengow->reImportOrder((int)$orderLengowId);
                            $informations = $this->getInformations();
                            $informations['import_order'] = $result;
                            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($informations));
                        }
                        break;
                    case 're_send':
                        $orderLengowId = Mage::app()->getRequest()->getParam('order_lengow_id');
                        if (!is_null($orderLengowId)) {
                            $orderLengow = Mage::getModel('lengow/import_order');
                            $result = $orderLengow->reSendOrder((int)$orderLengowId);
                            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                        }
                        break;
                    case 'migrate_button_fade':
                        Mage::helper('lengow_connector/config')->set('see_migrate_block', 0);
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
     * Order grid for AJAX request
     */
    public function gridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('lengow/adminhtml_order_grid')->toHtml()
        );
    }

    /**
     * Synchronize order action
     */
    public function synchronizeAction()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($orderId);
        $marketplaceSku = $order->getData('order_id_lengow');
        $synchro = Mage::getModel('lengow/import_order')->synchronizeOrder($order);
        if ($synchro) {
            $synchroMessage =  Mage::helper('lengow_connector/data')->setLogMessage(
                'log.import.order_synchronized_with_lengow',
                array('order_id' => $order->getIncrementId())
            );
        } else {
            $synchroMessage =  Mage::helper('lengow_connector/data')->setLogMessage(
                'log.import.order_not_synchronized_with_lengow',
                array('order_id' => $order->getIncrementId())
            );
        }
        Mage::helper('lengow_connector/data')->log('Import', $synchroMessage, false, $marketplaceSku);
        $url = Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/view", array('order_id' => $orderId));
        Mage::app()->getResponse()->setRedirect($url);
    }

    /**
     * Cancel and re-import order action
     */
    public function cancelAndReImportOrderAction()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($orderId);
        $newOrderId = Mage::getModel('lengow/import_order')->cancelAndReImportOrder($order);
        if (!$newOrderId) {
            $newOrderId = $orderId;
        }
        $url = Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/view", array('order_id' => $newOrderId));
        Mage::app()->getResponse()->setRedirect($url);
    }

    /**
     * Re-send action
     */
    public function reSendAction()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $action = $this->getRequest()->getParam('action') == 'complete'
            ? 'ship'
            : $this->getRequest()->getParam('action');
        $order = Mage::getModel('sales/order')->load($orderId);
        $shipment = $order->getShipmentsCollection()->getFirstItem();
        Mage::getModel('lengow/import_order')->callAction($action, $order, $shipment);
        $url = Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/view", array('order_id' => $orderId));
        Mage::app()->getResponse()->setRedirect($url);
    }

    /**
     * Mass re-import order action
     */
    public function massReImportAction()
    {
        $orderLengowIds = $this->getRequest()->getParam('order');
        if (count($orderLengowIds) > 0) {
            $orderLengow = Mage::getModel('lengow/import_order');
            foreach ($orderLengowIds as $orderLengowId) {
                $orderLengow->reImportOrder((int)$orderLengowId);
            }
        }
    }

    /**
     * Mass re-send order action
     */
    public function massReSendAction()
    {
        $orderLengowIds = $this->getRequest()->getParam('order');
        if (count($orderLengowIds) > 0) {
            $orderLengow = Mage::getModel('lengow/import_order');
            foreach ($orderLengowIds as $orderLengowId) {
                $orderLengow->reSendOrder((int)$orderLengowId);
            }
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
        return Mage::getSingleton('admin/session')->isAllowed('lengow_connector/order');
    }

    /**
     * Get Messages
     *
     * @param array $results results from import process
     *
     * @return array
     */
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
            $messages[]= $helper->__(
                'lengow_log.error.nb_order_imported',
                array('nb_order' => $results['order_new'])
            );
        }
        if (isset($results['order_update']) && $results['order_update'] > 0) {
            $messages[]= $helper->__(
                'lengow_log.error.nb_order_updated',
                array('nb_order' => $results['order_update'])
            );
        }
        if (isset($results['order_error']) && $results['order_error'] > 0) {
            $messages[]= $helper->__(
                'lengow_log.error.nb_order_with_error',
                array('nb_order' => $results['order_error'])
            );
        }
        if (count($messages) == 0) {
            $messages[]= $helper->__('lengow_log.error.no_notification');
        }
        if (isset($results['error'])) {
            foreach ($results['error'] as $storeId => $values) {
                if ((int)$storeId > 0) {
                    $store = Mage::getModel('core/store')->load($storeId);
                    $storeName = $store->getName().' ('.$store->getId().') : ';
                    if (is_array($values)) {
                        $messages[] = $storeName.join(', ', $helper->decodeLogMessage($values));
                    } else {
                        $messages[] = $storeName.$helper->decodeLogMessage($values);
                    }
                }
            }
        }
        return $messages;
    }

    /**
     * Get all order informations
     *
     * @return array
     */
    public function getInformations()
    {
        $informations = array();
        $helper = Mage::helper('lengow_connector');
        $lastImport = Mage::helper('lengow_connector/import')->getLastImport();
        $lastImportDate = $helper->getDateInCorrectFormat(time());
        $order = Mage::getModel('lengow/import_order');
        $informations['order_with_error'] = $helper->__(
            'order.screen.order_with_error',
            array('nb_order' => $order->countOrderWithError())
        );
        $informations['order_to_be_sent'] = $helper->__(
            'order.screen.order_to_be_sent',
            array('nb_order' => $order->countOrderToBeSent())
        );
        if ($lastImport['type'] != 'none') {
            $informations['last_importation'] = $helper->__(
                'order.screen.last_order_importation',
                array('last_importation' => '<b>'.$lastImportDate.'</b>')
            );
        } else {
            $informations['last_importation'] = $helper->__('order.screen.no_order_importation');
        }
        return $informations;
    }
}
