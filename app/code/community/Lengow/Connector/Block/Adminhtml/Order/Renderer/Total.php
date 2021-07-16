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
 * Block adminhtml order renderer total
 */
class Lengow_Connector_Block_Adminhtml_Order_Renderer_Total
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Decorate total values
     *
     * @param Varien_Object $row Magento Varien object instance
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        if ($row->getData('currency') !== null && $value !== '') {
            $currencySymbol = Mage::app()->getLocale()->currency($row->getData('currency'))->getSymbol();
        } else {
            $currencySymbol = '';
        }
        $nbProduct = $this->helper('lengow_connector')->decodeLogMessage(
            'order.table.nb_product',
            null,
            array('nb' => $row->getData('order_item'))
        );
        return '
            <div class="lengow_tooltip">'
                . $value . ' ' . $currencySymbol .
                '<span class="lengow_order_amount">' . $nbProduct . '</span>
            </div>
        ';
    }
}
