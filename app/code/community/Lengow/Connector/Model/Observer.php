<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Model_Observer
{
    /**
     * Path for Lengow options
     */
    protected $_lengow_options = array(
        'lengow_global_options',
        'lengow_export_options',
        'lengow_import_options'
    );

    /**
     * Excludes attributes for export
     */
    protected $_exclude_options = array(
        'import_in_progress',
        'last_import_manual',
        'last_import_cron',
        'export_last_export',
        'last_statistic_update',
        'order_statistic',
        'see_migrate_block',
    );

    /**
     * Order already shipped
     */
    protected $_alreadyShipped = array();

    /**
     * Display Lengow Menu on demand
     */
    public function updateAdminMenu()
    {
        $is_new_merchant = Mage::helper('lengow_connector/sync')->isNewMerchant();
        $update_value = $is_new_merchant ? 1 : 0;
        $menu = Mage::getSingleton('admin/config')->getAdminhtmlConfig()->getNode('menu/lengowtab/children');
        foreach ($menu->children() as $childName => $child) {
            $menu->setNode($childName.'/disabled', $update_value);
        }
    }

    /**
     * Save Change on lengow data
     *
     * @param $observer
     */
    public function onAfterSave(Varien_Event_Observer $observer)
    {
        $object = $observer->getEvent()->getObject();
        if (is_a($object, 'Mage_Core_Model_Config_Data')) {
            $path_explode = explode("/", $object['path']);
            if (isset($path_explode[0]) && in_array($path_explode[0], $this->_lengow_options)) {
                if ($object['scope'] == 'stores' || $object['scope'] == 'default') {
                    $old_value = Mage::getStoreConfig($object['path'], $object['scope_id']);
                    $value = $object['value'];
                    if ($old_value != $value && !in_array($path_explode[2], $this->_exclude_options)) {
                        if ($path_explode[2] == 'global_access_token' || $path_explode[2] == 'global_secret_token') {
                            $value = preg_replace("/[a-zA-Z0-9]/", '*', $value);
                            $old_value = preg_replace("/[a-zA-Z0-9]/", '*', $old_value);
                        }
                        if ($object['scope'] == 'stores') {
                            $message = Mage::helper('lengow_connector/translation')->t(
                                'log.setting.setting_change_for_store',
                                array(
                                    'key'       => $object['path'],
                                    'old_value' => $old_value,
                                    'value'     => $value,
                                    'store_id'  => $object['scope_id']
                                )
                            );
                        } else {
                            $message = Mage::helper('lengow_connector/translation')->t(
                                'log.setting.setting_change',
                                array(
                                    'key'       => $object['path'],
                                    'old_value' => $old_value,
                                    'value'     => $value,
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
     * @param $observer
     */
    public function salesOrderShipmentSaveAfter(Varien_Event_Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();
        $helper = Mage::helper('lengow_connector');
        if ($order->getData('from_lengow') == 1
            && Mage::getSingleton('core/session')->getCurrentOrderLengow() != $order->getData('order_id_lengow')
            && !array_key_exists($order->getData('order_id_lengow'), $this->_alreadyShipped)
        ) {
            $order_lengow = Mage::getModel('lengow/import_order');
            $order_lengow->callAction('ship', $order, $shipment);
            $this->_alreadyShipped[$order->getData('order_id_lengow')] = true;
        }
        return $this;
    }

    /**
     * Sending a call WSDL for a new tracking
     *
     * @param $observer
     */
    public function salesOrderShipmentTrackSaveAfter(Varien_Event_Observer $observer)
    {
        $track = $observer->getEvent()->getTrack();
        $shipment = $track->getShipment();
        $order = $shipment->getOrder();
        $helper = Mage::helper('lengow_connector');
        if ($order->getData('from_lengow') == 1
            && Mage::getSingleton('core/session')->getCurrentOrderLengow() != $order->getData('order_id_lengow')
            && !array_key_exists($order->getData('order_id_lengow'), $this->_alreadyShipped)
        ) {
            $order_lengow = Mage::getModel('lengow/import_order');
            $order_lengow->callAction('ship', $order, $shipment);
            $this->_alreadyShipped[$order->getData('order_id_lengow')] = true;
        }
        return $this;
    }

    /**
     * Sending a call for a cancellation of order
     *
     * @param $observer
     */
    public function salesOrderPaymentCancel(Varien_Event_Observer $observer)
    {
        $payment = $observer->getEvent()->getPayment();
        $order = $payment->getOrder();
        $helper = Mage::helper('lengow_connector');
        if ($order->getData('from_lengow') == 1
            && Mage::getSingleton('core/session')->getCurrentOrderLengow() != $order->getData('order_id_lengow')
        ) {
            $order_lengow = Mage::getModel('lengow/import_order');
            $order_lengow->callAction('cancel', $order);
        }
        return $this;
    }

    /**
     * Exports products for each store with cron job
     *
     * @param $observer
     */
    public function exportCron(Varien_Event_Observer $observer)
    {
        $config = Mage::helper('lengow_connector/config');
        if ((bool)$config->get('export_cron_enable')) {
            set_time_limit(0);
            ini_set('memory_limit', '1G');
            $store_collection = Mage::getResourceModel('core/store_collection')->addFieldToFilter('is_active', 1);
            foreach ($store_collection as $store) {
                $store_id = (int)$store->getId();
                if ($config->get('store_enable', $store_id)) {
                    try {
                        // config store
                        Mage::app()->getStore()->setCurrentStore($store_id);
                        // launch export process
                        $export = Mage::getModel('lengow/export', array(
                            'store_id'           => $store_id,
                            'stream'             => false,
                            'update_export_date' => false,
                            'type'               => 'magento cron'
                        ));
                        $export->exec();
                    } catch (Exception $e) {
                        $error_message = '[Magento error] "'.$e->getMessage().'" '.$e->getFile().' line '.$e->getLine();
                        Mage::helper('lengow_connector')->log('Export', $error_message);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Imports orders for each store with cron job
     *
     * @param $observer
     */
    public function importCron(Varien_Event_Observer $observer)
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
            $options = Mage::helper('core')->jsonEncode(Mage::helper('lengow_connector/sync')->getOptionData());
            $connector = Mage::getModel('lengow/connector');
            $result = $connector->queryApi('put', '/v3.0/cms', null, array(), $options);
        }
        return $this;
    }
}
