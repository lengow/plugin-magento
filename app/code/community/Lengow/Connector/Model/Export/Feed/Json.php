<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Model_Export_Feed_Json extends Lengow_Connector_Model_Export_Feed_Abstract
{

    protected $_content_type = 'application/json';

    public function getContentType()
    {
        return $this->_content_type;
    }

    public function makeHeader()
    {
        return '{"catalog":[';
    }

    public function makeData($array, $args = array())
    {
        foreach ($this->_fields as $name) {
            $json_array[$name] = array_key_exists($name, $array) ? $array[$name] : '';
        }
        $line = Mage::helper('core')->jsonEncode($json_array) . (!$args['last'] ? ',' : '');
        return $line;
    }

    public function makeFooter()
    {
        return ']}';
    }
}
