<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Model_Resource_Import_Action_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Construct
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('lengow/import_action');
    }
}
