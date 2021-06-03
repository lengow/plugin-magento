<?php
/**
 * Copyright 2021 Lengow SAS
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
 * @copyright   2021 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml lengow dashboardController
 */
class Lengow_Connector_Adminhtml_Lengow_DashboardController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Init action
     *
     * @return Lengow_Connector_Adminhtml_Lengow_DashboardController
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
        if (Mage::helper('lengow_connector/config')->isNewMerchant()) {
            $this->_redirect('adminhtml/lengow_home/index');
        } else {
            if ($this->getRequest()->getParam('isAjax')) {
                $action = Mage::app()->getRequest()->getParam('action');
                if ($action) {
                    switch ($action) {
                        case 'remind_me_later':
                            $timestamp = time() + (7 * 86400);
                            Mage::helper('lengow_connector/config')->set(
                                Lengow_Connector_Helper_Config::LAST_UPDATE_PLUGIN_MODAL,
                                $timestamp
                            );
                            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('success' => true)));
                            break;
                    }
                }
            } else {
                $this->_initAction()->renderLayout();
            }
        }
    }

    /**
     * Is allowed
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('lengowtab/home');
    }

    /**
     * Refresh account status
     */
    public function refreshAction()
    {
        Mage::helper('lengow_connector/sync')->getStatusAccount(true);
        Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl('adminhtml/lengow_home'));
    }
}
