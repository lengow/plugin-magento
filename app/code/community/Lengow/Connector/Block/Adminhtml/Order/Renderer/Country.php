<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Block_Adminhtml_Order_Renderer_Country
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Decorate country values
     *
     * @param $row
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $iso_code = $row->getData($this->getColumn()->getIndex());
        if (!is_null($iso_code) && strlen($iso_code) === 2) {
            $filename = $this->getSkinUrl('lengow/images/flag').DS.strtoupper($iso_code).'.png';
            $country_name = Mage::getModel('directory/country')->loadByCode($iso_code)->getName();
            return '<a class="lengow_tooltip" href="#"><img src="'.$filename.'" />
                <span class="lengow_order_country">'.$country_name.'</span></a>';
        }
    }
}
