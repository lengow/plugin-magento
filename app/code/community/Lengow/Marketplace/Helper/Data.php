<?php

/**
 * Lengow sync helper data
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Team module <team-module@lengow.com>
 * @copyright   2015 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Marketplace_Helper_Data extends Mage_Core_Helper_Abstract
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

}