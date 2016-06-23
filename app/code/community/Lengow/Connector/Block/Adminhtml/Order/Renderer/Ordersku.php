<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Block_Adminhtml_Order_Renderer_Ordersku extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Decorate order sku values
     *
     * @param $row
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $order_sku = $row->getData($this->getColumn()->getIndex());
        $sent_marketplace = $row->getData('sent_marketplace');
        if (is_null($order_sku) && $sent_marketplace == 1) {
            return '<span class="lengow_label lengow_label_not_synchronized">'
            .Mage::helper('lengow_connector')->__('order.table.status_shipped_by_mkp').'</span>';
        } else {
            return $order_sku;
        }
    }
}
