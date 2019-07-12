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
 * Helper translation
 */
class Lengow_Connector_Helper_Translation extends Mage_Core_Helper_Abstract
{
    /**
     * @var array all translations
     */
    protected static $_translation = null;

    /**
     * @var string fallback iso code
     */
    public $fallbackIsoCode = 'en_GB';

    /**
     * @var string iso code
     */
    protected $_isoCode = null;

    /**
     * @var string force iso code for log
     */
    public static $forceIsoCode = null;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->_isoCode = Mage::app()->getLocale()->getLocaleCode();
    }

    /**
     * Translate message
     *
     * @param string $message localization key
     * @param array $args replace word in string
     * @param array $isoCode iso code
     *
     * @return string
     */
    public function t($message, $args = array(), $isoCode = null)
    {
        if (!is_null(self::$forceIsoCode)) {
            $isoCode = self::$forceIsoCode;
        }
        if (is_null($isoCode)) {
            $isoCode = $this->_isoCode;
        }
        if (!isset(self::$_translation[$isoCode])) {
            $this->loadFile($isoCode);
        }
        if (isset(self::$_translation[$isoCode][$message])) {
            return $this->translateFinal(self::$_translation[$isoCode][$message], $args);
        } else {
            if (!isset(self::$_translation[$this->fallbackIsoCode])) {
                $this->loadFile($this->fallbackIsoCode);
            }
            if (isset(self::$_translation[$this->fallbackIsoCode][$message])) {
                return $this->translateFinal(self::$_translation[$this->fallbackIsoCode][$message], $args);
            } else {
                return 'Missing Translation [' . $message . ']';
            }
        }
    }

    /**
     * Translate string
     *
     * @param string $text localization key
     * @param array $args replace word in string
     *
     * @return string
     */
    protected function translateFinal($text, $args)
    {
        if ($args) {
            $params = array();
            $values = array();
            foreach ($args as $key => $value) {
                $params[] = '%{' . $key . '}';
                $values[] = $value;
            }
            return str_replace($params, $values, $text);
        } else {
            return $text;
        }
    }

    /**
     * Load csv file
     *
     * @param string $isoCode translation iso code
     * @param string $filename file location
     *
     * @return boolean
     */
    public function loadFile($isoCode, $filename = null)
    {
        if (!$filename) {
            $filename = Mage::getModuleDir('locale', 'Lengow_Connector') . DS . $isoCode . '.csv';
        }
        $translation = array();
        if (file_exists($filename)) {
            if (($handle = fopen($filename, 'r')) !== false) {
                while (($data = fgetcsv($handle, 1000, '|')) !== false) {
                    $translation[$data[0]] = $data[1];
                }
                fclose($handle);
            }
        }
        self::$_translation[$isoCode] = $translation;
        return count($translation) > 0;
    }
}
