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
     *
     * List params
     * string  toolbox_action   Toolbox specific action
     * string  type             Type of data to display
     * string  created_from     Synchronization of orders since
     * string  created_to       Synchronization of orders until
     * string  date             Log date to download
     * string  marketplace_name Lengow marketplace name to synchronize
     * string  marketplace_sku  Lengow marketplace order id to synchronize
     * string  process          Type of process for order action
     * boolean force            Force synchronization order even if there are errors (1) or not (0)
     * integer shop_id          Shop id to synchronize
     * integer days             Synchronization interval time
     */
    public function indexAction()
    {
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
                Lengow_Connector_Helper_Toolbox::PARAM_TOOLBOX_ACTION,
                Lengow_Connector_Helper_Toolbox::ACTION_DATA
            );
            if ($toolboxHelper->isToolboxAction($action)) {
                switch ($action) {
                    case Lengow_Connector_Helper_Toolbox::ACTION_LOG:
                        $date = $this->getRequest()->getParam(Lengow_Connector_Helper_Toolbox::PARAM_DATE);
                        $toolboxHelper->downloadLog($date);
                        break;
                    case Lengow_Connector_Helper_Toolbox::ACTION_ORDER:
                        $result = $toolboxHelper->syncOrders(
                            array(
                                Lengow_Connector_Helper_Toolbox::PARAM_CREATED_TO => $this->getRequest()
                                    ->getParam(Lengow_Connector_Helper_Toolbox::PARAM_CREATED_TO),
                                Lengow_Connector_Helper_Toolbox::PARAM_CREATED_FROM => $this->getRequest()
                                    ->getParam(Lengow_Connector_Helper_Toolbox::PARAM_CREATED_FROM),
                                Lengow_Connector_Helper_Toolbox::PARAM_DAYS => $this->getRequest()
                                    ->getParam(Lengow_Connector_Helper_Toolbox::PARAM_DAYS),
                                Lengow_Connector_Helper_Toolbox::PARAM_FORCE => $this->getRequest()
                                    ->getParam(Lengow_Connector_Helper_Toolbox::PARAM_FORCE),
                                Lengow_Connector_Helper_Toolbox::PARAM_MARKETPLACE_NAME => $this->getRequest()
                                    ->getParam(Lengow_Connector_Helper_Toolbox::PARAM_MARKETPLACE_NAME),
                                Lengow_Connector_Helper_Toolbox::PARAM_MARKETPLACE_SKU => $this->getRequest()
                                    ->getParam(Lengow_Connector_Helper_Toolbox::PARAM_MARKETPLACE_SKU),
                                Lengow_Connector_Helper_Toolbox::PARAM_SHOP_ID => $this->getRequest()
                                    ->getParam(Lengow_Connector_Helper_Toolbox::PARAM_SHOP_ID),
                            )
                        );
                        if (isset($result[Lengow_Connector_Helper_Toolbox::ERRORS][
                            Lengow_Connector_Helper_Toolbox::ERROR_CODE
                        ])) {
                            $errorCode = $result[Lengow_Connector_Helper_Toolbox::ERRORS][
                                Lengow_Connector_Helper_Toolbox::ERROR_CODE
                            ];
                            if ($errorCode === Lengow_Connector_Model_Connector::CODE_404) {
                                $this->getResponse()->setHeader('HTTP/1.1', '404 Not Found');
                            } else {
                                $this->getResponse()->setHeader('HTTP/1.1', '403 Forbidden');
                            }
                        }
                        $this->getResponse()->setBody(json_encode($result));
                        break;
                    default:
                        $type = $this->getRequest()->getParam(
                            Lengow_Connector_Helper_Toolbox::PARAM_TYPE,
                            Lengow_Connector_Helper_Toolbox::DATA_TYPE_CMS
                        );
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
