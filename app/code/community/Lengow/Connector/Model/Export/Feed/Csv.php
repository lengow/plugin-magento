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
 * @subpackage  Model
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Model export feed csv
 */
class Lengow_Connector_Model_Export_Feed_Csv extends Lengow_Connector_Model_Export_Feed_Abstract
{
    /**
     * @var string CSV separator
     */
    public static $csvSeparator = '|';

    /**
     * @var string CSV protection
     */
    public static $csvProtection = '"';

    /**
     * @var string CSV End of line
     */
    public static $csvEol = "\r\n";

    /**
     * @var string content type
     */
    protected $_contentType = 'text/csv';

    /**
     * Get content type
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->_contentType;
    }

    /**
     * Make header
     *
     * @return string
     */
    public function makeHeader()
    {
        $head = '';
        foreach ($this->_fields as $name) {
            $head .= self::$csvProtection .
                Mage::helper('lengow_connector')->replaceAccentedChars(substr(str_replace('-', '_', $name), 0, 59)) .
                self::$csvProtection . self::$csvSeparator;
        }
        return rtrim($head, self::$csvSeparator) . self::$csvEol;
    }

    /**
     * Make each data
     *
     * @param array $array All product datas
     * @param array $args Specific arguments for different format
     *
     * @return string
     */
    public function makeData($array, $args = array())
    {
        $line = '';
        foreach ($this->_fields as $name) {
            $line .= self::$csvProtection .
                (array_key_exists($name, $array)
                    ? (str_replace(array(self::$csvProtection, '\\'), '', $array[$name]))
                    : ''
                ) . self::$csvProtection . self::$csvSeparator;
        }
        return rtrim($line, self::$csvSeparator) . self::$csvEol;
    }

    /**
     * Make footer
     *
     * @return string
     */
    public function makeFooter()
    {
        return '';
    }
}
