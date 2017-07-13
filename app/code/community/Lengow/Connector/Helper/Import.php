<?php
/**
 * Copyright 2017 Lengow SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @subpackage  Helper
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Helper import
 */
class Lengow_Connector_Helper_Import extends Mage_Core_Helper_Abstract
{
    /**
     * @var Lengow_Connector_Helper_Config Lengow config helper instance
     */
    protected $_config = null;

    /**
     * @var array marketplaces collection
     */
    public static $marketplaces = array();

    /**
     * @var array valid states lengow to create a Lengow order
     */
    protected $_lengowStates = array(
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
     * @param string $name marketplace name
     * @param integer $storeId Magento store Id
     *
     * @return array Lengow marketplace
     */
    public static function getMarketplaceSingleton($name, $storeId = null)
    {
        if (!array_key_exists($name, self::$marketplaces)) {
            self::$marketplaces[$name] = Mage::getModel(
                'lengow/import_marketplace',
                array(
                    'name' => $name,
                    'store_id' => $storeId
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
            // security check : if last import is more than 60 seconds old => authorize new import to be launched
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
     * @param string $orderStateMarketplace order state
     * @param Lengow_Connector_Model_Import_Marketplace $marketplace order marketplace
     *
     * @return boolean
     */
    public function checkState($orderStateMarketplace, $marketplace)
    {
        if (empty($orderStateMarketplace)) {
            return false;
        }
        if (!in_array($marketplace->getStateLengow($orderStateMarketplace), $this->_lengowStates)) {
            return false;
        }
        return true;
    }

    /**
     * Record the date of the last import
     *
     * @param string $type last import type (cron or manual)
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
     * @return array
     */
    public function getLastImport()
    {
        $timestampCron = $this->_config->get('last_import_cron');
        $timestampManual = $this->_config->get('last_import_manual');

        if ($timestampCron && $timestampManual) {
            if ((int)$timestampCron > (int)$timestampManual) {
                return array('type' => 'cron', 'timestamp' => (int)$timestampCron);
            } else {
                return array('type' => 'manual', 'timestamp' => (int)$timestampManual);
            }
        } elseif ($timestampCron && !$timestampManual) {
            return array('type' => 'cron', 'timestamp' => (int)$timestampCron);
        } elseif ($timestampManual && !$timestampCron) {
            return array('type' => 'manual', 'timestamp' => (int)$timestampManual);
        }

        return array('type' => 'none', 'timestamp' => 'none');
    }

    /**
     * Check logs table and send mail for order not imported correctly
     *
     * @param  boolean $logOutput see log or not
     */
    public function sendMailAlert($logOutput = false)
    {
        $helper = Mage::helper('lengow_connector');
        $subject = '<h2>' . $helper->decodeLogMessage('lengow_log.mail_report.subject_report_mail') . '</h2>';
        $mailBody = '<p><ul>';
        $errors = Mage::getModel('lengow/import_ordererror')->getImportErrors();
        if ($errors) {
            foreach ($errors as $error) {
                $mailBody .= '<li>' . $helper->decodeLogMessage(
                        'lengow_log.mail_report.order',
                        null,
                        array(
                            'marketplace_sku' => $error['marketplace_sku']
                        )
                    );
                if ($error['message'] != '') {
                    $mailBody .= ' - ' . $helper->decodeLogMessage($error['message']);
                } else {
                    $mailBody .= ' - ' . $helper->decodeLogMessage('lengow_log.mail_report.no_error_in_report_mail');
                }
                $mailBody .= '</li>';
                $orderError = Mage::getModel('lengow/import_ordererror')->load($error['id']);
                $orderError->updateOrderError(array('mail' => 1));
                unset($orderError);
            }
            $mailBody .= '</ul></p>';
            $emails = Mage::helper('lengow_connector/config')->getReportEmailAddress();
            foreach ($emails as $email) {
                if (strlen($email) > 0) {
                    $mail = Mage::getModel('core/email');
                    $mail->setToEmail($email);
                    $mail->setBody($mailBody);
                    $mail->setSubject($subject);
                    $mail->setFromEmail(Mage::getStoreConfig('trans_email/ident_general/email'));
                    $mail->setFromName("Lengow");
                    $mail->setType('html');
                    try {
                        $mail->send();
                        $helper->log(
                            'MailReport',
                            $helper->setLogMessage('log.mail_report.send_mail_to', array('email' => $email)),
                            $logOutput
                        );
                    } catch (Exception $e) {
                        $helper->log(
                            'MailReport',
                            $helper->setLogMessage('log.mail_report.unable_send_mail_to', array('email' => $email)),
                            $logOutput
                        );
                    }
                    unset($mail);
                }
            }
        }
    }
}
