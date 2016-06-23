<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Block_Adminhtml_Action_Renderer_Parameter extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Decorate parameters values
     *
     * @param $row
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $parameters = $row->getData($this->getColumn()->getIndex());
        $parameters = json_decode($parameters, true);
        $return = '';
        foreach ($parameters as $key => $value) {
            if ($key == 'line' || $key == 'action_type') {
                continue;
            } elseif ($key == 'tracking_number') {
                $key = 'tracking';
            } elseif ($key == 'marketplace_order_id') {
                $key = 'marketplace sku';
            }
            $return.= strlen($return) == 0 ? ucfirst($key).': '.$value.' ' : '- '.ucfirst($key).': '.$value.' ' ;
        }
        return $return;
    }
}
