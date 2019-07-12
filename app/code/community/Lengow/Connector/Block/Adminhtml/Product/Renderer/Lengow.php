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
 * Block adminhtml product render lengow
 */
class Lengow_Connector_Block_Adminhtml_Product_Renderer_Lengow
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Decorate lengow publication values
     *
     * @param Varien_Object $row Magento varian object instance
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = $this->helper('lengow_connector');
        $value = (int)$row->getData($this->getColumn()->getIndex());
        if ($value === 1) {
            $value = $helper->__('global.just_yes');
            $class = 'lgw-btn-green';
        } else {
            $value = $helper->__('global.just_no');
            $class = 'lgw-btn-red';
        }
        return '<span class="publish-lgw lgw-label ' . $class . '">' . $value . '</span>';
    }
}
