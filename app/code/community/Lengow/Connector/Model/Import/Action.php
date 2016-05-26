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
     * @return Lengow_Connector_Model_Import_Action
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
        $this->setData('created_at', Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s'));
        return $this->save();
    }

    /**
     * Update Lengow action
     *
     * @param array $params
     *
     * @return Lengow_Connector_Model_Import_Action
     */
    public function updateAction($params = array())
    {
        if (!$this->id) {
            return false;
        }
        if ((int)$this->getData('state') != self::STATE_NEW) {
            return false;
        }
        $updated_fields = $this->getUpdatedFields();
        foreach ($params as $key => $value) {
            if (in_array($key, $updated_fields)) {
                $this->setData($key, $value);
            }
        }
        $this->setData('retry', (int)$this->getData('retry') + 1);
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
     * Get ID from API action ID
     *
     * @param integer $action_id action id from API
     *
     * @return mixed
     */
    public function getIdByActionId($action_id)
    {
        $results = $this->getCollection()
            ->addFieldToFilter('action_id', $action_id)
            ->addFieldToSelect('id')
            ->getData();
        if (count($results) > 0) {
            return (int)$results[0]['id'];
        }
        return false;
    }

    /**
     * Find actions by order id
     *
     * @param integer $order_id
     * @param string  $action_type (ship or cancel)
     *
     * @return mixed
     */
    public function getOrderActiveAction($order_id, $action_type)
    {
        $results = $this->getCollection()
            ->addFieldToFilter('order_id', $order_id)
            ->addFieldToFilter('action_type', $action_type)
            ->getData();
        if (count($results) > 0) {
            return $results;
        }
        return false;
    }

    /**
     * Check if active actions are finished
     *
     * @return bool
     */
    public static function checkFinishAction()
    {
        if (!(bool)Mage::helper('lengow_connector/config')->get('preprod_mode_enable')) {
            return false;
        }

        $shops = LengowShop::findAll();
        foreach ($shops as $shop) {
            $actions = LengowAction::getActiveActionByShop('ship', $shop['id_shop'], false);
            foreach ($actions as $action) {
                $result = LengowConnector::queryApi(
                    'get',
                    '/v3.0/orders/actions/',
                    $shop['id_shop'],
                    array('id' => $action['id'])
                );
                if (isset($result->id) && isset($result->processed) && isset($result->queued)) {
                    if ((int)$result->id > 0 && $result->queued == false) {
                        //update actions
                        Db::getInstance()->autoExecute(
                            _DB_PREFIX_ . 'lengow_actions',
                            array(
                                'state'         => (int)LengowAction::STATE_FINISH,
                                'updated_at'    => date('Y-m-d h:m:i'),
                            ),
                            'UPDATE',
                            'id = '.(int)$action['id']
                        );
                        if ($result->processed) {
                            $id_order_lengow = LengowOrder::findByOrder($action['id_order']);
                            LengowOrder::updateOrderLengow($id_order_lengow, array(
                                'order_process_state' => 2
                            ));
                        }
                    }
                }
                if (!LengowMain::inTest()) {
                    usleep(250000);
                }
            }
        }
        return true;
    }
}
