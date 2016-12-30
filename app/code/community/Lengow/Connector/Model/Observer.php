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
 * @subpackage  Model
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Model observer
 */
class Lengow_Connector_Model_Observer
{
    /**
     * @var array path for Lengow options
     */
    protected $_lengowOptions = array(
        'lengow_global_options',
        'lengow_export_options',
        'lengow_import_options'
    );

    /**
     * @var array excludes attributes for export
     */
    protected $_excludeOptions = array(
        'import_in_progress',
        'last_import_manual',
        'last_import_cron',
        'export_last_export',
        'last_statistic_update',
        'order_statistic',
        'see_migrate_block',
        'last_status_update',
        'account_status',
        'last_option_cms_update',
    );

    /**
     * @var array order already shipped
     */
    protected $_alreadyShipped = array();

    /**
     * Display Lengow Menu on demand
     */
    public function updateAdminMenu()
    {
        if (Mage::helper('lengow_connector/data')->lengowIsInstalled()) {
            $isNewMerchant = Mage::helper('lengow_connector/sync')->isNewMerchant();
            $isStatus = Mage::helper('lengow_connector/sync')->getStatusAccount();
            if ($isNewMerchant
                || ($isStatus['type'] == 'free_trial' && $isStatus['day'] == 0)
                || $isStatus['type'] == 'bad_payer'
            ) {
                $updateValue = 1;
            } else {
                $updateValue = 0;
            }
            $menu = Mage::getSingleton('admin/config')->getAdminhtmlConfig()->getNode('menu/lengowtab/children');
            foreach ($menu->children() as $child) {
                $child->setNode('disabled', $updateValue);
            }
            // Clean config cache to valid configuration
            Mage::app()->getCache()->clean(
                Zend_Cache::CLEANING_MODE_MATCHING_TAG,
                array(Mage_Adminhtml_Block_Page_Menu::CACHE_TAGS)
            );
        }
    }

    /**
     * Save Change on lengow data
     *
     * @param Varien_Event_Observer $observer Magento varien event observer instance
     */
    public function onAfterSave(Varien_Event_Observer $observer)
    {
        $object = $observer->getEvent()->getObject();
        if (is_a($object, 'Mage_Core_Model_Config_Data')) {
            $pathExplode = explode("/", $object['path']);
            if (isset($pathExplode[0]) && in_array($pathExplode[0], $this->_lengowOptions)) {
                if ($object['scope'] == 'stores' || $object['scope'] == 'default') {
                    $oldValue = Mage::getStoreConfig($object['path'], $object['scope_id']);
                    $value = $object['value'];
                    if ($oldValue != $value && !in_array($pathExplode[2], $this->_excludeOptions)) {
                        if ($pathExplode[2] == 'global_access_token' || $pathExplode[2] == 'global_secret_token') {
                            $newValue = preg_replace("/[a-zA-Z0-9]/", '*', $value);
                            $oldValue = preg_replace("/[a-zA-Z0-9]/", '*', $oldValue);
                        } else {
                            $newValue = $value;
                        }
                        if ($object['scope'] == 'stores') {
                            $message = Mage::helper('lengow_connector/translation')->t(
                                'log.setting.setting_change_for_store',
                                array(
                                    'key'       => $object['path'],
                                    'old_value' => $oldValue,
                                    'value'     => $newValue,
                                    'store_id'  => $object['scope_id']
                                )
                            );
                        } else {
                            $message = Mage::helper('lengow_connector/translation')->t(
                                'log.setting.setting_change',
                                array(
                                    'key'       => $object['path'],
                                    'old_value' => $oldValue,
                                    'value'     => $newValue,
                                )
                            );
                        }
                        Mage::helper('lengow_connector')->log('Config', $message);
                    }
                }
            }
        }
    }

