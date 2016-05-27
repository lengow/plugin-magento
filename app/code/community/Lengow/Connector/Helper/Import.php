<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Helper_Import extends Mage_Core_Helper_Abstract
{
    /**
     * @var Lengow_Connector_Helper_Config
     */
    protected $_config = null;

    /**
     * marketplaces collection
     */
    public static $marketplaces = array();

    /**
     * @var array valid states lengow to create a Lengow order
     */
    protected $_lengow_states = array(
        'accepted',
        'waiting_shipment',
        'shipped',
        'closed'
    );

    /**
     * Construct
     */
    public function __construct()
    {
        $this->_config = Mage::helper('lengow_connector/config');
    }

    /**
     * Get Marketplace singleton
     *
     * @param string    $name       markeplace name
     * @param integer   $store_id   store Id
     *
     * @return array Lengow marketplace
     */
    public static function getMarketplaceSingleton($name, $store_id = null)
    {
        if (!array_key_exists($name, self::$marketplaces)) {
            self::$marketplaces[$name] = Mage::getModel(
                'lengow/import_marketplace',
                array(
                    'name'     => $name,
                    'store_id' => $store_id
                )
            );
        }
        return self::$marketplaces[$name];
    }

    /**
     * Check if import is already in process
     *
     * @return boolean
     */
    public function importIsInProcess()
    {
        $timestamp = $this->_config->get('import_in_progress');
        if ($timestamp > 0) {
            // security check : if last import is more than 10 min old => authorize new import to be launched
            if (($timestamp + (60 * 1)) < time()) {
                $this->setImportEnd();
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Get Rest time to make re import order
     *
     * @return boolean
     */
    public function restTimeToImport()
    {
        $timestamp = $this->_config->get('import_in_progress');
        if ($timestamp > 0) {
            return $timestamp + (60 * 1) - time();
        }
        return false;
    }

    /**
     * Set import to "in process" state
     *
     * @return boolean
     */
    public function setImportInProcess()
    {
        return $this->_config->set('import_in_progress', time());
    }

    /**
     * Set import to finished
     *
     * @return boolean
     */
    public function setImportEnd()
    {
        return $this->_config->set('import_in_progress', -1);
    }

    /**
     * Check if order status is valid for import
     *
     * @param string                                    $order_state_marketplace    order state
     * @param Lengow_Connector_Model_Import_Marketplace $marketplace                order marketplace
     *
     * @return boolean
     */
    public function checkState($order_state_marketplace, $marketplace)
    {
        if (empty($order_state_marketplace)) {
            return false;
        }
        if (!in_array($marketplace->getStateLengow($order_state_marketplace), $this->_lengow_states)) {
            return false;
        }
        return true;
    }

    /**
     * Record the date of the last import
     *
     * @param string $type (cron or manual)
     *
     * @return boolean
     */
    public function updateDateImport($type)
    {
        if ($type === 'cron') {
            $this->_config->set('last_import_cron', Mage::getModel('core/date')->gmtTimestamp());
        } else {
            $this->_config->set('last_import_manual', Mage::getModel('core/date')->gmtTimestamp());
        }
    }

    /**
     * Get last import (type and timestamp)
     *
     * @return mixed
     */
    public function getLastImport()
    {
        $timestamp_cron = $this->_config->get('last_import_cron');
        $timestamp_manual = $this->_config->get('last_import_manual');

        if ($timestamp_cron && $timestamp_manual) {
            if ((int)$timestamp_cron > (int) $timestamp_manual) {
                return array('type' => 'cron', 'timestamp' => (int)$timestamp_cron);
            } else {
                return array('type' => 'manual', 'timestamp' => (int)$timestamp_manual);
            }
        } elseif ($timestamp_cron && !$timestamp_manual) {
            return array('type' => 'cron', 'timestamp' => (int)$timestamp_cron);
        } elseif ($timestamp_manual && !$timestamp_cron) {
            return array('type' => 'manual', 'timestamp' => (int)$timestamp_manual);
        }

        return array('type' => 'none', 'timestamp' => 'none');
    }

    /**
     * Check logs table and send mail for order not imported correctly
     *
     * @param  boolean $log_output See log or not
     *
     * @return void
     */
    public function sendMailAlert($log_output = false)
    {
        //TODO: sendmail + lengow_log

        $helper = Mage::helper('lengow_connector');
        $subject = 'Lengow imports logs';
        $mail_body = '';
        $errors = Mage::getModel('lengow/import_ordererror')->getImportErrors();
        if (empty($errors)) {
            return true;
        }
        foreach ($errors as $error) {
            $mail_body .= '<li>'.$helper->decodeLogMessage('lengow_log.mail_report.order', null, array(
                    'marketplace_sku' => $error['marketplace_sku']
                ));
            if ($error['message'] != '') {
                $mail_body .= ' - '.$helper->decodeLogMessage($error['message']);
            } else {
                $mail_body .= ' - '.$helper->decodeLogMessage('lengow_log.mail_report.no_error_in_report_mail');
            }
            $mail_body .= '</li>';
            $order_error = Mage::getModel('lengow/import_ordererror')
                ->load($error['id']);
            //comment for tests
                //->updateOrderError(array('mail' => 1));
            unset($order_error);
        }


    }
}
