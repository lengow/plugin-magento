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
 * Block adminhtml order renderer magentostate
 */
class Lengow_Connector_Block_Adminhtml_Order_Renderer_Magentostate
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Decorate magento state values
     *
     * @param Varien_Object $row Magento varian object instance
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $magentoState = $row->getData('status');
        $sentMarketplace = $row->getData('sent_marketplace');
        if ($sentMarketplace == 1) {
            return '<span class="lgw-label">'
                . Mage::helper('lengow_connector')->__('order.table.status_shipped_by_mkp') . '</span>';
        } else {
            return $magentoState;
        }
    }
}
