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

        if (isset($args[1]) && is_array($args[1])) {
            return $t->t($args[0], $args[1]);
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
        $decoded_message = $this->decodeLogMessage($message, 'en_GB');
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
        $all_params = array();
        foreach ($params as $param => $value) {
            $value = str_replace(array('|', '=='), array('', ''), $value);
            $all_params[] = $param.'=='.$value;
        }
        $message = $key.'['.join('|', $all_params).']';
        return $message;
    }

    /**
     * Decode message with params for translation
     *
     * @param string $message
     * @param string $iso_code
     * @param mixed  $params
     *
     * @return string
     */
    public function decodeLogMessage($message, $iso_code = null, $params = null)
    {
        if (preg_match('/^(([a-z\_]*\.){1,3}[a-z\_]*)(\[(.*)\]|)$/', $message, $result)) {
            if (isset($result[1])) {
                $key = $result[1];
            }
            if (isset($result[4]) && is_null($params)) {
                $str_param = $result[4];
                $all_params = explode('|', $str_param);
                foreach ($all_params as $param) {
                    $result = explode('==', $param);
                    $params[$result[0]] = $result[1];
                }
            }
            $message = $this->__($key, $params, $iso_code);
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
        if ($nbDays<=0) {
            $nbDays = self::LOG_LIFE;
        }
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $table = $resource->getTableName('lengow/log');
        $query = "DELETE FROM ".$table." WHERE `date` < DATE_SUB(NOW(),INTERVAL ".$nbDays." DAY)";
        $writeConnection->query($query);
    }

    /**
     * Convert specials chars to html chars
     * Clean None utf-8 characters
     *
     * @param string $value The content
     *
     * @return string $value
     */
    public function cleanData($value)
    {
        if (is_array($value)) {
            return $value;
        }
        $value = nl2br($value);
        $value = Mage::helper('core/string')->cleanString($value);
        // Reject overly long 2 byte sequences, as well as characters above U+10000 and replace with blank
        $value = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]' .
            '|[\x00-\x7F][\x80-\xBF]+' .
            '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*' .
            '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})' .
            '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S', '', $value);
        // Reject overly long 3 byte sequences and UTF-16 surrogates and replace with blank
        $value = preg_replace('/\xE0[\x80-\x9F][\x80-\xBF]' .
            '|\xED[\xA0-\xBF][\x80-\xBF]/S', '', $value);
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

    /**
     * Check if import is already in process
     *
     * @return boolean
     */
    public function importIsInProcess()
    {
        $timestamp = Mage::getStoreConfig('lengow_import_options/advanced/import_in_progress');
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
        $timestamp = Mage::getStoreConfig('lengow_import_options/advanced/import_in_progress');
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
        return Mage::getModel('core/config')->saveConfig(
            'lengow_import_options/advanced/import_in_progress',
            time()
        );
    }

    /**
     * Set import to finished
     *
     * @return boolean
     */
    public function setImportEnd()
    {
        return Mage::getModel('core/config')->saveConfig(
            'lengow_import_options/advanced/import_in_progress',
            -1
        );
    }
}
