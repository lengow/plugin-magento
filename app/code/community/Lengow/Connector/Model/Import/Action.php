<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Model_Import_Action extends Mage_Core_Model_Abstract
{
    /**
    * integer action state for new action
    */
    const STATE_NEW = 0;

    /**
    * integer action state for action finished
    */
    const STATE_FINISH = 1;

    /**
     * @var array $_field_list field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    protected $_field_list = array(
        'order_id'       => array('required' => true, 'updated' => false),
        'action_id'      => array('required' => true, 'updated' => false),
        'order_line_sku' => array('required' => false, 'updated' => false),
        'action_type'    => array('required' => true, 'updated' => false),
        'retry'          => array('required' => false, 'updated' => true),
        'parameters'     => array('required' => true, 'updated' => false),
        'state'          => array('required' => false, 'updated' => true)
    );

    /**
     * Constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('lengow/import_action');
    }

    /**
     * Create Lengow action
     *
     * @param array $params
     *
     */
    public function createAction($params = array())
    {
        foreach ($this->_field_list as $key => $value) {
            if (!array_key_exists($key, $params) && $value['required']) {
                return false;
            }
        }
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        $this->setData('state', self::STATE_NEW);
        $this->setData('created_at', Mage::getModel('core/date')->date('Y-m-d H:i:s'));
        return $this->save();
    }

    /**
     * Update Lengow action
     *
     * @param array $params
     *
     */
    public function updateAction($params = array())
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
}
