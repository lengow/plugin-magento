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
    /* Log category codes */
    const CODE_CONNECTION = 'Connection';
    const CODE_SETTING = 'Setting';
    const CODE_CONNECTOR = 'Connector';
    const CODE_EXPORT = 'Export';
    const CODE_IMPORT = 'Import';
    const CODE_ACTION = 'Action';
    const CODE_MAIL_REPORT = 'Mail Report';
    const CODE_ORM = 'Orm';

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
        if ($args[0] === '') {
            return '';
        }
        $message = $args[0];
        if (isset($args[1]) && is_array($args[1])) {
            $params = $args[1];
        }
        if (isset($args[2]) && $args[2] !== '') {
            $isoCode = $args[2];
        }
        return Mage::helper('lengow_connector/translation')->t($message, $params, $isoCode);
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
            Lengow_Connector_Model_Export::PARAM_STORE => $storeId,
            Lengow_Connector_Model_Export::PARAM_TOKEN => Mage::helper('lengow_connector/config')->getToken($storeId),
            '_nosid' => true,
            '_store_to_url' => false,
        );
        if (!empty($additionalParams)) {
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
        $defaultStoreId = Mage::app()->getWebsite(true)->getDefaultGroup()->getDefaultStoreId();
        $defaultParams = array(
            Lengow_Connector_Model_Export::PARAM_TOKEN => Mage::helper('lengow_connector/config')->getToken(),
            '_nosid' => true,
            '_store_to_url' => false,
        );
        if (!empty($additionalParams)) {
            $defaultParams = array_merge($defaultParams, $additionalParams);
        }
        return Mage::getModel('core/url')->setStore($defaultStoreId)->getUrl('lengow/cron', $defaultParams);
    }

    /**
     * Get Toolbox Url
     *
     * @param array $additionalParams additional parameters for toolbox url
     *
     * @return string
     */
    public function getToolboxUrl($additionalParams = array())
    {
        $defaultStoreId = Mage::app()->getWebsite(true)->getDefaultGroup()->getDefaultStoreId();
        $defaultParams = array(
            Lengow_Connector_Helper_Toolbox::PARAM_TOKEN => Mage::helper('lengow_connector/config')->getToken(),
            '_nosid' => true,
            '_store_to_url' => false,
        );
        if (!empty($additionalParams)) {
            $defaultParams = array_merge($defaultParams, $additionalParams);
        }
        return Mage::getModel('core/url')->setStore($defaultStoreId)->getUrl('lengow/toolbox', $defaultParams);
    }

    /**
     * Write log
     *
     * @param string $category log category
     * @param string $message log message
     * @param boolean $display display on screen
     * @param string|null $marketplaceSku Lengow order id
     *
     * @return boolean
     */
    public function log($category, $message = '', $display = false, $marketplaceSku = null)
    {
        if ($message === '') {
            return false;
        }
        $decodedMessage = $this->decodeLogMessage($message, Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE);
        $finalMessage = empty($category) ? '' : '[' . $category . '] ';
        $finalMessage .= '' . (empty($marketplaceSku) ? '' : 'order ' . $marketplaceSku . ' : ');
        $finalMessage .= $decodedMessage;
        if ($display) {
            $date = Mage::getModel('core/date')->date('Y-m-d H:i:s');
            print_r($date . ' - ' . $finalMessage . '<br />');
            flush();
        }
        return Mage::getModel('lengow/log')->createLog(array('message' => $finalMessage));
    }

    /**
     * Set message with params for translation
     *
     * @param string $key log key
     * @param array|null $params log parameters
     *
     * @return string
     */
    public function setLogMessage($key, $params = null)
    {
        if ($params === null || (is_array($params) && empty($params))) {
            return $key;
        }
        $allParams = array();
        foreach ($params as $param => $value) {
            $value = str_replace(array('|', '=='), array('', ''), $value);
            $allParams[] = $param . '==' . $value;
        }
        return $key . '[' . join('|', $allParams) . ']';
    }

    /**
     * Decode message with params for translation
     *
     * @param string $message log message
     * @param string|null $isoCode iso code for translation
     * @param mixed|null $params log parameters
     *
     * @return string
     */
    public function decodeLogMessage($message, $isoCode = null, $params = null)
    {
        if (preg_match('/^(([a-z\_]*\.){1,3}[a-z\_]*)(\[(.*)\]|)$/', $message, $result)) {
            if (isset($result[1])) {
                $key = $result[1];
                if (isset($result[4]) && $params === null) {
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
    public function cleanLog($nbDays = Lengow_Connector_Model_Log::LOG_LIFE)
    {
        if ($nbDays <= 0) {
            $nbDays = Lengow_Connector_Model_Log::LOG_LIFE;
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
     * @return string|false
     */
    public function getHost($storeId)
    {
        try {
            $domain = Mage::app()->getStore($storeId)->getBaseUrl();
            preg_match('`([a-zàâäéèêëôöùûüîïç0-9-]+\.[a-z]+)`', $domain, $out);
            if ($out[1]) {
                return $out[1];
            }
            return $domain;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get date in local date
     *
     * @param integer $timestamp linux timestamp
     * @param boolean $second see seconds or not
     *
     * @return string
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
     * @param string|array $value the content
     * @param boolean $html keep html or not
     *
     * @return string|array
     */
    public function cleanData($value, $html = true)
    {
        if (is_array($value)) {
            return $value;
        }
        $value = nl2br($value);
        $value = Mage::helper('core/string')->cleanString($value);
        // reject overly long 2 byte sequences, as well as characters above U+10000 and replace with blank
        $value = preg_replace(
            '/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]' .
            '|[\x00-\x7F][\x80-\xBF]+' .
            '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*' .
            '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})' .
            '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
            '',
            $value
        );
        // reject overly long 3 byte sequences and UTF-16 surrogates and replace with blank
        $value = preg_replace(
            '/\xE0[\x80-\x9F][\x80-\xBF]' . '|\xED[\xA0-\xBF][\x80-\xBF]/S',
            '',
            $value
        );
        if (!$html) {
            $pattern = '@<[\/\!]*?[^<>]*?>@si';
            $value = preg_replace($pattern, ' ', $value);
        }
        $value = preg_replace('/[\s]+/', ' ', $value);
        $value = trim($value);
        return str_replace(
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
                "\r",
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
                '',
            ),
            $value
        );

    }

    /**
     * Replace all accented chars by their equivalent non accented chars
     *
     * @param string $str the content
     *
     * @return string
     */
    public function replaceAccentedChars($str)
    {
        /* One source among others:
            http://www.tachyonsoft.com/uc0000.htm
            http://www.tachyonsoft.com/uc0001.htm
            http://www.tachyonsoft.com/uc0004.htm
        */
        $patterns = array(
            /* Lowercase */
            /* a  */
            '/[\x{00E0}\x{00E1}\x{00E2}\x{00E3}\x{00E4}\x{00E5}\x{0101}\x{0103}\x{0105}\x{0430}\x{00C0}-\x{00C3}\x{1EA0}-\x{1EB7}]/u',
            /* b  */
            '/[\x{0431}]/u',
            /* c  */
            '/[\x{00E7}\x{0107}\x{0109}\x{010D}\x{0446}]/u',
            /* d  */
            '/[\x{010F}\x{0111}\x{0434}\x{0110}]/u',
            /* e  */
            '/[\x{00E8}\x{00E9}\x{00EA}\x{00EB}\x{0113}\x{0115}\x{0117}\x{0119}\x{011B}\x{0435}\x{044D}\x{00C8}-\x{00CA}\x{1EB8}-\x{1EC7}]/u',
            /* f  */
            '/[\x{0444}]/u',
            /* g  */
            '/[\x{011F}\x{0121}\x{0123}\x{0433}\x{0491}]/u',
            /* h  */
            '/[\x{0125}\x{0127}]/u',
            /* i  */
            '/[\x{00EC}\x{00ED}\x{00EE}\x{00EF}\x{0129}\x{012B}\x{012D}\x{012F}\x{0131}\x{0438}\x{0456}\x{00CC}\x{00CD}\x{1EC8}-\x{1ECB}\x{0128}]/u',
            /* j  */
            '/[\x{0135}\x{0439}]/u',
            /* k  */
            '/[\x{0137}\x{0138}\x{043A}]/u',
            /* l  */
            '/[\x{013A}\x{013C}\x{013E}\x{0140}\x{0142}\x{043B}]/u',
            /* m  */
            '/[\x{043C}]/u',
            /* n  */
            '/[\x{00F1}\x{0144}\x{0146}\x{0148}\x{0149}\x{014B}\x{043D}]/u',
            /* o  */
            '/[\x{00F2}\x{00F3}\x{00F4}\x{00F5}\x{00F6}\x{00F8}\x{014D}\x{014F}\x{0151}\x{043E}\x{00D2}-\x{00D5}\x{01A0}\x{01A1}\x{1ECC}-\x{1EE3}]/u',
            /* p  */
            '/[\x{043F}]/u',
            /* r  */
            '/[\x{0155}\x{0157}\x{0159}\x{0440}]/u',
            /* s  */
            '/[\x{015B}\x{015D}\x{015F}\x{0161}\x{0441}]/u',
            /* ss */
            '/[\x{00DF}]/u',
            /* t  */
            '/[\x{0163}\x{0165}\x{0167}\x{0442}]/u',
            /* u  */
            '/[\x{00F9}\x{00FA}\x{00FB}\x{00FC}\x{0169}\x{016B}\x{016D}\x{016F}\x{0171}\x{0173}\x{0443}\x{00D9}-\x{00DA}\x{0168}\x{01AF}\x{01B0}\x{1EE4}-\x{1EF1}]/u',
            /* v  */
            '/[\x{0432}]/u',
            /* w  */
            '/[\x{0175}]/u',
            /* y  */
            '/[\x{00FF}\x{0177}\x{00FD}\x{044B}\x{1EF2}-\x{1EF9}\x{00DD}]/u',
            /* z  */
            '/[\x{017A}\x{017C}\x{017E}\x{0437}]/u',
            /* ae */
            '/[\x{00E6}]/u',
            /* ch */
            '/[\x{0447}]/u',
            /* kh */
            '/[\x{0445}]/u',
            /* oe */
            '/[\x{0153}]/u',
            /* sh */
            '/[\x{0448}]/u',
            /* shh*/
            '/[\x{0449}]/u',
            /* ya */
            '/[\x{044F}]/u',
            /* ye */
            '/[\x{0454}]/u',
            /* yi */
            '/[\x{0457}]/u',
            /* yo */
            '/[\x{0451}]/u',
            /* yu */
            '/[\x{044E}]/u',
            /* zh */
            '/[\x{0436}]/u',

            /* Uppercase */
            /* A  */
            '/[\x{0100}\x{0102}\x{0104}\x{00C0}\x{00C1}\x{00C2}\x{00C3}\x{00C4}\x{00C5}\x{0410}]/u',
            /* B  */
            '/[\x{0411}]/u',
            /* C  */
            '/[\x{00C7}\x{0106}\x{0108}\x{010A}\x{010C}\x{0426}]/u',
            /* D  */
            '/[\x{010E}\x{0110}\x{0414}]/u',
            /* E  */
            '/[\x{00C8}\x{00C9}\x{00CA}\x{00CB}\x{0112}\x{0114}\x{0116}\x{0118}\x{011A}\x{0415}\x{042D}]/u',
            /* F  */
            '/[\x{0424}]/u',
            /* G  */
            '/[\x{011C}\x{011E}\x{0120}\x{0122}\x{0413}\x{0490}]/u',
            /* H  */
            '/[\x{0124}\x{0126}]/u',
            /* I  */
            '/[\x{0128}\x{012A}\x{012C}\x{012E}\x{0130}\x{0418}\x{0406}]/u',
            /* J  */
            '/[\x{0134}\x{0419}]/u',
            /* K  */
            '/[\x{0136}\x{041A}]/u',
            /* L  */
            '/[\x{0139}\x{013B}\x{013D}\x{0139}\x{0141}\x{041B}]/u',
            /* M  */
            '/[\x{041C}]/u',
            /* N  */
            '/[\x{00D1}\x{0143}\x{0145}\x{0147}\x{014A}\x{041D}]/u',
            /* O  */
            '/[\x{00D3}\x{014C}\x{014E}\x{0150}\x{041E}]/u',
            /* P  */
            '/[\x{041F}]/u',
            /* R  */
            '/[\x{0154}\x{0156}\x{0158}\x{0420}]/u',
            /* S  */
            '/[\x{015A}\x{015C}\x{015E}\x{0160}\x{0421}]/u',
            /* T  */
            '/[\x{0162}\x{0164}\x{0166}\x{0422}]/u',
            /* U  */
            '/[\x{00D9}\x{00DA}\x{00DB}\x{00DC}\x{0168}\x{016A}\x{016C}\x{016E}\x{0170}\x{0172}\x{0423}]/u',
            /* V  */
            '/[\x{0412}]/u',
            /* W  */
            '/[\x{0174}]/u',
            /* Y  */
            '/[\x{0176}\x{042B}]/u',
            /* Z  */
            '/[\x{0179}\x{017B}\x{017D}\x{0417}]/u',
            /* AE */
            '/[\x{00C6}]/u',
            /* CH */
            '/[\x{0427}]/u',
            /* KH */
            '/[\x{0425}]/u',
            /* OE */
            '/[\x{0152}]/u',
            /* SH */
            '/[\x{0428}]/u',
            /* SHH*/
            '/[\x{0429}]/u',
            /* YA */
            '/[\x{042F}]/u',
            /* YE */
            '/[\x{0404}]/u',
            /* YI */
            '/[\x{0407}]/u',
            /* YO */
            '/[\x{0401}]/u',
            /* YU */
            '/[\x{042E}]/u',
            /* ZH */
            '/[\x{0416}]/u',
        );

        // ö to oe
        // å to aa
        // ä to ae
        $replacements = array(
            'a',
            'b',
            'c',
            'd',
            'e',
            'f',
            'g',
            'h',
            'i',
            'j',
            'k',
            'l',
            'm',
            'n',
            'o',
            'p',
            'r',
            's',
            'ss',
            't',
            'u',
            'v',
            'w',
            'y',
            'z',
            'ae',
            'ch',
            'kh',
            'oe',
            'sh',
            'shh',
            'ya',
            'ye',
            'yi',
            'yo',
            'yu',
            'zh',
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
            'H',
            'I',
            'J',
            'K',
            'L',
            'M',
            'N',
            'O',
            'P',
            'R',
            'S',
            'T',
            'U',
            'V',
            'W',
            'Y',
            'Z',
            'AE',
            'CH',
            'KH',
            'OE',
            'SH',
            'SHH',
            'YA',
            'YE',
            'YI',
            'YO',
            'YU',
            'ZH',
        );
        return preg_replace($patterns, $replacements, $str);
    }
}
