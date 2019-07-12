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
 * Model shipping carrier lengow
 */
class Lengow_Connector_Model_Shipping_Carrier_Lengow
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    /**
     * @var string Lengow carrier code
     */
    protected $_code = 'lengow';

    /**
     * @var boolean is fixed
     */
    protected $_isFixed = true;

    /**
     * FreeShipping Rates Collector
     *
     * @param Mage_Shipping_Model_Rate_Request $request Magento shipping model rate request instance
     *
     * @return Mage_Shipping_Model_Rate_Result|false
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->isActive()) {
            return false;
        }
        $result = Mage::getModel('shipping/rate_result');
        $method = Mage::getModel('shipping/rate_result_method');
        $method->setCarrier('lengow');
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod('lengow');
        $method->setMethodTitle($this->getConfigData('name'));
        $method->setPrice($this->getSession()->getShippingPrice());
        $method->setCost($this->getSession()->getShippingPrice());
        $result->append($method);
        return $result;
    }

    /**
     * Processing additional validation to check is carrier applicable
     *
     * @param Mage_Shipping_Model_Rate_Request $request Magento shipping model rate request instance
     *
     * @return Mage_Shipping_Model_Carrier_Abstract|Mage_Shipping_Model_Rate_Result_Error|boolean
     */
    public function proccessAdditionalValidation(Mage_Shipping_Model_Rate_Request $request)
    {
        if (Mage::getVersion() === '1.4.1.0') {
            return $this->isActive();
        }
        return parent::proccessAdditionalValidation($request);
    }

    /**
     * Get session
     *
     * @return Mage_Checkout_Model_Session|Mage_Core_Model_Abstract
     */
    public function getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Lengow carrier is active
     *
     * @return boolean
     */
    public function isActive()
    {
        return (bool)Mage::getSingleton('core/session')->getIsFromlengow();
    }

    /**
     * Get allowed methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return array('lengow' => $this->getConfigData('name'));
    }
}
