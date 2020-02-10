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
 * Adminhtml lengow orderController
 */
class Lengow_Connector_Adminhtml_Lengow_OrderController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Init action
     *
     * @return Lengow_Connector_Adminhtml_Lengow_OrderController
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
                        case 'import_all':
                            $params = array('type' => Lengow_Connector_Model_Import::TYPE_MANUAL);
                            $results = Mage::getModel('lengow/import', $params)->exec();
                            $informations = $this->getInformations();
                            $informations['messages'] = $this->getMessages($results);
                            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($informations));
                            break;
                        case 're_import':
                            $orderLengowId = Mage::app()->getRequest()->getParam('order_lengow_id');
                            if (!is_null($orderLengowId)) {
                                $result = Mage::getModel('lengow/import_order')->reImportOrder((int)$orderLengowId);
                                $informations = $this->getInformations();
                                $informations['import_order'] = $result;
                                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($informations));
                            }
                            break;
                        case 're_send':
                            $orderLengowId = Mage::app()->getRequest()->getParam('order_lengow_id');
                            if (!is_null($orderLengowId)) {
                                $result = Mage::getModel('lengow/import_order')->reSendOrder((int)$orderLengowId);
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
            }
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
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector/data');
        $orderId = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($orderId);
        $marketplaceSku = $order->getData('order_id_lengow');
        $synchro = Mage::getModel('lengow/import_order')->synchronizeOrder($order);
        if ($synchro) {
            $synchroMessage = $helper->setLogMessage(
                'log.import.order_synchronized_with_lengow',
                array('order_id' => $order->getIncrementId())
            );
        } else {
            $synchroMessage = $helper->setLogMessage(
                'log.import.order_not_synchronized_with_lengow',
                array('order_id' => $order->getIncrementId())
            );
        }
        $helper->log(Lengow_Connector_Helper_Data::CODE_IMPORT, $synchroMessage, false, $marketplaceSku);
        $url = Mage::helper('adminhtml')->getUrl('adminhtml/sales_order/view', array('order_id' => $orderId));
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
        $url = Mage::helper('adminhtml')->getUrl('adminhtml/sales_order/view', array('order_id' => $newOrderId));
        Mage::app()->getResponse()->setRedirect($url);
    }

    /**
     * Re-send action
     */
    public function reSendAction()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $action = $this->getRequest()->getParam('action') === 'canceled'
            ? Lengow_Connector_Model_Import_Action::TYPE_CANCEL
            : Lengow_Connector_Model_Import_Action::TYPE_SHIP;
        $order = Mage::getModel('sales/order')->load($orderId);
        $shipment = $action === Lengow_Connector_Model_Import_Action::TYPE_SHIP
            ? $order->getShipmentsCollection()->getFirstItem()
            : null;
        Mage::getModel('lengow/import_order')->callAction($action, $order, $shipment);
        $url = Mage::helper('adminhtml')->getUrl('adminhtml/sales_order/view', array('order_id' => $orderId));
        Mage::app()->getResponse()->setRedirect($url);
    }

    /**
     * Mass re-import order action
     */
    public function massReImportAction()
    {
        $orderLengowIds = $this->getRequest()->getParam('order');
        if (count($orderLengowIds) > 0) {
            /** @var Lengow_Connector_Model_Import_Order $orderLengow */
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
            /** @var Lengow_Connector_Model_Import_Order $orderLengow */
            $orderLengow = Mage::getModel('lengow/import_order');
            foreach ($orderLengowIds as $orderLengowId) {
                $orderLengow->reSendOrder((int)$orderLengowId);
            }
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
        return Mage::getSingleton('admin/session')->isAllowed('lengowtab/order');
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
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector');
        // if global error return this
        if (isset($results['error'][0])) {
            $messages[] = $helper->decodeLogMessage($results['error'][0]);
            return $messages;
        }
        if (isset($results['order_new']) && $results['order_new'] > 0) {
            $messages[] = $helper->__(
                'lengow_log.error.nb_order_imported',
                array('nb_order' => $results['order_new'])
            );
        }
        if (isset($results['order_update']) && $results['order_update'] > 0) {
            $messages[] = $helper->__(
                'lengow_log.error.nb_order_updated',
                array('nb_order' => $results['order_update'])
            );
        }
        if (isset($results['order_error']) && $results['order_error'] > 0) {
            $messages[] = $helper->__(
                'lengow_log.error.nb_order_with_error',
                array('nb_order' => $results['order_error'])
            );
        }
        if (empty($messages)) {
            $messages[] = $helper->__('lengow_log.error.no_notification');
        }
        if (isset($results['error'])) {
            foreach ($results['error'] as $storeId => $values) {
                if ((int)$storeId > 0) {
                    $store = Mage::getModel('core/store')->load($storeId);
                    $storeName = $store->getName() . ' (' . $store->getId() . ') : ';
                    $messages[] = $storeName . $helper->decodeLogMessage($values);
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
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector');
        $lastImport = Mage::helper('lengow_connector/import')->getLastImport();
        $lastImportDate = $helper->getDateInCorrectFormat(time());
        /** @var Lengow_Connector_Model_Import_Order $order */
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
                array('last_importation' => '<b>' . $lastImportDate . '</b>')
            );
        } else {
            $informations['last_importation'] = $helper->__('order.screen.no_order_importation');
        }
        return $informations;
    }
}
