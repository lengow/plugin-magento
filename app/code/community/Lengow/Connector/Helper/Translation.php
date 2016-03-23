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
    protected static $translation = null;

    public $fallbackIsoCode = 'en_GB';

    protected $isoCode = null;

    public static $forceIsoCode = null;

    public function __construct()
    {
        $this->isoCode = Mage::app()->getLocale()->getLocaleCode();
    }

    /**
     * Translate message
     *
     * @param string $message   localization key
     * @param array  $args      replace word in string
     * @param array  $iso_code  iso code
     *
     * @return mixed
     */
    public function t($message, $args = array(), $iso_code = null)
    {
        if (!is_null(self::$forceIsoCode)) {
            $iso_code = self::$forceIsoCode;
        }
        if (is_null($iso_code)) {
            $iso_code = $this->isoCode;
        }
        if (!isset(self::$translation[$iso_code])) {
            $this->loadFile($iso_code);
        }
        if (isset(self::$translation[$iso_code][$message])) {
            return $this->translateFinal(self::$translation[$iso_code][$message], $args);
        } else {
            if (!isset(self::$translation[$this->fallbackIsoCode])) {
                $this->loadFile($this->fallbackIsoCode);
            }
            if (isset(self::$translation[$this->fallbackIsoCode][$message])) {
                return $this->translateFinal(self::$translation[$this->fallbackIsoCode][$message], $args);
            } else {
                return 'Missing Translation ['.$message.']';
            }
        }
    }

    /**
     * Translate string
     *
     * @param $text
     * @param $args
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
     * @param string $iso_code
     * @param string $filename file location
     *
     * @return boolean
     */
    public function loadFile($iso_code, $filename = null)
    {
        if (!$filename) {
            $filename = Mage::getModuleDir('locale', 'Lengow_Connector').DS.$iso_code.'.csv';
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
        self::$translation[$iso_code] = $translation;
        return count($translation) > 0;
    }
}
