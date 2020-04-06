<?php
/**
 * Copyright 2020 Lengow SAS
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
 * @copyright   2020 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Block adminhtml order renderer types
 */
class Lengow_Connector_Block_Adminhtml_Order_Renderer_Types
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Decorate types values
     *
     * @param Varien_Object $row Magento varian object instance
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        $return = '<div>';
        $orderTypes = $value !== null ? json_decode($value, true) : array();
        if (isset($orderTypes[Lengow_Connector_Model_Import_Order::TYPE_EXPRESS])
            || isset($orderTypes[Lengow_Connector_Model_Import_Order::TYPE_PRIME])
        ) {
            $iconLabel = isset($orderTypes[Lengow_Connector_Model_Import_Order::TYPE_PRIME])
                ? $orderTypes[Lengow_Connector_Model_Import_Order::TYPE_PRIME]
                : $orderTypes[Lengow_Connector_Model_Import_Order::TYPE_EXPRESS];
            $return .= $this->_generateOrderTypeIcon($iconLabel, 'orange-light', 'mod-chrono');
        }
        if (isset($orderTypes[Lengow_Connector_Model_Import_Order::TYPE_DELIVERED_BY_MARKETPLACE])
            || (bool)$row->getData('sent_marketplace')
        ) {
            $iconLabel = isset($orderTypes[Lengow_Connector_Model_Import_Order::TYPE_DELIVERED_BY_MARKETPLACE])
                ? $orderTypes[Lengow_Connector_Model_Import_Order::TYPE_DELIVERED_BY_MARKETPLACE]
                : Lengow_Connector_Model_Import_Order::LABEL_FULFILLMENT;
            $return .= $this->_generateOrderTypeIcon($iconLabel, 'green-light', 'mod-delivery');
        }
        if (isset($orderTypes[Lengow_Connector_Model_Import_Order::TYPE_BUSINESS])) {
            $iconLabel = $orderTypes[Lengow_Connector_Model_Import_Order::TYPE_BUSINESS];
            $return .= $this->_generateOrderTypeIcon($iconLabel, 'blue-light', 'mod-pro');
        }
        $return .= '</div>';
        return $return;
    }

    /**
     * Generate order type icon
     *
     * @param string $iconLabel icon label for tooltip
     * @param string $iconColor icon background color
     * @param string $iconMod icon mod for image
     *
     * @return string
     */
    private function _generateOrderTypeIcon($iconLabel, $iconColor, $iconMod)
    {
        return '
            <div class="lgw-label ' . $iconColor . ' icon-solo lengow_tooltip">
                <a class="lgw-icon ' . $iconMod . '">
                    <span class="lengow_order_types">' . $iconLabel . '</span>
                </a>
            </div>
        ';
    }
}
