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
 * Model export feed yaml
 */
class Lengow_Connector_Model_Export_Feed_Yaml extends Lengow_Connector_Model_Export_Feed_Abstract
{
    /**
     * @var string content type
     */
    protected $_contentType = 'text/x-yaml';

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
        return '"catalog":' . "\r\n";
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
        if ($args['max_character'] % 2 == 1) {
            $maxCharacter = $args['max_character'] + 1;
        } else {
            $maxCharacter = $args['max_character'] + 2;
        }
        $line = '  ' . '"product":' . "\r\n";
        foreach ($this->_fields as $name) {
            $line .= '    ' . '"' . $name . '":' .
                $this->_addSpaces($name, $maxCharacter) . (isset($array[$name]) ? $array[$name] : '') . "\r\n";
        }
        return $line;
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
     * For YAML, add spaces to have good indentation
     *
     * @param string $name The fielname
     * @param string $size The max spaces
     *
     * @return string
     */
    private function _addSpaces($name, $size)
    {
        $strlen = strlen($name);
        $spaces = '';
        for ($i = $strlen; $i < $size; $i++) {
            $spaces .= ' ';
        }
        return $spaces;
    }
}
