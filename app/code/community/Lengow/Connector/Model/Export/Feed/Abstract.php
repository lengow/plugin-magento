<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
abstract class Lengow_Connector_Model_Export_Feed_Abstract
{
    /**
     * Version.
     */
    const VERSION = '1.0.0';

    protected $_fields;

    abstract public function getContentType();

    abstract public function makeHeader();

    abstract public function makeData($array, $args);

    abstract public function makeFooter();

    public function setFields($array = array())
    {
        $this->_fields = $array;
    }
}
