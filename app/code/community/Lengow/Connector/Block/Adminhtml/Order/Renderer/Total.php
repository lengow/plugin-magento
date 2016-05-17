<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Block_Adminhtml_Order_Renderer_Total extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value =  $row->getData($this->getColumn()->getIndex());
        if (!is_null($row->getData('currency')) && $value != '') {
            $currency_symbol = Mage::app()->getLocale()->currency($row->getData('currency'))->getSymbol();
        } else {
            $currency_symbol = '';
        }
        return $value.' '.$currency_symbol;
    }
}
