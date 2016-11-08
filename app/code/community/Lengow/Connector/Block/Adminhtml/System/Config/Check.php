<?php
/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Block_Adminhtml_System_Config_Check
    extends Mage_Adminhtml_Block_Template
    implements Varien_Data_Form_Element_Renderer_Interface
{
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
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string $html
     *
     * @see Mage_Adminhtml_Block_System_Config_Form_Field::_getElementHtml()
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
