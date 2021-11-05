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
     * @var array order already shipped
     */
    protected $_alreadyShipped = array();

    /**
     * Save Change on lengow data
     *
     * @param Varien_Event_Observer $observer Magento Varien event observer instance
     */
    public function onAfterSave(Varien_Event_Observer $observer)
    {
        $object = $observer->getEvent()->getObject();
        if (is_a($object, 'Mage_Core_Model_Config_Data')) {
            /** @var Lengow_Connector_Model_Config $config */
            $config =  Mage::getModel('lengow/config');
            $config->checkAndLog($object['path'], $object['value'], $object['scope'], $object['scope_id']);
        }
    }

    /**
     * Sending a call WSDL for a new order shipment
     *
     * @param Varien_Event_Observer $observer Magento Varien event observer instance
     *
     * @return Lengow_Connector_Model_Observer
     */
    public function salesOrderShipmentSaveAfter(Varien_Event_Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();
        if ((bool) $order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_FROM_LENGOW)
            && !array_key_exists(
                $order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_MARKETPLACE_SKU),
                $this->_alreadyShipped
            )
            && Mage::getSingleton('core/session')->getCurrentOrderLengow() !== $order->getData(
                Lengow_Connector_Model_Import_Order::FIELD_LEGACY_MARKETPLACE_SKU
            )
        ) {
            /** @var Lengow_Connector_Model_Import_Order $orderLengow */
            $orderLengow = Mage::getModel('lengow/import_order');
            $orderLengow->callAction(Lengow_Connector_Model_Import_Action::TYPE_SHIP, $order, $shipment);
            $this->_alreadyShipped[
                $order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_MARKETPLACE_SKU)
            ] = true;
        }
        return $this;
    }

    /**
     * Sending a call WSDL for a new tracking
     *
     * @param Varien_Event_Observer $observer Magento Varien event observer instance
     *
     * @return Lengow_Connector_Model_Observer
     */
    public function salesOrderShipmentTrackSaveAfter(Varien_Event_Observer $observer)
    {
        $track = $observer->getEvent()->getTrack();
        $shipment = $track->getShipment();
        $order = $shipment->getOrder();
        if ((bool) $order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_FROM_LENGOW)
            && !array_key_exists(
                $order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_MARKETPLACE_SKU),
                $this->_alreadyShipped
            )
            && Mage::getSingleton('core/session')->getCurrentOrderLengow() !== $order->getData(
                Lengow_Connector_Model_Import_Order::FIELD_LEGACY_MARKETPLACE_SKU
            )
        ) {
            /** @var Lengow_Connector_Model_Import_Order $orderLengow */
            $orderLengow = Mage::getModel('lengow/import_order');
            $orderLengow->callAction(Lengow_Connector_Model_Import_Action::TYPE_SHIP, $order, $shipment);
            $this->_alreadyShipped[
                $order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_MARKETPLACE_SKU)
            ] = true;
        }
        return $this;
    }

    /**
     * Sending a call for a cancellation of order
     *
     * @param Varien_Event_Observer $observer Magento Varien event observer instance
     *
     * @return Lengow_Connector_Model_Observer
     */
    public function salesOrderPaymentCancel(Varien_Event_Observer $observer)
    {
        $payment = $observer->getEvent()->getPayment();
        $order = $payment->getOrder();
        if ((bool) $order->getData(Lengow_Connector_Model_Import_Order::FIELD_LEGACY_FROM_LENGOW)
            && Mage::getSingleton('core/session')->getCurrentOrderLengow() !== $order->getData(
                Lengow_Connector_Model_Import_Order::FIELD_LEGACY_MARKETPLACE_SKU
            )
        ) {
            /** @var Lengow_Connector_Model_Import_Order $orderLengow */
            $orderLengow = Mage::getModel('lengow/import_order');
            $orderLengow->callAction(Lengow_Connector_Model_Import_Action::TYPE_CANCEL, $order);
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
        set_time_limit(0);
        ini_set('memory_limit', '1G');
        /** @var Lengow_Connector_Helper_Config $configHelper */
        $configHelper = Mage::helper('lengow_connector/config');
        $storeCollection = Mage::getResourceModel('core/store_collection')->addFieldToFilter('is_active', 1);
        foreach ($storeCollection as $store) {
            $storeId = (int) $store->getId();
            if ($configHelper->get(Lengow_Connector_Helper_Config::EXPORT_MAGENTO_CRON_ENABLED, $storeId)) {
                try {
                    // config store
                    Mage::app()->getStore()->setCurrentStore($storeId);
                    $params = array(
                        Lengow_Connector_Model_Export::PARAM_STORE_ID => $storeId,
                        Lengow_Connector_Model_Export::PARAM_STREAM => false,
                        Lengow_Connector_Model_Export::PARAM_UPDATE_EXPORT_DATE => false,
                        Lengow_Connector_Model_Export::PARAM_LOG_OUTPUT => false,
                        Lengow_Connector_Model_Export::PARAM_TYPE => Lengow_Connector_Model_Export::TYPE_MAGENTO_CRON,
                    );
                    /** @var Lengow_Connector_Model_Export $export */
                    $export = Mage::getModel('lengow/export', $params);
                    $export->setOriginalCurrency(Mage::app()->getStore($storeId)->getCurrentCurrencyCode());
                    // launch export process
                    $export->exec();
                } catch (Exception $e) {
                    $errorMessage = '[Magento error]: "' . $e->getMessage()
                        . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
                    Mage::helper('lengow_connector')->log(Lengow_Connector_Helper_Data::CODE_EXPORT, $errorMessage);
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
        if ((bool) Mage::helper('lengow_connector/config')->get(
            Lengow_Connector_Helper_Config::SYNCHRONISATION_MAGENTO_CRON_ENABLED
        )) {
            /** @var Lengow_Connector_Helper_Sync $syncHelper */
            $syncHelper = Mage::helper('lengow_connector/sync');
            // sync catalogs id between Lengow and Magento
            $syncHelper->syncCatalog();
            // sync orders between Lengow and Magento
            /** @var Lengow_Connector_Model_Import $import */
            $import = Mage::getModel(
                'lengow/import',
                array(Lengow_Connector_Model_Import::PARAM_TYPE => Lengow_Connector_Model_Import::TYPE_MAGENTO_CRON)
            );
            $import->exec();
            // sync action between Lengow and Magento
            /** @var Lengow_Connector_Model_Import_Action $action */
            $action = Mage::getModel('lengow/import_action');
            $action->checkFinishAction();
            $action->checkOldAction();
            $action->checkActionNotSent();
            // sync options between Lengow and Magento
            $syncHelper->setCmsOption();
        }
        return $this;
    }

    /**
     * change tax class of lengow's b2b order
     *
     * @param Varien_Event_Observer $observer Magento Varien event observer instance
     * @return void
     */
    public function salesQuoteCollectTotalsBefore(Varien_Event_Observer $observer) {
        // get Core session instance
        $coreSession = Mage::getSingleton('core/session');
        $isLengowB2b = $coreSession->getIsLengowB2b();
        $isFromLengow = $coreSession->getIsFromlengow();
        // if the order is fromm lengow and b2b without tax is enabled
        $quote = $observer->getEvent()->getQuote();
        if ($isLengowB2b && $isFromLengow) {
            $items = $quote->getAllVisibleItems();
            foreach ($items as $item) {
                $item->getProduct()->setTaxClassId(0);
            }
            $coreSession->setIsLengowB2b(0);
        }
    }
}
