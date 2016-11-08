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
class Lengow_Connector_Helper_Translation extends Mage_Core_Helper_Abstract
{
    protected static $_translation = null;

    public $fallbackIsoCode = 'en_GB';

    protected $_isoCode = null;

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
     * @param array  $args    replace word in string
     * @param array  $isoCode iso code
     *
     * @return mixed
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
                return 'Missing Translation ['.$message.']';
            }
        }
    }

    /**
     * Translate string
     *
     * @param string $text localization key
     * @param array  $args replace word in string
     *
     * @return string Final Translate string
     */
    protected function translateFinal($text, $args)
    {
        if ($args) {
            $params = array();
            $values = array();
            foreach ($args as $key => $value) {
                $params[] = '%{'.$key.'}';
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
     * @param string $isoCode
     * @param string $filename file location
     *
     * @return boolean
     */
    public function loadFile($isoCode, $filename = null)
    {
        if (!$filename) {
            $filename = Mage::getModuleDir('locale', 'Lengow_Connector').DS.$isoCode.'.csv';
        }
        $translation = array();
        if (file_exists($filename)) {
            if (($handle = fopen($filename, "r")) !== false) {
                while (($data = fgetcsv($handle, 1000, "|")) !== false) {
                    $translation[$data[0]] = $data[1];
                }
                fclose($handle);
            }
        }
        self::$_translation[$isoCode] = $translation;
        return count($translation) > 0;
    }
}
