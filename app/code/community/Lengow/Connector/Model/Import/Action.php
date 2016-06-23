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
            ->addFieldToFilter('state', self::STATE_NEW)
            ->getData();
        if (count($results) > 0) {
            return $results;
        }
        return false;
    }

    /**
     * Get last order action type to re-send action
     *
     * @param integer $order_id
     *
     * @return mixed
     */
    public function getLastOrderActionType($order_id)
    {
        $results = $this->getCollection()
            ->addFieldToFilter('order_id', $order_id)
            ->addFieldToFilter('state', self::STATE_NEW)
            ->addFieldToSelect('action_type');
        if (count($results) > 0) {
            $last_action = $results->getLastItem()->getData();
            return (string)$last_action['action_type'];
        }
        return false;
    }

    /**
     * Find active actions by store
     *
     * @param integer $store_id
     *
     * @return mixed
     */
    public function getActiveActionByStore($store_id)
    {
        $results = $this->getCollection()
            ->join(
                array('order' => 'sales/order'),
                'order.entity_id=main_table.order_id',
                array('store_id' => 'store_id')
            )
            ->addFieldToFilter('order.store_id', $store_id)
            ->addFieldToFilter('main_table.state', self::STATE_NEW)
            ->getData();
        if (count($results) > 0) {
            return $results;
        }
        return false;
    }

    /**
     * Find active actions by order id
     *
     * @param integer $order_id
     *
     * @return mixed
     */
    public function getActiveActionByOrderId($order_id)
    {
        $results = $this->getCollection()
            ->addFieldToFilter('order_id', $order_id)
            ->addFieldToFilter('state', self::STATE_NEW)
            ->getData();
        if (count($results) > 0) {
            return $results;
        }
        return false;
    }

    /**
     * Removes all actions for one order Magento
     *
     * @param integer $order_id     Magento order id
     * @param string  $action_type  type (null, ship or cancel)
     *
     * @return boolean
     */
    public function finishAllActions($order_id, $action_type = null)
    {
        // get all order action
        $collection = $this->getCollection()
            ->addFieldToFilter('order_id', $order_id)
            ->addFieldToFilter('state', self::STATE_NEW);
        if (!is_null($action_type)) {
            $collection->addFieldToFilter('action_type', $action_type);
        }
        $results = $collection->addFieldToSelect('id')->getData();
        if (count($results) > 0) {
            foreach ($results as $result) {
                $action = Mage::getModel('lengow/import_action')->load($result['id']);
                $action->updateAction(array('state' => self::STATE_FINISH));
                unset($action);
            }
            return true;
        }
        return false;
    }

    /**
     * Remove old actions > 3 days
     *
     * @param string  $action_type  type (null, ship or cancel)
     *
     * @return boolean
     */
    public function finishAllOldActions($action_type = null)
    {
        // get all old order action (+ 3 days)
        $collection = $this->getCollection()
            ->addFieldToFilter('state', self::STATE_NEW)
            ->addFieldToFilter('created_at', array(
                'to'       => strtotime('-3 days', time()),
                'datetime' => true
            ));
        if (!is_null($action_type)) {
            $collection->addFieldToFilter('action_type', $action_type);
        }
        $results = $collection->getData();

        if (count($results) > 0) {
            foreach ($results as $result) {
                $action = Mage::getModel('lengow/import_action')->load($result['id']);
                $action->updateAction(array('state' => self::STATE_FINISH));
                $order_lengow_id = Mage::getModel('lengow/import_order')
                    ->getLengowOrderIdWithOrderId($result['order_id']);
                if ($order_lengow_id) {
                    $helper = Mage::helper('lengow_connector/data');
                    $order_lengow = Mage::getModel('lengow/import_order')->load($order_lengow_id);
                    $process_state_finish = $order_lengow->getOrderProcessState('closed');
                    if ((int)$order_lengow->getData('order_process_state') != $process_state_finish
                        && $order_lengow->getData('is_in_error') == 0
                    ) {
                        // If action is denied -> create order error
                        $error_message = $helper->setLogMessage('lengow_log.exception.action_is_too_old');
                        $order_error = Mage::getModel('lengow/import_ordererror');
                        $order_error->createOrderError(array(
                            'order_lengow_id' => $order_lengow_id,
                            'message'         => $error_message,
                            'type'            => 'send',
                        ));
                        $order_lengow->updateOrder(array('is_in_error' => 1));
                        $decoded_message = $helper->decodeLogMessage($error_message, 'en_GB');
                        $helper->log(
                            'API-OrderAction',
                            $helper->setLogMessage('log.order_action.call_action_failed', array(
                                'decoded_message' => $decoded_message
                            )),
                            false,
                            $order_lengow->getData('marketplace_sku')
                        );
                        unset($order_error);
                    }
                    unset($order_lengow);
                }
                unset($action);
            }
            return true;
        }

        return false;
    }

    /**
     * Check if active actions are finished
     *
     * @return bool
     */
    public function checkFinishAction()
    {
        $config = Mage::helper('lengow_connector/config');
        $helper = Mage::helper('lengow_connector/data');
        if ((bool)$config->get('preprod_mode_enable')) {
            return false;
        }
        // get all store to check active actions
        $store_collection = Mage::getResourceModel('core/store_collection')->addFieldToFilter('is_active', 1);
        foreach ($store_collection as $store) {
            if ($config->get('store_enable', (int)$store->getId())) {
                $helper->log(
                    'API-OrderAction',
                    $helper->setLogMessage('log.order_action.start_for_store', array(
                        'store_name' => $store->getName(),
                        'store_id'   => $store->getId()
                    ))
                );
                // Get all active actions by store
                $store_actions = $this->getActiveActionByStore((int)$store->getId());
                // If no active action, do nothing
                if (!$store_actions) {
                    continue;
                }
                // Get all actions with API for 3 days
                $page = 1;
                $api_actions = array();
                $connector = Mage::getModel('lengow/connector');
                do {
                    $results = $connector->queryApi(
                        'get',
                        '/v3.0/orders/actions/',
                        (int)$store->getId(),
                        array(
                            'updated_from' => date('c', strtotime(date('Y-m-d').' -3days')),
                            'updated_to'   => date('c'),
                            'page'         => $page
                        )
                    );
                    if (!is_object($results) || isset($results->error)) {
                        break;
                    }
                    // Construct array actions
                    foreach ($results->results as $action) {
                        if (isset($action->id)) {
                            $api_actions[$action->id] = $action;
                        }
                    }
                    $page++;
                } while ($results->next != null);
                if (count($api_actions) == 0) {
                    continue;
                }
                // Check foreach action if is complete
                foreach ($store_actions as $action) {
                    if (!isset($api_actions[$action['action_id']])) {
                        continue;
                    }
                    if (isset($api_actions[$action['action_id']]->queued)
                        && isset($api_actions[$action['action_id']]->processed)
                        && isset($api_actions[$action['action_id']]->errors)
                    ) {
                        if ($api_actions[$action['action_id']]->queued == false) {
                            // Finish action in lengow_action table
                            $lengow_action = Mage::getModel('lengow/import_action')->load($action['id']);
                            $lengow_action->updateAction(array('state' => self::STATE_FINISH));
                            $order_lengow_id = Mage::getModel('lengow/import_order')
                                ->getLengowOrderIdWithOrderId($action['order_id']);
                            // if lengow order not exist do nothing (compatibility v2)
                            if ($order_lengow_id) {
                                $order_error = Mage::getModel('lengow/import_ordererror');
                                $order_error->finishOrderErrors($order_lengow_id, 'send');
                                $order_lengow = Mage::getModel('lengow/import_order')->load($order_lengow_id);
                                if ($order_lengow->getData('is_in_error') == 1) {
                                    $order_lengow->updateOrder(array('is_in_error' => 0));
                                }
                                $process_state_finish = $order_lengow->getOrderProcessState('closed');
                                if ((int)$order_lengow->getData('order_process_state') != $process_state_finish) {
                                    // If action is accepted -> close order and finish all order actions
                                    if ($api_actions[$action['action_id']]->processed == true) {
                                        $order_lengow->updateOrder(array(
                                            'order_process_state' => $process_state_finish
                                        ));
                                        $this->finishAllActions($action['order_id']);
                                    } else {
                                        // If action is denied -> create order error
                                        $order_error->createOrderError(array(
                                            'order_lengow_id' => $order_lengow_id,
                                            'message'         => $api_actions[$action['action_id']]->errors,
                                            'type'            => 'send',
                                        ));
                                        $order_lengow->updateOrder(array('is_in_error' => 1));
                                        $helper->log(
                                            'API-OrderAction',
                                            $helper->setLogMessage('log.order_action.call_action_failed', array(
                                                'decoded_message' => $api_actions[$action['action_id']]->errors
                                            )),
                                            false,
                                            $order_lengow->getData('marketplace_sku')
                                        );
                                        unset($order_error);
                                    }
                                }
                                unset($order_lengow);
                            }
                            unset($lengow_action);
                        }
                    }
                }
            }
        }
        // Clean actions after 3 days
        $this->finishAllOldActions();
        return true;
    }
}
