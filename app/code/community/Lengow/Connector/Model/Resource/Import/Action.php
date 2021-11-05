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
 * Model resource import action
 */
class Lengow_Connector_Model_Resource_Import_Action extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Construct
     */
    protected function _construct()
    {
        $this->_init('lengow/import_action', Lengow_Connector_Model_Import_Action::FIELD_ID);
    }
}
