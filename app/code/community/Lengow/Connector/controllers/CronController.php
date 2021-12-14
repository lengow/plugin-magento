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
 * CronController
 */
class Lengow_Connector_CronController extends Mage_Core_Controller_Front_Action
{
    /**
     * Cron Process (Import orders, check actions and send stats)
     *
     * List params
     * string  sync                Data type to synchronize
     * integer days                Synchronization interval time
     * integer limit               Maximum number of new orders created
     * integer store_id            Store id to synchronize
     * string  marketplace_sku     Lengow marketplace order id to synchronize
     * string  marketplace_name    Lengow marketplace name to synchronize
     * string  created_from        Synchronization of orders since
     * string  created_to          Synchronization of orders until
     * integer delivery_address_id Lengow delivery address id to synchronize
     * boolean debug_mode          Activate debug mode
     * boolean log_output          See logs (1) or not (0)
     * boolean get_sync            See synchronization parameters in json format (1) or not (0)
     */
    public function indexAction()
    {
        $token = $this->getRequest()->getParam(Lengow_Connector_Model_Import::PARAM_TOKEN);
        /** @var Lengow_Connector_Helper_Security $securityHelper */
        $securityHelper = Mage::helper('lengow_connector/security');
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector');
        if ($securityHelper->checkWebserviceAccess($token)) {
            /** @var Lengow_Connector_Helper_Sync $syncHelper */
            $syncHelper = Mage::helper('lengow_connector/sync');
            // get all store data for synchronisation with Lengow
            if ($this->getRequest()->getParam(Lengow_Connector_Model_Import::PARAM_GET_SYNC) === '1') {
                $storeData = $syncHelper->getSyncData();
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($storeData));
            } else {
                $force = $this->getRequest()->getParam(Lengow_Connector_Model_Import::PARAM_FORCE) === '1';
                $logOutput = $this->getRequest()->getParam(Lengow_Connector_Model_Import::PARAM_LOG_OUTPUT) === '1';
                // get sync action if exists
                $sync = $this->getRequest()->getParam(Lengow_Connector_Model_Import::PARAM_SYNC);
                // sync catalogs id between Lengow and Magento
                if (!$sync || $sync === Lengow_Connector_Helper_Sync::SYNC_CATALOG) {
                    $syncHelper->syncCatalog($force, $logOutput);
                }
                // sync orders between Lengow and Magento
                if ($sync === null || $sync === Lengow_Connector_Helper_Sync::SYNC_ORDER) {
                    // array of params for import order
                    $params = array(
                        Lengow_Connector_Model_Import::PARAM_TYPE => Lengow_Connector_Model_Import::TYPE_CRON,
                        Lengow_Connector_Model_Import::PARAM_LOG_OUTPUT => $logOutput,
                    );
                    // check if the GET parameters are available
                    if ($this->getRequest()->getParam(Lengow_Connector_Model_Import::PARAM_FORCE_SYNC) !== null) {
                        $params[Lengow_Connector_Model_Import::PARAM_FORCE_SYNC] = (bool) $this->getRequest()
                            ->getParam(Lengow_Connector_Model_Import::PARAM_FORCE_SYNC);
                    }
                    if ($this->getRequest()->getParam(Lengow_Connector_Model_Import::PARAM_DEBUG_MODE) !== null) {
                        $params[Lengow_Connector_Model_Import::PARAM_DEBUG_MODE] = (bool) $this->getRequest()
                            ->getParam(Lengow_Connector_Model_Import::PARAM_DEBUG_MODE);
                    }
                    if ($this->getRequest()->getParam(Lengow_Connector_Model_Import::PARAM_DAYS) !== null) {
                        $params[Lengow_Connector_Model_Import::PARAM_DAYS] = (int) $this->getRequest()
                            ->getParam(Lengow_Connector_Model_Import::PARAM_DAYS);
                    }
                    if ($this->getRequest()->getParam(Lengow_Connector_Model_Import::PARAM_CREATED_FROM) !== null) {
                        $params[Lengow_Connector_Model_Import::PARAM_CREATED_FROM] = $this->getRequest()
                            ->getParam(Lengow_Connector_Model_Import::PARAM_CREATED_FROM);
                    }
                    if ($this->getRequest()->getParam(Lengow_Connector_Model_Import::PARAM_CREATED_TO) !== null) {
                        $params[Lengow_Connector_Model_Import::PARAM_CREATED_TO] = $this->getRequest()
                            ->getParam(Lengow_Connector_Model_Import::PARAM_CREATED_TO);
                    }
                    if ($this->getRequest()->getParam(Lengow_Connector_Model_Import::PARAM_LIMIT) !== null) {
                        $params[Lengow_Connector_Model_Import::PARAM_LIMIT] = (int) $this->getRequest()
                            ->getParam(Lengow_Connector_Model_Import::PARAM_LIMIT);
                    }
                    if ($this->getRequest()->getParam(Lengow_Connector_Model_Import::PARAM_MARKETPLACE_SKU) !== null) {
                        $params[Lengow_Connector_Model_Import::PARAM_MARKETPLACE_SKU] = $this->getRequest()
                            ->getParam(Lengow_Connector_Model_Import::PARAM_MARKETPLACE_SKU);
                    }
                    if ($this->getRequest()->getParam(Lengow_Connector_Model_Import::PARAM_MARKETPLACE_NAME) !== null) {
                        $params[Lengow_Connector_Model_Import::PARAM_MARKETPLACE_NAME] = $this->getRequest()
                            ->getParam(Lengow_Connector_Model_Import::PARAM_MARKETPLACE_NAME);
                    }
                    if ($this->getRequest()
                            ->getParam(Lengow_Connector_Model_Import::PARAM_DELIVERY_ADDRESS_ID) !== null
                    ) {
                        $params[Lengow_Connector_Model_Import::PARAM_DELIVERY_ADDRESS_ID] = (int) $this->getRequest()
                            ->getParam(Lengow_Connector_Model_Import::PARAM_DELIVERY_ADDRESS_ID);
                    }
                    if ($this->getRequest()->getParam(Lengow_Connector_Model_Import::PARAM_STORE_ID) !== null) {
                        $params[Lengow_Connector_Model_Import::PARAM_STORE_ID] = (int) $this->getRequest()
                            ->getParam(Lengow_Connector_Model_Import::PARAM_STORE_ID);
                    }
                    // synchronise orders
                    /** @var Lengow_Connector_Model_Import $import */
                    $import = Mage::getModel('lengow/import', $params);
                    $import->exec();
                }
                // sync action between Lengow and Magento
                if ($sync === null || $sync === Lengow_Connector_Helper_Sync::SYNC_ACTION) {
                    /** @var Lengow_Connector_Model_Import_Action $action */
                    $action = Mage::getModel('lengow/import_action');
                    $action->checkFinishAction($logOutput);
                    $action->checkOldAction($logOutput);
                    $action->checkActionNotSent($logOutput);
                }
                // sync options between Lengow and Magento
                if ($sync === null || $sync === Lengow_Connector_Helper_Sync::SYNC_CMS_OPTION) {
                    $syncHelper->setCmsOption($force, $logOutput);
                }
                // sync marketplaces between Lengow and Magento
                if ($sync === Lengow_Connector_Helper_Sync::SYNC_MARKETPLACE) {
                    $syncHelper->getMarketplaces($force, $logOutput);
                }
                // sync status account between Lengow and Magento
                if ($sync === Lengow_Connector_Helper_Sync::SYNC_STATUS_ACCOUNT) {
                    $syncHelper->getStatusAccount($force, $logOutput);
                }
                // sync plugin data between Lengow and Magento
                if ($sync === Lengow_Connector_Helper_Sync::SYNC_PLUGIN_DATA) {
                    $syncHelper->getPluginData($force, $logOutput);
                }
                // sync option is not valid
                if ($sync && !$syncHelper->isSyncAction($sync)) {
                    $this->getResponse()->setHeader('HTTP/1.1', '400 Bad Request');
                    $this->getResponse()->setBody(
                        $helper->__('log.import.not_valid_action', array('action' => $sync))
                    );
                }
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
