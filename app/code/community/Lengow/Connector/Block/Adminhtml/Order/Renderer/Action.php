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
        $helper = $this->helper('lengow_connector');
        if ($row->getData('is_in_error') == 1) {
            $order_lengow_id = $row->getData('id');
            $error_type = $row->getData('order_process_state') == 0 ? 'import' : 'send';
            $url = Mage::helper('adminhtml')->getUrl('adminhtml/lengow_order/').'?isAjax=true';

            $order_error = Mage::getModel('lengow/import_ordererror');
            $error_orders = $order_error->getOrderErrors($order_lengow_id, $error_type, false);
        
            $error_messages = array();
            if ($error_orders) {
                foreach ($error_orders as $error_order) {
                    $error_messages[] = $helper->decodeLogMessage($error_order['message']);
                }
            }
            if ($error_type == 'import') {
                $action = 're_import';
                $tootlip = $helper->decodeLogMessage('order.table.order_not_imported')
                    .'<br/>'.join('<br/>', $error_messages);
                return '<a class="lengow_action lengow_tooltip lengow_btn lengow_btn_white"
                    onclick="makeLengowActions(\''.$url.'\', \'re_import\', \''.$order_lengow_id.'\')">'
                    .$helper->decodeLogMessage('order.table.not_imported')
                    .'<span class="lengow_order_action">'.$tootlip.'</span>&nbsp<i class="fa fa-refresh"></i></a>';
            } else {
                $tootlip = $helper->decodeLogMessage('order.table.action_sent_not_work')
                    .'<br/>'.join('<br/>', $error_messages);
                return '<a class="lengow_action lengow_tooltip lengow_btn lengow_btn_white" 
                    onclick="makeLengowActions(\''.$url.'\', \'re_send\', \''.$order_lengow_id.'\')">'
                    .$helper->decodeLogMessage('order.table.not_sent')
                    .'<span class="lengow_order_action">'.$tootlip.'</span>&nbsp<i class="fa fa-refresh"></i></a>';
            }
        } else {
            //check if order actions in progress
            if (!is_null($row->getData('order_id')) && $row->getData('order_process_state') == 1) {
                $actions = Mage::getModel('lengow/import_action')
                    ->getOrderActiveAction($row->getData('order_id'), 'ship');
                if ($actions) {
                    return '<a class="lengow_action lengow_tooltip lengow_btn lengow_btn_white">'
                        .$helper->decodeLogMessage('order.table.action_sent')
                        .'<span class="lengow_order_action">'
                        .$helper->decodeLogMessage('order.table.action_waiting_return')
                        .'</span></a>';
                }
            }
        }
    }
}
