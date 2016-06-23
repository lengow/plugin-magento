<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Block_Adminhtml_Action_Renderer_Status extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Decorate status values
     *
     * @param $status
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $status = $row->getData($this->getColumn()->getIndex());
        if ($status == 0) {
            return '<span class="lengow_label orange">'
                .Mage::helper('lengow_connector')->__('toolbox.table.state_processing').'</span>';
        } else {
            return '<span class="lengow_label">'
                .Mage::helper('lengow_connector')->__('toolbox.table.state_complete').'</span>';
        }
    }
}
