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
     * @var integer life of log files in days
     */
    const LOG_LIFE = 20;

    /**
     * @var string Plugin version
     */
    const PLUGIN_VERSION = '3.0.0';

    /**
     * @var string Plugin code
     */
    const PLUGIN_CODE = 'lengow_connector_setup';

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
     * Write log
     *
     * @param string  $category       Category
     * @param string  $message        log message
     * @param boolean $display        display on screen
     * @param string  $marketplaceSku lengow order id
     *
     * @return boolean
     */
    public function log($category, $message = "", $display = false, $marketplaceSku = null)
    {
        if (strlen($message) == 0) {
            return false;
        }
        $decodedMessage = $this->decodeLogMessage($message, 'en_GB');
        $finalMessage = (empty($category) ? '' : '['.$category.'] ');
        $finalMessage.= ''.(empty($marketplaceSku) ? '' : 'order '.$marketplaceSku.' : ');
        $finalMessage.= $decodedMessage;
        if ($display) {
            echo $finalMessage.'<br />';
            flush();
        }
        $log = Mage::getModel('lengow/log');
        return $log->createLog(array('message' => $finalMessage));
    }

    /**
     * Set message with params for translation
     *
     * @param string $key
     * @param array  $params
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
            $allParams[] = $param.'=='.$value;
        }
        $message = $key.'['.join('|', $allParams).']';
        return $message;
    }

    /**
     * Decode message with params for translation
     *
     * @param string $message
     * @param string $isoCode
     * @param mixed  $params
     *
     * @return string
     */
    public function decodeLogMessage($message, $isoCode = null, $params = null)
    {
        if (preg_match('/^(([a-z\_]*\.){1,3}[a-z\_]*)(\[(.*)\]|)$/', $message, $result)) {
            if (isset($result[1])) {
                $key = $result[1];
            }
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
        return $message;
    }

    /**
     * Delete log files when too old
     *
     * @param integer $nbDays
     */
    public function cleanLog($nbDays = 20)
    {
        if ($nbDays <= 0) {
            $nbDays = self::LOG_LIFE;
        }
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $table = $resource->getTableName('lengow/log');
        $query = "DELETE FROM ".$table." WHERE `date` < DATE_SUB(NOW(),INTERVAL ".$nbDays." DAY)";
        $writeConnection->query($query);
    }


    /**
     * Check if lengow_connector_setup is present in core_ressource table
     *
     * @return boolean
     */
    public function lengowIsInstalled()
    {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $table = $resource->getTableName('core/resource');
        $query = 'SELECT version FROM '.$table.' WHERE code = \''.self::PLUGIN_CODE.'\'';
        $version = $readConnection->fetchOne($query);
        if ($version === self::PLUGIN_VERSION) {
            return true;
        }
        return false;
    }


    /**
     * Get host for generated email
     *
     * @param integer $storeId store id
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
     * @param boolean $second    see seconds or not
     *
     * @return integer $timestamp in gmt format
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
     * @param string  $value The content
     * @param boolean $html  keep html or not
     *
     * @return string $value
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
            '/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]'.
            '|[\x00-\x7F][\x80-\xBF]+'.
            '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*'.
            '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})'.
            '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
            '',
            $value
        );
        // Reject overly long 3 byte sequences and UTF-16 surrogates and replace with blank
        $value = preg_replace(
            '/\xE0[\x80-\x9F][\x80-\xBF]'.'|\xED[\xA0-\xBF][\x80-\xBF]/S',
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
