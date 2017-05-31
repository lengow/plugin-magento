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
     */
    public function indexAction()
    {
        /**
         * List params
         * string  sync                Number of products exported
         * integer days                Import period
         * integer limit               Number of orders to import
         * integer store_id            Store id to import
         * string  $marketplace_sku    Lengow marketplace order id to import
         * string  marketplace_name    Lengow marketplace name to import
         * integer delivery_address_id Lengow delivery address id to import
         * boolean preprod_mode        Activate preprod mode
         * boolean log_output          See logs (1) or not (0)
         * boolean get_sync            See synchronisation parameters in json format (1) or not (0)
         */
        $token = $this->getRequest()->getParam('token');
        $securityHelper = Mage::helper('lengow_connector/security');
        if ($securityHelper->checkWebserviceAccess($token)) {
            // get all store datas for synchronisation with Lengow
            if ($this->getRequest()->getParam('get_sync') == 1) {
                $storeDatas = Mage::helper('lengow_connector/sync')->getSyncData();
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($storeDatas));
            } else {
                // get sync action if exists
                $sync = $this->getRequest()->getParam('sync');
                // sync orders between Lengow and Magento
                if (is_null($sync) || $sync === 'order') {
                    // array of params for import order
                    $params = array();
                    // check if the GET parameters are availables
                    if (!is_null($this->getRequest()->getParam('preprod_mode'))) {
                        $params['preprod_mode'] = (bool)$this->getRequest()->getParam('preprod_mode');
                    }
                    if (!is_null($this->getRequest()->getParam('log_output'))) {
                        $params['log_output'] = (bool)$this->getRequest()->getParam('log_output');
                    }
                    if (!is_null($this->getRequest()->getParam('days'))) {
                        $params['days'] = (int)$this->getRequest()->getParam('days');
                    }
                    if (!is_null($this->getRequest()->getParam('limit'))) {
                        $params['limit'] = (int)$this->getRequest()->getParam('limit');
                    }
                    if (!is_null($this->getRequest()->getParam('marketplace_sku'))) {
                        $params['marketplace_sku'] = (string)$this->getRequest()->getParam('marketplace_sku');
                    }
                    if (!is_null($this->getRequest()->getParam('marketplace_name'))) {
                        $params['marketplace_name'] = (string)$this->getRequest()->getParam('marketplace_name');
                    }
                    if (!is_null($this->getRequest()->getParam('delivery_address_id'))) {
                        $params['delivery_address_id'] = (int)$this->getRequest()->getParam('delivery_address_id');
                    }
                    if (!is_null($this->getRequest()->getParam('store_id'))) {
                        $params['store_id'] = (int)$this->getRequest()->getParam('store_id');
                    }
                    $params['type'] = 'cron';
                    // Import orders
                    $import = Mage::getModel('lengow/import', $params);
                    $import->exec();
                }
                // sync action between Lengow and Magento
                if (is_null($sync) || $sync === 'action') {
                    $action = Mage::getModel('lengow/import_action');
                    $action->checkFinishAction();
                    $action->checkActionNotSent();
                }
                // sync options between Lengow and Magento
                if (is_null($sync) || $sync === 'option') {
                    Mage::helper('lengow_connector/sync')->setCmsOption();
                }
                // sync option is not valid
                if ($sync && ($sync !== 'order' && $sync !== 'action' && $sync !== 'option')) {
                    $this->getResponse()->setHeader('HTTP/1.1', '400 Bad Request');
                    $this->getResponse()->setBody(
                        Mage::helper('lengow_connector')->__('log.import.not_valid_action', array('action' => $sync))
                    );
                }
            }
        } else {
            $dataHelper = Mage::helper('lengow_connector');
            if ((bool)Mage::helper('lengow_connector/config')->get('ip_enable')) {
                $errorMessage = $dataHelper->__(
                    'log.export.unauthorised_ip',
                    array('ip' => $securityHelper->getRemoteIp())
                );
            } else {
                $errorMessage = strlen($token) > 0
                    ? $dataHelper->__('log.export.unauthorised_token', array('token' => $token))
                    : $dataHelper->__('log.export.empty_token');
            }
            $this->getResponse()->setHeader('HTTP/1.1', '403 Forbidden');
            $this->getResponse()->setBody($errorMessage);
        }
    }
}
