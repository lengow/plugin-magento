<?php
/**
 * Copyright 2017 Lengow SAS.
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
 * Block adminhtml action renderer status
 */
class Lengow_Connector_Block_Adminhtml_Action_Renderer_Status
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Decorate status values
     *
     * @param Varien_Object $row Magento Varien object instance
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = $this->helper('lengow_connector');
        $status = (int) $row->getData($this->getColumn()->getIndex());
        if ($status === Lengow_Connector_Model_Import_Action::STATE_NEW) {
            return '<span class="lgw-label orange">' . $helper->__('toolbox.table.state_processing') . '</span>';
        }
        return '<span class="lgw-label">' . $helper->__('toolbox.table.state_complete') . '</span>';
    }
}
