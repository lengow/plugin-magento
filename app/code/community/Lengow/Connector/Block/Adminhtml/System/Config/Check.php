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
 * @subpackage  Block
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Block adminhtml system config check
 */
class Lengow_Connector_Block_Adminhtml_System_Config_Check
    extends Mage_Adminhtml_Block_Template
    implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * @var Varien_Data_Form_Element_Abstract Magento Varien data form element instance
     */
    protected $_element;

    /**
     * Construct
     */
    protected function _construct()
    {
        $this->setTemplate('widget/form/renderer/fieldset.phtml');
    }

    /**
     * Get html element
     */
    public function getElement()
    {
        return $this->_element;
    }

    /**
     * Generate html for button
     * 
     * @see Mage_Adminhtml_Block_System_Config_Form_Field::_getElementHtml()
     *
     * @param Varien_Data_Form_Element_Abstract $element Magento Varien data form element instance
     *
     * @return string $html 
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = $this->getLayout()
                    ->createBlock('lengow/adminhtml_system_config_check_point', 'lengow_checkpoint')
                    ->toHtml();
        $element->setHtmlContent($html);
        $this->_element = $element;
        return $this->toHtml();
    }
}
