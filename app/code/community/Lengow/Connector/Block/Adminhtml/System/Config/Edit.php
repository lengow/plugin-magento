<?php
/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Block_Adminhtml_System_Config_Edit extends Mage_Adminhtml_Block_System_Config_Edit
{
    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct();
        if ((string)$this->_section->attributes()->module == "lengow_connector") {
            $this->setTitle(Mage::helper('lengow_connector/translation')->t((string)$this->_section->label));
        }
    }
}
