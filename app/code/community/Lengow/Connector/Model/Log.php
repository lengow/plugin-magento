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

    protected function _construct()
    {
        $this->_init('lengow/log');
    }

    /**
     * Save message event
     * @param $message string
     *
     * @return boolean
     */
    public function log($message)
    {
        $log = Mage::getModel('lengow/log');
        if (strlen($message) > 0) {
            $log->setCreatedAt(Mage::getModel('core/date')->date('Y-m-d H:i:s'));
            $log->setMessage($message);
            return $log->save();
        } else {
            return false;
        }
    }

    /**
     * Suppress log files when too old.
     */
    public function cleanLog()
    {
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $table = $resource->getTableName('lengow/log');
        $query = "DELETE FROM ".$table." WHERE created_at < DATE_SUB(NOW(),INTERVAL 20 DAY)";
        $writeConnection->query($query);
    }
}
