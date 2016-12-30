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
 * Model export feed abstract
 */
abstract class Lengow_Connector_Model_Export_Feed_Abstract
{
    /**
    * @var array export fields
    */
    protected $_fields;

    abstract public function getContentType();

    abstract public function makeHeader();

    abstract public function makeData($array, $args);

    abstract public function makeFooter();

    /**
    * Set export fields
    */
    public function setFields($array = array())
    {
        $this->_fields = $array;
    }
}
