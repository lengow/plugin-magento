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
 * Model payment method lengow
 */
class Lengow_Connector_Model_Payment_Method_Lengow extends Mage_Payment_Model_Method_Abstract
{
    /**
     * @var string Lengow payment code
     */
    protected $_code = 'lengow';

    /**
     * @var string info block type
     */
    protected $_infoBlockType = 'lengow/payment_info_purchaseorder';

    /**
     * Assign data to info model instance
     *
     * @param mixed $data payment datas
     *
     * @return Lengow_Connector_Model_Payment_Method_Lengow
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
     * @param Mage_Sales_Model_Quote|null $quote Magento quote instance
     *
     * @return boolean
     */
    public function isAvailable($quote = null)
    {
        return (bool)Mage::getSingleton('core/session')->getIsFromlengow();
    }
}
