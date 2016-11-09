<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Model_Log extends Mage_Core_Model_Abstract
{
    /**
     * @var array $_fieldList field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    protected $_fieldList = array(
        'message' => array('required' => true, 'updated' => false)
    );

    /**
     * Constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('lengow/log');
    }

    /**
     * Create Lengow log
     *
     * @param array $params
     *
     * @return Lengow_Connector_Model_Log
     */
    public function createLog($params = array())
    {
        foreach ($this->_fieldList as $key => $value) {
            if (!array_key_exists($key, $params) && $value['required']) {
                return false;
            }
        }
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        $this->setData('date', Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s'));
        return $this->save();
    }
}
