<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Block_Adminhtml_Order_Renderer_State extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value =  $row->getData($this->getColumn()->getIndex());
        if (empty($value)) {
            $value = 'not_synchronized';
        }
        return '<span class="lengow_label lengow_label_'.$value.'">'
            .Mage::helper('lengow_connector')->__('order.table.status_'.$value).'</span>';
    }
}
