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
 * Adminhtml lengow homeController
 */
class Lengow_Connector_Adminhtml_Lengow_HomeController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Init action
     *
     * @return Lengow_Connector_Adminhtml_Lengow_HomeController
     */
    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu('lengowtab');
        return $this;
    }

    /**
     * Index Action
     *
     * @return mixed
     */
    public function indexAction()
    {
        $isAjax = Mage::app()->getRequest()->isAjax();
        if ($isAjax) {
            $action = (string)$this->getRequest()->getParam('action');
            if (strlen($action) > 0) {
                switch ($action) {
                    case "get_sync_data":
                        $data = array();
                        $data['function'] = 'sync';
                        $data['parameters'] = Mage::helper('lengow_connector/sync')->getSyncData();
                        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($data));
                        break;
                    case "sync":
                        $data = $this->getRequest()->getParam('data', 0);
                        Mage::helper('lengow_connector/sync')->sync($data);
                        Mage::helper('lengow_connector/sync')->getStatusAccount(true);
                        break;
                }
            }
        } else {
            $this->_initAction()->renderLayout();
        }
        return $this;
    }

    /**
     * Is allowed
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('lengow_connector/home');
    }

    /**
     * Refresh account status
     */
    public function refreshAction()
    {
        Mage::helper('lengow_connector/sync')->getStatusAccount(true);
        Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/lengow_home"));
    }
}
