<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Model_Import_Orderline extends Mage_Core_Model_Abstract
{
    /**
     * @var array $_fieldList field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    protected $_fieldList = array(
        'order_id'      => array('required' => true, 'updated' => false),
        'order_line_id' => array('required' => true, 'updated' => false)
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
     * @param array $params
     *
     * @return Lengow_Connector_Model_Import_Orderline
     */
    public function createOrderLine($params = array())
    {
        foreach ($this->_fieldList as $key => $value) {
            if (!array_key_exists($key, $params) && $value['required']) {
                return false;
            }
        }
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        return $this->save();
    }

    /**
     * Get all order line id by order id
     *
     * @param integer $orderId Magento order id
     *
     * @return mixed
     */
    public function getOrderLineByOrderID($orderId)
    {
        $results = $this->getCollection()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToSelect('order_line_id')
            ->getData();
        if (count($results) > 0) {
            return $results;
        }
        return false;
    }
}
