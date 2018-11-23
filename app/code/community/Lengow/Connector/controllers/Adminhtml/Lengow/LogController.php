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
 * Adminhtml lengow logController
 */
class Lengow_Connector_Adminhtml_Lengow_LogController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Init action
     *
     * @return Lengow_Connector_Adminhtml_Lengow_LogController
     */
    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu('lengowtab');
        return $this;
    }

    /**
     * Index Action
     *
     * @return Lengow_Connector_Adminhtml_Lengow_LogController
     */
    public function indexAction()
    {
        $this->_initAction()->renderLayout();
        return $this;
    }

    /**
     * Cron grid for AJAX request
     */
    public function gridAction()
    {
        $this->getResponse()->setBody($this->getLayout()->createBlock('lengow/adminhtml_log_grid')->toHtml());
    }

    /**
     * Is allowed
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('lengowtab/log');
    }
}
