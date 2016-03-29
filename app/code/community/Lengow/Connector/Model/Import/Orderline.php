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
     * @var array $_field_list field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    protected $_field_list = array(
        'id_order'      => array('required' => true, 'updated' => false),
        'id_order_line' => array('required' => true, 'updated' => false)
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
     */
    public function createOrderLine($params = array())
    {
        foreach ($this->_field_list as $key => $value) {
            if (!array_key_exists($key, $params) && $value['required']) {
                return false;
            }
        }
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        return $this->save();
    }
}
