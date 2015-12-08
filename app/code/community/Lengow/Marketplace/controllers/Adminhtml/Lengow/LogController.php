<?php

/**
 * Lengow adminhtml log controller.
 *
 * @category    Lengow
 *
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Marketplace_Adminhtml_Lengow_LogController extends Mage_Adminhtml_Controller_Action
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

    public function deleteAction()
    {
        $collection = Mage::getModel('lengow/log')->getCollection();
        foreach ($collection as $log) {
            $log->delete();
        }
        $this->_getSession()->addSuccess($this->__('Log is empty'));
        $this->_redirect('*/*/index');
    }

    public function gridAction()
    {
        $this->getResponse()->setBody($this->getLayout()->createBlock('lengow/adminhtml_log_grid')->toHtml());
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('lengow_marketplace/log');
    }
}
