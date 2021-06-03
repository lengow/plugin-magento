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
    protected $_configHelper;

    /**
     * @var array marketplaces collection
     */
    public static $marketplaces = array();

    /**
     * @var array valid states lengow to create a Lengow order
     */
    protected $_lengowStates = array(
        Lengow_Connector_Model_Import_Order::STATE_WAITING_SHIPMENT,
        Lengow_Connector_Model_Import_Order::STATE_SHIPPED,
        Lengow_Connector_Model_Import_Order::STATE_CLOSED,
    );

    /**
     * Construct
     */
    public function __construct()
    {
        $this->_configHelper = Mage::helper('lengow_connector/config');
    }

    /**
     * Get Marketplace singleton
     *
     * @param string $name marketplace name
     *
     * @return array Lengow marketplace
     */
    public static function getMarketplaceSingleton($name)
    {
        if (!array_key_exists($name, self::$marketplaces)) {
            self::$marketplaces[$name] = Mage::getModel('lengow/import_marketplace', array('name' => $name));
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
        $timestamp = $this->_configHelper->get(Lengow_Connector_Helper_Config::SYNCHRONIZATION_IN_PROGRESS);
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
        $timestamp = $this->_configHelper->get(Lengow_Connector_Helper_Config::SYNCHRONIZATION_IN_PROGRESS);
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
        return $this->_configHelper->set(Lengow_Connector_Helper_Config::SYNCHRONIZATION_IN_PROGRESS, time());
    }

    /**
     * Set import to finished
     *
     * @return boolean
     */
    public function setImportEnd()
    {
        return $this->_configHelper->set(Lengow_Connector_Helper_Config::SYNCHRONIZATION_IN_PROGRESS, -1);
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
        if (!in_array($marketplace->getStateLengow($orderStateMarketplace), $this->_lengowStates, true)) {
            return false;
        }
        return true;
    }

    /**
     * Record the date of the last import
     *
     * @param string $type last import type (cron or manual)
     */
    public function updateDateImport($type)
    {
        if ($type === Lengow_Connector_Model_Import::TYPE_CRON
            || $type === Lengow_Connector_Model_Import::TYPE_MAGENTO_CRON
        ) {
            $this->_configHelper->set(
                Lengow_Connector_Helper_Config::LAST_UPDATE_CRON_SYNCHRONIZATION,
                Mage::getModel('core/date')->gmtTimestamp()
            );
        } else {
            $this->_configHelper->set(
                Lengow_Connector_Helper_Config::LAST_UPDATE_MANUAL_SYNCHRONIZATION,
                Mage::getModel('core/date')->gmtTimestamp()
            );
        }
    }

    /**
     * Get last import (type and timestamp)
     *
     * @return array
     */
    public function getLastImport()
    {
        $timestampCron = $this->_configHelper->get(
            Lengow_Connector_Helper_Config::LAST_UPDATE_CRON_SYNCHRONIZATION
        );
        $timestampManual = $this->_configHelper->get(
            Lengow_Connector_Helper_Config::LAST_UPDATE_MANUAL_SYNCHRONIZATION
        );
        if ($timestampCron && $timestampManual) {
            if ((int) $timestampCron > (int) $timestampManual) {
                return array('type' => Lengow_Connector_Model_Import::TYPE_CRON, 'timestamp' => (int) $timestampCron);
            }
            return array('type' => Lengow_Connector_Model_Import::TYPE_MANUAL, 'timestamp' => (int) $timestampManual);
        }
        if ($timestampCron && !$timestampManual) {
            return array('type' => Lengow_Connector_Model_Import::TYPE_CRON, 'timestamp' => (int) $timestampCron);
        }
        if ($timestampManual && !$timestampCron) {
            return array('type' =>  Lengow_Connector_Model_Import::TYPE_MANUAL, 'timestamp' => (int) $timestampManual);
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
        $errors = Mage::getModel('lengow/import_ordererror')->getImportErrors();
        if ($errors) {
            /** @var Lengow_Connector_Helper_Data $helper */
            $helper = Mage::helper('lengow_connector');
            $subject = $helper->decodeLogMessage('lengow_log.mail_report.subject_report_mail');
            $pluginLinks = Mage::helper('lengow_connector/sync')->getPluginLinks();
            $support = $helper->decodeLogMessage(
                'lengow_log.mail_report.no_error_in_report_mail',
                null,
                array('support_link' => $pluginLinks[Lengow_Connector_Helper_Sync::LINK_TYPE_SUPPORT])
            );
            $mailBody = '<h2>' . $subject . '</h2><p><ul>';
            foreach ($errors as $error) {
                $order = $helper->decodeLogMessage(
                    'lengow_log.mail_report.order',
                    null,
                    array('marketplace_sku' => $error['marketplace_sku'])
                );
                $message = $error['message'] !== '' ? $helper->decodeLogMessage($error['message']): $support;
                $mailBody .= '<li>' . $order . ' - ' . $message . '</li>';
                $orderError = Mage::getModel('lengow/import_ordererror')->load($error['id']);
                $orderError->updateOrderError(array('mail' => 1));
                unset($orderError, $order, $message);
            }
            $mailBody .= '</ul></p>';
            $emails = Mage::helper('lengow_connector/config')->getReportEmailAddress();
            foreach ($emails as $email) {
                if ($email !== '') {
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
                            Lengow_Connector_Helper_Data::CODE_MAIL_REPORT,
                            $helper->setLogMessage('log.mail_report.send_mail_to', array('email' => $email)),
                            $logOutput
                        );
                    } catch (Exception $e) {
                        $helper->log(
                            Lengow_Connector_Helper_Data::CODE_MAIL_REPORT,
                            $helper->setLogMessage('log.mail_report.unable_send_mail_to', array('email' => $email)),
                            $logOutput
                        );
                    }
                    unset($mail, $email);
                }
            }
        }
    }
}
