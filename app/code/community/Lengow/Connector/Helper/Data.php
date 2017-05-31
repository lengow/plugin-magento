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
 * Helper data
 */
class Lengow_Connector_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @var integer life of log files in days
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
        $params = array();
        $isoCode = null;
        $t = Mage::helper('lengow_connector/translation');
        if ($args[0] == "") {
            return "";
        } else {
            $message = $args[0];
        }
        if (isset($args[1]) && is_array($args[1])) {
            $params = $args[1];
        }
        if (isset($args[2]) && strlen($args[2]) > 0) {
            $isoCode = $args[2];
        }
        return $t->t($message, $params, $isoCode);
    }

    /**
     * Get export Url
     *
     * @param integer $storeId Magento store id
     * @param array $additionalParams additional parameters for export url
     *
     * @return string
     */
    public function getExportUrl($storeId, $additionalParams = array())
    {
        $defaultParams = array(
            'store' => $storeId,
            'token' => Mage::helper('lengow_connector/config')->getToken($storeId),
            '_nosid' => true,
            '_store_to_url' => false,
        );
        if (count($additionalParams) > 0) {
            $defaultParams = array_merge($defaultParams, $additionalParams);
        }
        return Mage::getModel('core/url')->setStore($storeId)->getUrl('lengow/feed', $defaultParams);
    }

    /**
     * Get Cron Url
     *
     * @param array $additionalParams additional parameters for cron url
     *
     * @return string
     */
    public function getCronUrl($additionalParams = array())
    {
        $defaultParams = array(
            'token' => Mage::helper('lengow_connector/config')->getToken(),
            '_nosid' => true,
            '_store_to_url' => false,
        );
        if (count($additionalParams) > 0) {
            $defaultParams = array_merge($defaultParams, $additionalParams);
        }
        return Mage::getUrl('lengow/cron', $defaultParams);
    }

    /**
     * Write log
     *
     * @param string $category log category
     * @param string $message log message
     * @param boolean $display display on screen
     * @param string $marketplaceSku Lengow order id
     *
     * @return boolean
     */
    public function log($category, $message = "", $display = false, $marketplaceSku = null)
    {
        if (strlen($message) == 0) {
            return false;
        }
        $decodedMessage = $this->decodeLogMessage($message, 'en_GB');
        $finalMessage = (empty($category) ? '' : '[' . $category . '] ');
        $finalMessage .= '' . (empty($marketplaceSku) ? '' : 'order ' . $marketplaceSku . ' : ');
        $finalMessage .= $decodedMessage;
        if ($display) {
            echo $finalMessage . '<br />';
            flush();
        }
        $log = Mage::getModel('lengow/log');
        return $log->createLog(array('message' => $finalMessage));
    }

    /**
     * Set message with params for translation
     *
     * @param string $key log key
     * @param array $params log parameters
     *
     * @return string
     */
    public function setLogMessage($key, $params = null)
    {
        if (is_null($params) || (is_array($params) && count($params) == 0)) {
            return $key;
        }
        $allParams = array();
        foreach ($params as $param => $value) {
            $value = str_replace(array('|', '=='), array('', ''), $value);
            $allParams[] = $param . '==' . $value;
        }
        $message = $key . '[' . join('|', $allParams) . ']';
        return $message;
    }

    /**
     * Decode message with params for translation
     *
     * @param string $message log message
     * @param string $isoCode iso code for translation
     * @param mixed $params log parameters
     *
     * @return string
     */
    public function decodeLogMessage($message, $isoCode = null, $params = null)
    {
        if (preg_match('/^(([a-z\_]*\.){1,3}[a-z\_]*)(\[(.*)\]|)$/', $message, $result)) {
            if (isset($result[1])) {
                $key = $result[1];
                if (isset($result[4]) && is_null($params)) {
                    $strParam = $result[4];
                    $allParams = explode('|', $strParam);
                    foreach ($allParams as $param) {
                        $result = explode('==', $param);
                        $params[$result[0]] = $result[1];
                    }
                }
                $message = $this->__($key, $params, $isoCode);
            }
        }
        return $message;
    }

    /**
     * Delete log files when too old
     *
     * @param integer $nbDays number of days for deletion
     */
    public function cleanLog($nbDays = 20)
    {
        if ($nbDays <= 0) {
            $nbDays = self::LOG_LIFE;
        }
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $table = $resource->getTableName('lengow/log');
        $query = "DELETE FROM " . $table . " WHERE `date` < DATE_SUB(NOW(),INTERVAL " . $nbDays . " DAY)";
        $writeConnection->query($query);
    }

    /**
     * Get host for generated email
     *
     * @param integer $storeId Magento store id
     *
     * @return string Hostname
     */
    public function getHost($storeId)
    {
        $domain = Mage::app()->getStore($storeId)->getBaseUrl();
        preg_match('`([a-zàâäéèêëôöùûüîïç0-9-]+\.[a-z]+)`', $domain, $out);
        if ($out[1]) {
            return $out[1];
        }
        return $domain;
    }

    /**
     * Get date in local date
     *
     * @param integer $timestamp linux timestamp
     * @param boolean $second see seconds or not
     *
     * @return string in gmt format
     */
    public function getDateInCorrectFormat($timestamp, $second = false)
    {
        if ($second) {
            $format = 'l d F Y @ H:i:s';
        } else {
            $format = 'l d F Y @ H:i';
        }
        return Mage::getModel('core/date')->date($format, $timestamp);
    }

    /**
     * Convert specials chars to html chars
     * Clean None utf-8 characters
     *
     * @param string $value the content
     * @param boolean $html keep html or not
     *
     * @return string
     */
    public function cleanData($value, $html = true)
    {
        if (is_array($value)) {
            return $value;
        }
        $value = nl2br($value);
        $value = Mage::helper('core/string')->cleanString($value);
        // Reject overly long 2 byte sequences, as well as characters above U+10000 and replace with blank
        $value = preg_replace(
            '/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]' .
            '|[\x00-\x7F][\x80-\xBF]+' .
            '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*' .
            '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})' .
            '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
            '',
            $value
        );
        // Reject overly long 3 byte sequences and UTF-16 surrogates and replace with blank
        $value = preg_replace(
            '/\xE0[\x80-\x9F][\x80-\xBF]' . '|\xED[\xA0-\xBF][\x80-\xBF]/S',
            '',
            $value
        );
        if (!$html) {
            $pattern = '@<[\/\!]*?[^<>]*?>@si'; //nettoyage du code HTML
            $value = preg_replace($pattern, ' ', $value);
        }
        $value = preg_replace('/[\s]+/', ' ', $value); //nettoyage des espaces multiples
        $value = trim($value);
        $value = str_replace(
            array(
                '&nbsp;',
                '|',
                '"',
                '’',
                '&#39;',
                '&#150;',
                chr(9),
                chr(10),
                chr(13),
                chr(31),
                chr(30),
                chr(29),
                chr(28),
                "\n",
                "\r"
            ),
            array(
                ' ',
                ' ',
                '\'',
                '\'',
                ' ',
                '-',
                ' ',
                ' ',
                ' ',
                '',
                '',
                '',
                '',
                '',
                ''
            ),
            $value
        );
        return $value;
    }
}
