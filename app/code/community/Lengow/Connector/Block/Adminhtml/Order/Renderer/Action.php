<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Block_Adminhtml_Order_Renderer_Action
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Decorate Action values
     *
     * @param $row
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $helper = $this->helper('lengow_connector');
        if ($row->getData('is_in_error') == 1) {
            $orderLengowId = $row->getData('id');
            $errorType = $row->getData('order_process_state') == 0 ? 'import' : 'send';
            $url = Mage::helper('adminhtml')->getUrl('adminhtml/lengow_order/').'?isAjax=true';

            $orderError = Mage::getModel('lengow/import_ordererror');
            $errorOrders = $orderError->getOrderErrors($orderLengowId, $errorType, false);
        
            $errorMessages = array();
            if ($errorOrders) {
                foreach ($errorOrders as $errorOrder) {
                    $errorMessages[] = $helper->decodeLogMessage($errorOrder['message']);
                }
            }
            if ($errorType == 'import') {
                $action = 're_import';
                $tootlip = $helper->decodeLogMessage('order.table.order_not_imported')
                    .'<br/>'.join('<br/>', $errorMessages);
                return '<a class="lengow_action lengow_tooltip lgw-btn lgw-btn-white"
                    onclick="makeLengowActions(\''.$url.'\', \'re_import\', \''.$orderLengowId.'\')">'
                    .$helper->decodeLogMessage('order.table.not_imported')
                    .'<span class="lengow_order_action">'.$tootlip.'</span>&nbsp<i class="fa fa-refresh"></i></a>';
            } else {
                $tootlip = $helper->decodeLogMessage('order.table.action_sent_not_work')
                    .'<br/>'.join('<br/>', $errorMessages);
                return '<a class="lengow_action lengow_tooltip lgw-btn lgw-btn-white" 
                    onclick="makeLengowActions(\''.$url.'\', \'re_send\', \''.$orderLengowId.'\')">'
                    .$helper->decodeLogMessage('order.table.not_sent')
                    .'<span class="lengow_order_action">'.$tootlip.'</span>&nbsp<i class="fa fa-refresh"></i></a>';
            }
        } else {
            //check if order actions in progress
            if (!is_null($row->getData('order_id')) && $row->getData('order_process_state') == 1) {
                $action = Mage::getModel('lengow/import_action');
                $lastActionType = $action->getLastOrderActionType($row->getData('order_id'));
                if ($lastActionType) {
                    return '<a class="lengow_action lengow_tooltip lgw-btn lgw-btn-white">'
                        .$helper->decodeLogMessage(
                            'order.table.action_sent',
                            null,
                            array('action_type' => $lastActionType)
                        )
                        .'<span class="lengow_order_action">'
                        .$helper->decodeLogMessage('order.table.action_waiting_return')
                        .'</span></a>';
                }
            }
        }
    }
}
