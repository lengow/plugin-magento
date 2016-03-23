<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Model_Orderline extends Mage_Core_Model_Abstract
{
    protected $_required_fields = array(
        'id_order',
        'id_order_line'
    );

    protected function _construct()
    {
        parent::_construct();
        $this->_init('lengow/orderline');
    }

    /**
     * Create Lengow order line
     *
     * @param array $params
     *
     */
    public function createOrderLine($params = array())
    {
        foreach ($this->_required_fields as $value) {
            if (!array_key_exists($value, $params)) {
                return false;
            }
        }
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        return $this->save();
    }
}
