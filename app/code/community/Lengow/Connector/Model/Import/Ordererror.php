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
     * @var integer order error import type
     */
    const TYPE_ERROR_IMPORT = 1;

    /**
     * @var integer order error send type
     */
    const TYPE_ERROR_SEND = 2;

    /**
     * @var array $_fieldList field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    protected $_fieldList = array(
        'order_lengow_id' => array('required' => true, 'updated' => false),
        'message' => array('required' => true, 'updated' => false),
        'type' => array('required' => true, 'updated' => false),
        'is_finished' => array('required' => false, 'updated' => true),
        'mail' => array('required' => false, 'updated' => true)
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
            if (!array_key_exists($key, $params) && $value['required']) {
                return false;
            }
        }
        foreach ($params as $key => $value) {
            if ($key == 'type') {
                $value = $this->getOrderErrorType($value);
            }
            $this->setData($key, $value);
        }
        $this->setData('created_at', Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s'));
        return $this->save();
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
            if (in_array($key, $updatedFields)) {
                $this->setData($key, $value);
            }
        }
        $this->setData('updated_at', Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s'));
        return $this->save();
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
            if ($value['updated']) {
                $updatedFields[] = $key;
            }
        }
        return $updatedFields;
    }

    /**
     * Return type value
     *
     * @param string $type order error type (import or send)
     *
     * @return integer
     */
    public function getOrderErrorType($type = null)
    {
        switch ($type) {
            case 'import':
                return self::TYPE_ERROR_IMPORT;
            case 'send':
                return self::TYPE_ERROR_SEND;
            default:
                return self::TYPE_ERROR_IMPORT;
        }
    }

    /**
     * Get all order errors
     *
     * @param integer $orderLengowId Lengow order id
     * @param string $type order error type (import or send)
     * @param boolean $finished log finished
     *
     * @return array|false
     *
     */
    public function getOrderErrors($orderLengowId, $type = null, $finished = null)
    {
        $collection = $this->getCollection()->addFieldToFilter('order_lengow_id', $orderLengowId);
        if (!is_null($type)) {
            $errorType = $this->getOrderErrorType($type);
            $collection->addFieldToFilter('type', $errorType);
        }
        if (!is_null($finished)) {
            $errorFinished = $finished ? 1 : 0;
            $collection->addFieldToFilter('is_finished', $errorFinished);
        }
        $results = $collection->getData();
        if (count($results) > 0) {
            return $results;
        }
        return false;
    }

    /**
     * Removes all order error for one order lengow
     *
     * @param integer $orderLengowId Lengow order id
     * @param string $type order error type (import or send)
     *
     * @return boolean
     */
    public function finishOrderErrors($orderLengowId, $type = 'import')
    {
        $errorType = $this->getOrderErrorType($type);
        // get all order errors
        $results = $this->getCollection()
            ->addFieldToFilter('order_lengow_id', $orderLengowId)
            ->addFieldToFilter('is_finished', 0)
            ->addFieldToFilter('type', $errorType)
            ->addFieldToSelect('id')
            ->getData();
        if (count($results) > 0) {
            foreach ($results as $result) {
                $orderError = Mage::getModel('lengow/import_ordererror')->load($result['id']);
                $orderError->updateOrderError(array('is_finished' => 1));
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
        // Compatibility for version 1.5
        if (Mage::getVersion() < '1.6.0.0') {
            $results = $this->getCollection()
                ->join(
                    'lengow/import_order',
                    '`lengow/import_order`.id=main_table.order_lengow_id',
                    array('marketplace_sku' => 'marketplace_sku')
                )
                ->addFieldToFilter('mail', array('eq' => 0))
                ->addFieldToFilter('is_finished', array('eq' => 0))
                ->addFieldToSelect('message')
                ->addFieldToSelect('id')
                ->getData();
        } else {
            $results = $this->getCollection()
                ->join(
                    array('lengow_order' => 'lengow/import_order'),
                    'lengow_order.id=main_table.order_lengow_id',
                    array('marketplace_sku' => 'marketplace_sku')
                )
                ->addFieldToFilter('mail', array('eq' => 0))
                ->addFieldToFilter('is_finished', array('eq' => 0))
                ->addFieldToSelect('message')
                ->addFieldToSelect('id')
                ->getData();
        }
        if (count($results) == 0) {
            return false;
        }
        return $results;
    }
}
