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
 * Model import ordererror
 */
class Lengow_Connector_Model_Import_Ordererror extends Mage_Core_Model_Abstract
{
    /**
     * @var string Lengow order error table name
     */
    const TABLE_ORDER_ERROR = 'lengow_order_error';

    /* Order error fields */
    const FIELD_ID = 'id';
    const FIELD_ORDER_LENGOW_ID = 'order_lengow_id';
    const FIELD_MESSAGE = 'message';
    const FIELD_TYPE = 'type';
    const FIELD_IS_FINISHED = 'is_finished';
    const FIELD_MAIL = 'mail';
    const FIELD_CREATED_AT = 'created_at';
    const FIELD_UPDATED_AT = 'updated_at';

    /* Order error types */
    const TYPE_ERROR_IMPORT = 1;
    const TYPE_ERROR_SEND = 2;

    /**
     * @var array $_fieldList field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    protected $_fieldList = array(
        self::FIELD_ORDER_LENGOW_ID => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => true,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => false,
        ),
        self::FIELD_MESSAGE => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => true,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => false,
        ),
        self::FIELD_TYPE => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => true,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => false,
        ),
        self::FIELD_IS_FINISHED => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => false,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => true,
        ),
        self::FIELD_MAIL => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => false,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => true,
        ),
    );

    /**
     * Constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('lengow/import_ordererror');
    }

    /**
     * Create Lengow order error
     *
     * @param array $params ordererror parameters
     *
     * @return Lengow_Connector_Model_Import_Ordererror|false
     */
    public function createOrderError($params = array())
    {
        foreach ($this->_fieldList as $key => $value) {
            if (!array_key_exists($key, $params) && $value[Lengow_Connector_Helper_Data::FIELD_REQUIRED]) {
                return false;
            }
        }
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        $this->setData(
            self::FIELD_CREATED_AT,
            Mage::getModel('core/date')->gmtDate(Lengow_Connector_Helper_Data::DATE_FULL)
        );
        try {
            return $this->save();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Update Lengow order error
     *
     * @param array $params ordererror parameters
     *
     * @return Lengow_Connector_Model_Import_Ordererror|false
     */
    public function updateOrderError($params = array())
    {
        if (!$this->id) {
            return false;
        }
        $updatedFields = $this->getUpdatedFields();
        foreach ($params as $key => $value) {
            if (in_array($key, $updatedFields, true)) {
                $this->setData($key, $value);
            }
        }
        $this->setData(
            self::FIELD_UPDATED_AT,
            Mage::getModel('core/date')->gmtDate(Lengow_Connector_Helper_Data::DATE_FULL)
        );
        try {
            return $this->save();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get updated fields
     *
     * @return array
     */
    public function getUpdatedFields()
    {
        $updatedFields = array();
        foreach ($this->_fieldList as $key => $value) {
            if ($value[Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED]) {
                $updatedFields[] = $key;
            }
        }
        return $updatedFields;
    }

    /**
     * Get all order errors
     *
     * @param integer $orderLengowId Lengow order id
     * @param integer|null $type order error type (import or send)
     * @param boolean|null $finished log finished
     *
     * @return array|false
     *
     */
    public function getOrderErrors($orderLengowId, $type = null, $finished = null)
    {
        $collection = $this->getCollection()->addFieldToFilter(self::FIELD_ORDER_LENGOW_ID, $orderLengowId);
        if ($type !== null) {
            $collection->addFieldToFilter(self::FIELD_TYPE, $type);
        }
        if ($finished !== null) {
            $errorFinished = $finished ? 1 : 0;
            $collection->addFieldToFilter(self::FIELD_IS_FINISHED, $errorFinished);
        }
        $results = $collection->getData();
        if (!empty($results)) {
            return $results;
        }
        return false;
    }

    /**
     * Removes all order error for one order lengow
     *
     * @param integer $orderLengowId Lengow order id
     * @param integer $type order error type (import or send)
     *
     * @return boolean
     */
    public function finishOrderErrors($orderLengowId, $type = self::TYPE_ERROR_IMPORT)
    {
        // get all order errors
        $results = $this->getCollection()
            ->addFieldToFilter(self::FIELD_ORDER_LENGOW_ID, $orderLengowId)
            ->addFieldToFilter(self::FIELD_IS_FINISHED, 0)
            ->addFieldToFilter(self::FIELD_TYPE, $type)
            ->addFieldToSelect(self::FIELD_ID)
            ->getData();
        if (!empty($results)) {
            foreach ($results as $result) {
                /** @var Lengow_Connector_Model_Import_Ordererror $orderError */
                $orderError = Mage::getModel('lengow/import_ordererror')->load($result[self::FIELD_ID]);
                $orderError->updateOrderError(array(self::FIELD_IS_FINISHED => 1));
                unset($orderError);
            }
            return true;
        }
        return false;
    }

    /**
     * Get error import logs never send by mail
     *
     * @return array|false
     */
    public function getImportErrors()
    {
        $results = $this->getCollection()
            ->join(
                array(Lengow_Connector_Model_Import_Order::TABLE_ORDER => 'lengow/import_order'),
                'lengow_order.id=main_table.order_lengow_id',
                array(
                    Lengow_Connector_Model_Import_Order::FIELD_MARKETPLACE_SKU =>
                        Lengow_Connector_Model_Import_Order::FIELD_MARKETPLACE_SKU,
                )
            )
            ->addFieldToFilter(self::FIELD_MAIL, array('eq' => 0))
            ->addFieldToFilter(self::FIELD_IS_FINISHED, array('eq' => 0))
            ->addFieldToSelect(self::FIELD_MESSAGE)
            ->addFieldToSelect(self::FIELD_ID)
            ->getData();
        if (empty($results)) {
            return false;
        }
        return $results;
    }
}
