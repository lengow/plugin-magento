<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Adminhtml_Lengow_HelpController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu('lengowtab');
        return $this;
    }

    public function indexAction()
    {
        $isAjax = Mage::app()->getRequest()->isAjax();
        if ($isAjax) {
            $action = (string)$this->getRequest()->getParam('action');
            if (strlen($action)>0) {
                switch ($action) {
                    case "get_sync_data":
                        $data = array();
                        $data['function'] = 'sync';
                        $data['parameters'] =  Mage::helper('lengow_connector/sync')->getSyncData();
                        echo json_encode($data);
                        break;
                }
            }
        } else {
            $this->_initAction()->renderLayout();
        }
        return $this;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('lengow_connector/help');
    }
}
