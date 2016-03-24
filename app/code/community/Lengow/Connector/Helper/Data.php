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
     * @var integer    life of log files in days
     */
    const LOG_LIFE = 20;

    /**
     * User another translation system (key based)
     *
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

    /**
     * Write log
     *
     * @param string    $category           Category
     * @param string    $message            log message
     * @param boolean   $display            display on screen
     * @param string    $marketplace_sku    lengow order id
     *
     * @return boolean
     */
    public function log($category, $message = "", $display = false, $marketplace_sku = null)
    {
        if (strlen($message) == 0) {
            return false;
        }
        $decoded_message = $message; //= LengowMain::decodeLogMessage($message, 'en');
        $finalMessage = (empty($category) ? '' : '['.$category.'] ');
        $finalMessage.= ''.(empty($marketplace_sku) ? '' : 'order '.$marketplace_sku.' : ');
        $finalMessage.= $decoded_message;
        if ($display) {
            echo $finalMessage.'<br />';
            flush();
        }
        $log = Mage::getModel('lengow/log');
        return $log->createLog(array('message' => $finalMessage));
    }

    /**
     * Delete log files when too old
     */
    public function cleanLog()
    {
        $nbDays = (int)$nbDays;
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $table = $resource->getTableName('lengow/log');
        $query = "DELETE FROM ".$table." WHERE `date` < DATE_SUB(NOW(),INTERVAL ".self::LOG_LIFE." DAY)";
        $writeConnection->query($query);
    }
}
