<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Model_Import_Ordererror extends Mage_Core_Model_Abstract
{
    /**
    * integer order error import type
    */
    const TYPE_LOG_IMPORT = 1;

    /**
    * integer order error send type
    */
    const TYPE_LOG_SEND = 2;

    /**
     * @var array $_field_list field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    protected $_field_list = array(
        'order_lengow_id' => array('required' => true, 'updated' => false),
        'message'         => array('required' => true, 'updated' => false),
        'type'            => array('required' => true, 'updated' => false),
        'is_finished'     => array('required' => false, 'updated' => true),
        'mail'            => array('required' => false, 'updated' => true)
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
     * @param array $params
     *
     * @return Lengow_Connector_Model_Import_Ordererror
     */
    public function createOrderError($params = array())
    {
        foreach ($this->_field_list as $key => $value) {
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
     * @param array $params
     *
     * @return Lengow_Connector_Model_Import_Ordererror
     */
    public function updateOrderError($params = array())
    {
        if (!$this->id) {
            return false;
        }
        $updated_fields = $this->getUpdatedFields();
        foreach ($params as $key => $value) {
            if (in_array($key, $updated_fields)) {
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
        $updated_fields = array();
        foreach ($this->_field_list as $key => $value) {
            if ($value['updated']) {
                $updated_fields[] = $key;
            }
        }
        return $updated_fields;
    }

    /**
     * Return type value
     *
     * @param string $type Type (import or send)
     *
     * @return integer
     */
    public function getOrderErrorType($type = null)
    {
        switch ($type) {
            case 'import':
                return self::TYPE_LOG_IMPORT;
                break;
            case 'send':
                return self::TYPE_LOG_SEND;
                break;
            default:
                return self::TYPE_LOG_IMPORT;
                break;
        }
    }

    /**
     * Get all order errors
     *
     * @param integer $order_lengow_id Lengow order id
     * @param string  $type            type (import or send)
     * @param boolean $finished        log finished (true or false)
     *
     * @return mixed
     *
     */
    public function getOrderErrors($order_lengow_id, $type = null, $finished = null)
    {
        $error_type = $this->getOrderErrorType($type);
        $collection = $this->getCollection()->addFieldToFilter('order_lengow_id', $order_lengow_id);
        if (!is_null($type)) {
            $error_type = $this->getOrderErrorType($type);
            $collection->addFieldToFilter('type', $error_type);
        }
        if (!is_null($finished)) {
            $error_finished = $finished ? 1 : 0;
            $collection->addFieldToFilter('is_finished', $error_finished);
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
     * @param integer $order_lengow_id Lengow order id
     * @param string  $type            type (import or send)
     *
     * @return boolean
     */
    public function finishOrderErrors($order_lengow_id, $type = 'import')
    {
        $error_type = $this->getOrderErrorType($type);
        // get all order errors
        $results = $this->getCollection()
            ->addFieldToFilter('order_lengow_id', $order_lengow_id)
            ->addFieldToFilter('is_finished', 0)
            ->addFieldToFilter('type', $error_type)
            ->addFieldToSelect('id')
            ->getData();
        if (count($results) > 0) {
            foreach ($results as $result) {
                $order_error = Mage::getModel('lengow/import_ordererror')->load($result['id']);
                $order_error->updateOrderError(array('is_finished' => 1));
                unset($order_error);
            }
            return true;
        }
        return false;
    }

    /**
     * Get error import logs never send by mail
     *
     * @return mixed
     */
    public function getImportErrors() {
        $results = $this->getCollection()
            ->join(
                array('order'=> 'lengow/import_order'),
                'order.id=main_table.order_lengow_id',
                array('marketplace_sku' => 'marketplace_sku'),
                array('message' => 'message'),
                array('id' => 'id')
            )
            ->addFieldToFilter('mail', array('eq' => 0))
            ->addFieldToFilter('is_finished', array('eq' => 0))
            ->addFieldToSelect('message')
            ->addFieldToSelect('id')
            ->getData();
        if (count($results) == 0) {
            return false;
        }
        return $results;
    }
}
