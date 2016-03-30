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
                $value = $this->getOrderLogType($value);
            }
            $this->setData($key, $value);
        }
        $this->setData('created_at', Mage::getModel('core/date')->date('Y-m-d H:i:s'));
        return $this->save();
    }

    /**
     * Update Lengow order error
     *
     * @param array $params
     *
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
        $this->setData('updated_at', Mage::getModel('core/date')->date('Y-m-d H:i:s'));
        return $this->save();
    }

    /**
     * Get updated fields
     *
     * @return array
     *
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
     * @return mixed
     */
    public function getOrderLogType($type = null)
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
}
