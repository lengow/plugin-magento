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
 * Block adminhtml action renderer parameter
 */
class Lengow_Connector_Block_Adminhtml_Action_Renderer_Parameter
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Decorate parameters values
     *
     * @param Varien_Object $row Magento varian object instance
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $parameters = $row->getData($this->getColumn()->getIndex());
        $parameters = json_decode($parameters, true);
        $return = '';
        foreach ($parameters as $key => $value) {
            if ($key === Lengow_Connector_Model_Import_Action::ARG_LINE
                || $key === Lengow_Connector_Model_Import_Action::ARG_ACTION_TYPE
            ) {
                continue;
            } elseif ($key === Lengow_Connector_Model_Import_Action::ARG_TRACKING_NUMBER) {
                $key = 'tracking';
            } elseif ($key === 'marketplace_order_id') {
                $key = 'marketplace sku';
            }
            $return .= strlen($return) === 0
                ? ucfirst($key) . ': ' . $value . ' '
                : '- ' . ucfirst($key) . ': ' . $value . ' ';
        }
        return $return;
    }
}
