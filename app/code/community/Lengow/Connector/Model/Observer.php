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
        $menu = Mage::getSingleton('admin/config')->getAdminhtmlConfig()->getNode('menu/lengowtab/children');
        foreach ($menu->children() as $childName => $child) {
            $menu->setNode($childName.'/disabled', '0');
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
            $pathExplode = explode("/", $object['path']);
            if (isset($pathExplode[0]) && in_array($pathExplode[0], $this->_lengow_options)) {
                if ($object['scope'] == 'stores' || $object['scope'] == 'default') {
                    $oldValue = Mage::getStoreConfig($object['path'], $object['scope_id']);
                    if ($oldValue != $object['value'] && !in_array($pathExplode[2], $this->_exclude_options)) {
                        if ($object['scope'] == 'stores') {
                            $message = Mage::helper('lengow_connector/translation')->t(
                                'log.setting.setting_change_for_shop',
                                array(
                                    'key'       => $object['path'],
                                    'old_value' => $oldValue,
                                    'value'     => $object['value'],
                                    'shop_id'   => $object['scope_id']
                                )
                            );
                        } else {
                            $message = Mage::helper('lengow_connector/translation')->t(
                                'log.setting.setting_change',
                                array(
                                    'key'       => $object['path'],
                                    'old_value' => $oldValue,
                                    'value'     => $object['value'],
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
        if ($order->getData('from_lengow') == 1
            && Mage::getSingleton('core/session')->getCurrentOrderLengow() != $order->getData('order_id_lengow')
        ) {
            $order_lengow = Mage::getModel('lengow/import_order');
            $order_lengow->callAction('cancel', $order);
        }
        return $this;
    }
}
