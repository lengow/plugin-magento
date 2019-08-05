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
                $this->_clean(substr(str_replace('-', '_', $name), 0, 59)) .
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

    /**
     * Clean header
     *
     * @param string $str The fieldname
     *
     * @return string
     */
    private function _clean($str)
    {
        $patterns = array(
            /* Lowercase */
            '/[\x{0105}\x{00E0}\x{00E1}\x{00E2}\x{00E3}\x{00E4}\x{00E5}]/u',
            '/[\x{00E7}\x{010D}\x{0107}]/u',
            '/[\x{010F}]/u',
            '/[\x{00E8}\x{00E9}\x{00EA}\x{00EB}\x{011B}\x{0119}]/u',
            '/[\x{00EC}\x{00ED}\x{00EE}\x{00EF}]/u',
            '/[\x{0142}\x{013E}\x{013A}]/u',
            '/[\x{00F1}\x{0148}]/u',
            '/[\x{00F2}\x{00F3}\x{00F4}\x{00F5}\x{00F6}\x{00F8}]/u',
            '/[\x{0159}\x{0155}]/u',
            '/[\x{015B}\x{0161}]/u',
            '/[\x{00DF}]/u',
            '/[\x{0165}]/u',
            '/[\x{00F9}\x{00FA}\x{00FB}\x{00FC}\x{016F}]/u',
            '/[\x{00FD}\x{00FF}]/u',
            '/[\x{017C}\x{017A}\x{017E}]/u',
            '/[\x{00E6}]/u',
            '/[\x{0153}]/u',
            /* Uppercase */
            '/[\x{0104}\x{00C0}\x{00C1}\x{00C2}\x{00C3}\x{00C4}\x{00C5}]/u',
            '/[\x{00C7}\x{010C}\x{0106}]/u',
            '/[\x{010E}]/u',
            '/[\x{00C8}\x{00C9}\x{00CA}\x{00CB}\x{011A}\x{0118}]/u',
            '/[\x{0141}\x{013D}\x{0139}]/u',
            '/[\x{00D1}\x{0147}]/u',
            '/[\x{00D3}]/u',
            '/[\x{0158}\x{0154}]/u',
            '/[\x{015A}\x{0160}]/u',
            '/[\x{0164}]/u',
            '/[\x{00D9}\x{00DA}\x{00DB}\x{00DC}\x{016E}]/u',
            '/[\x{017B}\x{0179}\x{017D}]/u',
            '/[\x{00C6}]/u',
            '/[\x{0152}]/u',
        );
        $replacements = array(
            'a',
            'c',
            'd',
            'e',
            'i',
            'l',
            'n',
            'o',
            'r',
            's',
            'ss',
            't',
            'u',
            'y',
            'z',
            'ae',
            'oe',
            'A',
            'C',
            'D',
            'E',
            'L',
            'N',
            'O',
            'R',
            'S',
            'T',
            'U',
            'Z',
            'AE',
            'OE',
        );
        return preg_replace($patterns, $replacements, $str);
    }
}
