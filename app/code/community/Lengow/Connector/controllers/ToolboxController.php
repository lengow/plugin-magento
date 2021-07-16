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
 * ToolboxController
 */
class Lengow_Connector_ToolboxController extends Mage_Core_Controller_Front_Action
{
    /**
     * Get all plugin data for toolbox
     */
    public function indexAction()
    {
        /**
         * List params
         * string toolbox_action toolbox specific action
         * string type           type of data to display
         * string date           date of the log to export
         */
        $token = $this->getRequest()->getParam(Lengow_Connector_Helper_Toolbox::PARAM_TOKEN);
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector');
        /** @var Lengow_Connector_Helper_Security $securityHelper */
        $securityHelper = Mage::helper('lengow_connector/security');
        /** @var Lengow_Connector_Helper_Toolbox $toolboxHelper */
        $toolboxHelper = Mage::helper('lengow_connector/toolbox');
        if ($securityHelper->checkWebserviceAccess($token)) {
            // check if toolbox action is valid
            $action = $this->getRequest()->getParam(
                Lengow_Connector_Helper_Toolbox::PARAM_TOOLBOX_ACTION
            ) ?: Lengow_Connector_Helper_Toolbox::ACTION_DATA;
            if ($toolboxHelper->isToolboxAction($action)) {
                switch ($action) {
                    case Lengow_Connector_Helper_Toolbox::ACTION_LOG:
                        $date = $this->getRequest()->getParam(Lengow_Connector_Helper_Toolbox::PARAM_DATE);
                        $toolboxHelper->downloadLog($date);
                        break;
                    default:
                        $type = $this->getRequest()->getParam(Lengow_Connector_Helper_Toolbox::PARAM_TYPE);
                        $this->getResponse()->setBody(json_encode($toolboxHelper->getData($type)));
                }
            } else {
                $this->getResponse()->setHeader('HTTP/1.1', '400 Bad Request');
                $this->getResponse()->setBody($helper->__('log.import.not_valid_action', array('action' => $action)));
            }
        } else {
            if (Mage::helper('lengow_connector/config')->get(Lengow_Connector_Helper_Config::AUTHORIZED_IP_ENABLED)) {
                $errorMessage = $helper->__(
                    'log.export.unauthorised_ip',
                    array('ip' => $securityHelper->getRemoteIp())
                );
            } else {
                $errorMessage = $token !== ''
                    ? $helper->__('log.export.unauthorised_token', array('token' => $token))
                    : $helper->__('log.export.empty_token');
            }
            $this->getResponse()->setHeader('HTTP/1.1', '403 Forbidden');
            $this->getResponse()->setBody($errorMessage);
        }
    }
}
