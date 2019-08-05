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
 * Block adminhtml order renderer action
 */
class Lengow_Connector_Block_Adminhtml_Order_Renderer_Action
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Decorate Action values
     *
     * @param Varien_Object $row Magento varian object instance
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = $this->helper('lengow_connector');
        $orderProcessState = (int)$row->getData('order_process_state');
        if ((bool)$row->getData('is_in_error') && $orderProcessState !== 2) {
            $orderLengowId = $row->getData('id');
            $errorType = $orderProcessState === 0 ? 'import' : 'send';
            $url = Mage::helper('adminhtml')->getUrl('adminhtml/lengow_order/') . '?isAjax=true';
            $errorOrders = Mage::getModel('lengow/import_ordererror')
                ->getOrderErrors($orderLengowId, $errorType, false);
            $errorMessages = array();
            if ($errorOrders) {
                foreach ($errorOrders as $errorOrder) {
                    if ($errorOrder['message'] !== '') {
                        $errorMessages[] = $helper->cleanData($helper->decodeLogMessage($errorOrder['message']), false);
                    } else {
                        $errorMessages[] = $helper->decodeLogMessage('order.table.no_error_message');
                    }
                }
            }
            if ($errorType === 'import') {
                $tootlip = $helper->decodeLogMessage('order.table.order_not_imported')
                    . '<br/>' . join('<br/>', $errorMessages);
                return '<a class="lengow_action lengow_tooltip lgw-btn lgw-btn-white"
                    onclick="makeLengowActions(\'' . $url . '\', \'re_import\', \'' . $orderLengowId . '\')">'
                    . $helper->decodeLogMessage('order.table.not_imported')
                    . '<span class="lengow_order_action">' . $tootlip . '</span>&nbsp<i class="fa fa-refresh"></i></a>';
            } else {
                $tootlip = $helper->decodeLogMessage('order.table.action_sent_not_work')
                    . '<br/>' . join('<br/>', $errorMessages);
                return '<a class="lengow_action lengow_tooltip lgw-btn lgw-btn-white" 
                    onclick="makeLengowActions(\'' . $url . '\', \'re_send\', \'' . $orderLengowId . '\')">'
                    . $helper->decodeLogMessage('order.table.not_sent')
                    . '<span class="lengow_order_action">' . $tootlip . '</span>&nbsp<i class="fa fa-refresh"></i></a>';
            }
        } else {
            //check if order actions in progress
            $orderId = $row->getData('order_id');
            if (!is_null($orderId) && $orderProcessState === 1) {
                $lastActionType = Mage::getModel('lengow/import_action')->getLastOrderActionType($orderId);
                if ($lastActionType) {
                    return '<a class="lengow_action lengow_tooltip lgw-btn lgw-btn-white">'
                        . $helper->decodeLogMessage(
                            'order.table.action_sent',
                            null,
                            array('action_type' => $lastActionType)
                        )
                        . '<span class="lengow_order_action">'
                        . $helper->decodeLogMessage('order.table.action_waiting_return')
                        . '</span></a>';
                }
            }
        }
    }
}