    /**
     * Sending a call WSDL for a new order shipment
     *
     * @param Varien_Event_Observer $observer Magento varien event observer instance
     *
     * @return Lengow_Connector_Model_Observer
     */
    public function salesOrderShipmentSaveAfter(Varien_Event_Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();
        if ($order->getData('from_lengow') == 1
            && Mage::getSingleton('core/session')->getCurrentOrderLengow() != $order->getData('order_id_lengow')
            && !array_key_exists($order->getData('order_id_lengow'), $this->_alreadyShipped)
        ) {
            $orderLengow = Mage::getModel('lengow/import_order');
            $orderLengow->callAction('ship', $order, $shipment);
            $this->_alreadyShipped[$order->getData('order_id_lengow')] = true;
        }
        return $this;
    }

    /**
     * Sending a call WSDL for a new tracking
     *
     * @param Varien_Event_Observer $observer Magento varien event observer instance
     *
     * @return Lengow_Connector_Model_Observer
     */
    public function salesOrderShipmentTrackSaveAfter(Varien_Event_Observer $observer)
    {
        $track = $observer->getEvent()->getTrack();
        $shipment = $track->getShipment();
        $order = $shipment->getOrder();
        if ($order->getData('from_lengow') == 1
            && Mage::getSingleton('core/session')->getCurrentOrderLengow() != $order->getData('order_id_lengow')
            && !array_key_exists($order->getData('order_id_lengow'), $this->_alreadyShipped)
        ) {
            $orderLengow = Mage::getModel('lengow/import_order');
            $orderLengow->callAction('ship', $order, $shipment);
            $this->_alreadyShipped[$order->getData('order_id_lengow')] = true;
        }
        return $this;
    }

    /**
     * Sending a call for a cancellation of order
     *
     * @param Varien_Event_Observer $observer Magento varien event observer instance
     *
     * @return Lengow_Connector_Model_Observer
     */
    public function salesOrderPaymentCancel(Varien_Event_Observer $observer)
    {
        $payment = $observer->getEvent()->getPayment();
        $order = $payment->getOrder();
        if ($order->getData('from_lengow') == 1
            && Mage::getSingleton('core/session')->getCurrentOrderLengow() != $order->getData('order_id_lengow')
        ) {
            $orderLengow = Mage::getModel('lengow/import_order');
            $orderLengow->callAction('cancel', $order);
        }
        return $this;
    }

    /**
     * Exports products for each store with cron job
     *
     * @return Lengow_Connector_Model_Observer
     */
    public function exportCron()
    {
        $config = Mage::helper('lengow_connector/config');
        if ((bool)$config->get('export_cron_enable')) {
            set_time_limit(0);
            ini_set('memory_limit', '1G');
            $storeCollection = Mage::getResourceModel('core/store_collection')->addFieldToFilter('is_active', 1);
            foreach ($storeCollection as $store) {
                $storeId = (int)$store->getId();
                if ($config->get('store_enable', $storeId)) {
                    try {
                        // config store
                        Mage::app()->getStore()->setCurrentStore($storeId);
                        // launch export process
                        $export = Mage::getModel(
                            'lengow/export',
                            array(
                                'store_id'           => $storeId,
                                'stream'             => false,
                                'update_export_date' => false,
                                'type'               => 'magento cron'
                            )
                        );
                        $export->exec();
                    } catch (Exception $e) {
                        $errorMessage = '[Magento error] "'.$e->getMessage().'" '.$e->getFile().' line '.$e->getLine();
                        Mage::helper('lengow_connector')->log('Export', $errorMessage);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Imports orders for each store with cron job
     *
     * @return Lengow_Connector_Model_Observer
     */
    public function importCron()
    {
        $config = Mage::helper('lengow_connector/config');
        if ((bool)$config->get('import_cron_enable')) {
            // sync orders between Lengow and Magento
            $import = Mage::getModel('lengow/import', array('type' => 'magento cron'));
            $import->exec();
            // sync action between Lengow and Magento
            $action = Mage::getModel('lengow/import_action');
            $action->checkFinishAction();
            $action->checkActionNotSent();
            // sync options between Lengow and Magento
            Mage::helper('lengow_connector/sync')->setCmsOption();
        }
        return $this;
    }
}
