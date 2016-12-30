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
 * Model export feed xml
 */
class Lengow_Connector_Model_Export_Feed_Xml extends Lengow_Connector_Model_Export_Feed_Abstract
{
    /**
     * @var string content type
     */
    protected $_contentType = 'application/xml';

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
        return '<?xml version="1.0" encoding="UTF-8"?>'."\r\n".'<catalog>'."\r\n";
    }

    /**
     * Make each data
     *
     * @param array $array All product datas
     * @param array $args  Specific arguments for different format
     *
     * @return string
     */
    public function makeData($array, $args = array())
    {
        $line = '<product>'."\r\n";
        foreach ($this->_fields as $name) {
            $line .= '<'.$name.'><![CDATA['.(isset($array[$name]) ? $array[$name] : '').']]></'.$name.'>'."\r\n";
        }
        $line .= '</product>'."\r\n";
        return $line;
    }

    /**
     * Make footer
     *
     * @return string
     */
    public function makeFooter()
    {
        return '</catalog>';
    }
}
