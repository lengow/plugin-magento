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
 * Model import orderline
 */
class Lengow_Connector_Model_Import_Orderline extends Mage_Core_Model_Abstract
{
    /**
     * @var string Lengow order line table name
     */
    const TABLE_ORDER_LINE = 'lengow_order_line';

    /* Order line fields */
    const FIELD_ID = 'id';
    const FIELD_ORDER_ID = 'order_id';
    const FIELD_ORDER_LINE_ID = 'order_line_id';

    /**
     * @var array $_fieldList field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    protected $_fieldList = array(
        self::FIELD_ORDER_ID => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => true,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => false,
        ),
        self::FIELD_ORDER_LINE_ID => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => true,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => false,
        ),
    );

    /**
     * Constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('lengow/import_orderline');
    }

    /**
     * Create Lengow order line
     *
     * @param array $params orderline parameters
     *
     * @return Lengow_Connector_Model_Import_Orderline|false
     */
    public function createOrderLine($params = array())
    {
        foreach ($this->_fieldList as $key => $value) {
            if (!array_key_exists($key, $params) && $value[Lengow_Connector_Helper_Data::FIELD_REQUIRED]) {
                return false;
            }
        }
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        try {
            return $this->save();
        } catch (\Exception $e) {
            /** @var Lengow_Connector_Helper_Data $helper */
            $helper = Mage::helper('lengow_connector/data');
            $errorMessage = '[Orm error]: "' . $e->getMessage() . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
            $helper->log(
                Lengow_Connector_Helper_Data::CODE_ORM,
                $helper->setLogMessage('log.orm.record_insert_failed', array('error_message' => $errorMessage))
            );
            return false;
        }
    }

    /**
     * Get all order line id by order id
     *
     * @param integer $orderId Magento order id
     *
     * @return array|false
     */
    public function getOrderLineByOrderID($orderId)
    {
        $results = $this->getCollection()
            ->addFieldToFilter(self::FIELD_ORDER_ID, $orderId)
            ->addFieldToSelect(self::FIELD_ORDER_LINE_ID)
            ->getData();
        if (!empty($results)) {
            return $results;
        }
        return false;
    }
}
