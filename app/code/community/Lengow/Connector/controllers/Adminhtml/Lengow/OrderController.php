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
        $params['type'] = 'manual';
        // Import orders
        $import = Mage::getModel('lengow/import', $params);
        $results = $import->exec();
        if ($results['order_new'] > 0) {
            $this->_getSession()->addSuccess($results['order_new'].'orders new');
        }
        if ($results['order_update'] > 0) {
            $this->_getSession()->addSuccess($results['order_update'].'orders updated');
        }
        if ($results['order_error'] > 0) {
            $this->_getSession()->addSuccess($results['order_error'].'orders with error');
        }
        if ($results['order_new'] == 0 && $results['order_new'] == 0 && $results['order_error'] == 0) {
            $this->_getSession()->addSuccess('No order available to import');
        }
        $this->_redirect('*/*/index');
    }

    public function migrateAction()
    {
        $order = Mage::getModel('lengow/import_order');
        $order->migrateOldOrder();
        $this->_redirect('adminhtml/lengow_order/index');
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
