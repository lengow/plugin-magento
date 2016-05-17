<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Block_Adminhtml_Order_Renderer_Action extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        if ($row->getData('is_in_error') == 1) {
            $helper = $this->helper('lengow_connector');
            $order_lengow_id = $row->getData('id');
            $error_type = $row->getData('order_process_state') == 0 ? 'import' : 'send';
            $order_error = Mage::getModel('lengow/import_ordererror');
            $error_orders = $order_error->getOrderErrors($order_lengow_id, $error_type, false);
            $error_messages = array();
            if ($error_orders) {
                foreach ($error_orders as $error_order) {
                    $error_messages[] = $helper->decodeLogMessage($error_order['message']);
                }
            }
            if ($error_type == 'import') {
                $tootlip = $helper->decodeLogMessage('order.table.order_not_imported')
                    .'<br/>'.join('<br/>', $error_messages);
                return '<a class="lgw-tooltip lgw-btn lgw-btn-white" href="#">'
                    .$helper->decodeLogMessage('order.table.not_imported')
                    .'<span class="lgw-order-action">'.$tootlip.'</span></a>';
            } else {
                $tootlip = $helper->decodeLogMessage('order.table.action_sent_not_work')
                    .'<br/>'.join('<br/>', $error_messages);
                return '<a class="lgw-tooltip lgw-btn lgw-btn-white" href="#">'
                    .$helper->decodeLogMessage('order.table.not_sent')
                    .'<span class="lgw-order-action">'.$tootlip.'</span></a>';
            }
        }
    }
}
