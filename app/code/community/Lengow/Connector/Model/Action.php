<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Model_Action extends Mage_Core_Model_Abstract
{
    const STATE_NEW = 0;

    const STATE_FINISH = 1;

    protected $_required_fields = array(
        'id_order',
        'id_action',
        'action_type',
        'parameters',
    );

    protected function _construct()
    {
        parent::_construct();
        $this->_init('lengow/action');
    }

    /**
     * Create Lengow action
     *
     * @param array $params
     *
     */
    public function createAction($params = array())
    {
        foreach ($this->_required_fields as $value) {
            if (!array_key_exists($value, $params)) {
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
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        $this->setData('updated_at', Mage::getModel('core/date')->date('Y-m-d H:i:s'));
        return $this->save();
    }
}
