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
        parent::_construct();
        $this->_init('lengow/log');
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
    public function write($category, $message = "", $display = false, $marketplace_sku = null)
    {
        if (strlen($message) == 0) {
            return false;
        }
        $decoded_message = $message; //= LengowMain::decodeLogMessage($message, 'en');
        $finalMessage = (empty($category) ? '' : '['.$category.'] ');
        $finalMessage .= ''.(empty($marketplace_sku) ? '' : 'order '.$marketplace_sku.' : ');
        $finalMessage .= $decoded_message;
        if ($display) {
            echo $finalMessage.'<br />';
            flush();
        }
        $log = Mage::getModel('lengow/log');
        $log->setDate(Mage::getModel('core/date')->date('Y-m-d H:i:s'));
        $log->setMessage($finalMessage);
        return $log->save();
    }

    /**
     * Suppress log files when too old
     *
     * @param integer $nbDays
     */
    public function cleanLog($nbDays = 20)
    {
        $nbDays = (int)$nbDays;
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $table = $resource->getTableName('lengow/log');
        $query = "DELETE FROM ".$table." WHERE `date` < DATE_SUB(NOW(),INTERVAL ".$nbDays." DAY)";
        $writeConnection->query($query);
    }
}
