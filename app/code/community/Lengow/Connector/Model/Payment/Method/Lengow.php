<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Model_Payment_Method_Lengow extends Mage_Payment_Model_Method_Abstract
{
    /**
     * Code
     */
    protected $_code = 'lengow';
    
    /**
     * Info block type
     */
    protected $_infoBlockType = 'lengow/payment_info_purchaseorder';

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     *
     * @return  Profileolabs_Lengow_Model_Manageorders_Payment_Method_Purchaseorder
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $this->getInfoInstance()->setAdditionalData($data->getMarketplace());
        return $this;
    }

    /**
     * Check whether payment method can be used
     *
     * @param Mage_Sales_Model_Quote
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        return (bool)Mage::getSingleton('core/session')->getIsFromlengow();
    }
}
