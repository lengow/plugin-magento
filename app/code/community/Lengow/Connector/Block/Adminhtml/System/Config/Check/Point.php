<?php
/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Block_Adminhtml_System_Config_Check_Point extends Mage_Adminhtml_Block_Template
{

    protected $_element;

    protected $_helper;

    public function __construct()
    {
        $this->setTemplate('lengow/check/point.phtml');
        $this->_helper = Mage::helper('lengow_connector/security');
        parent::_construct();
    }

    public function getPluginVersion()
    {
        return $this->_helper->getPluginVersion();
    }

    public function getMagentoVersion()
    {
        return $this->_helper->getMagentoVersion();
    }

    public function checkValidMagentoVersion()
    {
        return $this->_helper->checkValidMagentoVersion();
    }

    public function getRealIP()
    {
        return $this->_helper->getRealIP();
    }
}
