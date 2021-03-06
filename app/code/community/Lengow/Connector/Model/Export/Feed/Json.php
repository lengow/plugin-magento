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
 * Model export feed json
 */
class Lengow_Connector_Model_Export_Feed_Json extends Lengow_Connector_Model_Export_Feed_Abstract
{
    /**
     * @var string content type
     */
    protected $_contentType = 'application/json';

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
        return '{"catalog":[';
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
        $jsonArray = array();
        foreach ($this->_fields as $name) {
            $jsonArray[$name] = array_key_exists($name, $array) ? $array[$name] : '';
        }
        $line = Mage::helper('core')->jsonEncode($jsonArray) . (!$args['last'] ? ',' : '');
        return $line;
    }

    /**
     * Make footer
     *
     * @return string
     */
    public function makeFooter()
    {
        return ']}';
    }
}
