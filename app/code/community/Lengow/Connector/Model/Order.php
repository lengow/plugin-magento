<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Model_Order extends Mage_Core_Model_Abstract
{
    protected $_required_fields = array(
        'id_store',
        'marketplace_sku',
        'marketplace_name',
        'marketplace_label',
        'delivery_address_id',
        'order_lengow_state',
        'order_date',
    );

    /**
     * Constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('lengow/order');
    }

    /**
     * Create Lengow order
     *
     * @param array $params
     *
     */
    public function createOrder($params = array())
    {
        foreach ($this->_required_fields as $value) {
            if (!array_key_exists($value, $params)) {
                return false;
            }
        }
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        $this->setData('created_at', Mage::getModel('core/date')->date('Y-m-d H:i:s'));
        return $this->save();
    }

    /**
     * Update Lengow order
     *
     * @param array $params
     *
     */
    public function updateOrder($params = array())
    {
        if (!$this->id) {
            return false;
        }
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        $this->setData('updated_at', Mage::getModel('core/date')->date('Y-m-d H:i:s'));
        return $this->save();
    }
}
