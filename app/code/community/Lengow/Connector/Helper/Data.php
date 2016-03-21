<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Writes log
     *
     * @param string $message log message
     * @param string $id_order lengow order id
     *
     * @return Lengow_Sync_Helper_Data
     */
    public function log($message, $id_order = null)
    {
        $log_model = Mage::getModel('lengow/log');
        return $log_model->log($message, $id_order);
    }

    /**
     * User another translation system (key based)
     * @return string
     */
    public function __()
    {
        $args = func_get_args();
        $t = Mage::helper('lengow_connector/translation');
        if ($args[0]=="") {
            return "";
        }
        return $t->t($args[0]);
    }
}
