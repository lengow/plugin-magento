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
 * Block adminhtml order renderer country
 */
class Lengow_Connector_Block_Adminhtml_Order_Renderer_Country
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Decorate country values
     *
     * @param Varien_Object $row Magento Varien object instance
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $isoCode = $row->getData($this->getColumn()->getIndex());
        if ($isoCode !== null && strlen($isoCode) === 2) {
            $filename = $this->getSkinUrl('lengow/images/flag') . DS . strtoupper($isoCode) . '.png';
            $countryName = Mage::getModel('directory/country')->loadByCode($isoCode)->getName();
            return '<a class="lengow_tooltip" href="#"><img src="' . $filename . '" />
                <span class="lengow_order_country">' . $countryName . '</span></a>';
        }
    }
}
