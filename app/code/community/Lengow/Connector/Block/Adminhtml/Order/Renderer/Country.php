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
        $isoCode = $row->getData($this->getColumn()->getIndex());
        if (!is_null($isoCode) && strlen($isoCode) === 2) {
            $filename = $this->getSkinUrl('lengow/images/flag').DS.strtoupper($isoCode).'.png';
            $countryName = Mage::getModel('directory/country')->loadByCode($isoCode)->getName();
            return '<a class="lengow_tooltip" href="#"><img src="'.$filename.'" />
                <span class="lengow_order_country">'.$countryName.'</span></a>';
        }
    }
}
