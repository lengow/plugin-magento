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
 * Block adminhtml order grid
 */
class Lengow_Connector_Block_Adminhtml_Order_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('lengowOrderGrid');
        $this->setDefaultSort(Lengow_Connector_Model_Import_Order::FIELD_ORDER_DATE);
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('order_filter');
    }

    /**
     * Prepare collection
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('lengow/import_order')->getCollection();
        $collection->getSelect()->joinleft(
            array('s' => $collection->getTable('sales/order')),
            'main_table.order_id = s.entity_id',
            array('status' => 's.status')
        );
        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }

    /**
     * Prepare columns
     *
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector');
        $this->addColumn(
            'action',
            array(
                'header' => $helper->__('order.table.lengow_action'),
                'index' => Lengow_Connector_Model_Import_Order::FIELD_IS_IN_ERROR,
                'renderer' => 'Lengow_Connector_Block_Adminhtml_Order_Renderer_Action',
                'type' => 'options',
                'options' => array(
                    0 => $helper->__('order.table.action_success'),
                    1 => $helper->__('order.table.action_error'),
                ),
            )
        );
        $this->addColumn(
            'lengow_state',
            array(
                'header' => $helper->__('order.table.lengow_state'),
                'index' => Lengow_Connector_Model_Import_Order::FIELD_ORDER_LENGOW_STATE,
                'renderer' => 'Lengow_Connector_Block_Adminhtml_Order_Renderer_State',
                'type' => 'options',
                'column_css_class' => 'a-center',
                'options' => array(
                    Lengow_Connector_Model_Import_Order::STATE_ACCEPTED => $helper->__('order.table.status_accepted'),
                    Lengow_Connector_Model_Import_Order::STATE_WAITING_SHIPMENT => $helper->__(
                        'order.table.status_waiting_shipment'
                    ),
                    Lengow_Connector_Model_Import_Order::STATE_SHIPPED => $helper->__('order.table.status_shipped'),
                    Lengow_Connector_Model_Import_Order::STATE_REFUNDED => $helper->__('order.table.status_refunded'),
                    Lengow_Connector_Model_Import_Order::STATE_CLOSED => $helper->__('order.table.status_closed'),
                    Lengow_Connector_Model_Import_Order::STATE_CANCELED => $helper->__('order.table.status_canceled'),
                ),
            )
        );
        $this->addColumn(
            Lengow_Connector_Model_Import_Order::FIELD_ORDER_TYPES,
            array(
                'header' => $helper->__('order.table.order_types'),
                'index' => Lengow_Connector_Model_Import_Order::FIELD_ORDER_TYPES,
                'renderer' => 'Lengow_Connector_Block_Adminhtml_Order_Renderer_Types',
                'type' => 'options',
                'width' => '70px',
                'column_css_class' => 'a-center',
                'options' => array(
                    Lengow_Connector_Model_Import_Order::TYPE_EXPRESS => $helper->__('order.table.type_express'),
                    Lengow_Connector_Model_Import_Order::TYPE_DELIVERED_BY_MARKETPLACE => $helper->__(
                        'order.table.type_delivered_by_marketplace'
                    ),
                    Lengow_Connector_Model_Import_Order::TYPE_BUSINESS => $helper->__('order.table.type_business'),
                ),
            )
        );
        $this->addColumn(
            Lengow_Connector_Model_Import_Order::FIELD_MARKETPLACE_SKU,
            array(
                'header' => $helper->__('order.table.marketplace_sku'),
                'index' => Lengow_Connector_Model_Import_Order::FIELD_MARKETPLACE_SKU,
            )
        );
        $this->addColumn(
            Lengow_Connector_Model_Import_Order::FIELD_MARKETPLACE_LABEL,
            array(
                'header' => $helper->__('order.table.marketplace_name'),
                'index' => Lengow_Connector_Model_Import_Order::FIELD_MARKETPLACE_LABEL,
            )
        );
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn(
                Lengow_Connector_Model_Import_Order::FIELD_STORE_ID,
                array(
                    'header' => $helper->__('order.table.store'),
                    'index' => Lengow_Connector_Model_Import_Order::FIELD_STORE_ID,
                    'filter_index' => 'main_table.store_id',
                    'type' => 'store',
                    'store_view' => true,
                )
            );
        }
        $this->addColumn(
            Lengow_Connector_Model_Import_Order::FIELD_ORDER_SKU,
            array(
                'header' => $helper->__('order.table.magento_sku'),
                'index' => Lengow_Connector_Model_Import_Order::FIELD_ORDER_SKU,
            )
        );
        $this->addColumn(
            'status',
            array(
                'header' => $helper->__('order.table.magento_status'),
                'index' => 'status',
                'type' => 'options',
                'width' => '150px',
                'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
            )
        );
        $this->addColumn(
            Lengow_Connector_Model_Import_Order::FIELD_CUSTOMER_NAME,
            array(
                'header' => $helper->__('order.table.customer_name'),
                'index' => Lengow_Connector_Model_Import_Order::FIELD_CUSTOMER_NAME,
            )
        );
        $this->addColumn(
            Lengow_Connector_Model_Import_Order::FIELD_ORDER_DATE,
            array(
                'header' => $helper->__('order.table.order_date'),
                'width' => '150px',
                'index' => Lengow_Connector_Model_Import_Order::FIELD_ORDER_DATE,
                'type' => 'datetime',
            )
        );
        $this->addColumn(
            Lengow_Connector_Model_Import_Order::FIELD_DELIVERY_COUNTRY_ISO,
            array(
                'header' => $helper->__('order.table.country'),
                'index' => Lengow_Connector_Model_Import_Order::FIELD_DELIVERY_COUNTRY_ISO,
                'renderer' => 'Lengow_Connector_Block_Adminhtml_Order_Renderer_Country',
                'column_css_class' => 'a-center',
                'width' => '50px',
            )
        );
        $this->addColumn(
            Lengow_Connector_Model_Import_Order::FIELD_TOTAL_PAID,
            array(
                'header' => $helper->__('order.table.total_paid'),
                'index' => Lengow_Connector_Model_Import_Order::FIELD_TOTAL_PAID,
                'filter_index' => 'main_table.total_paid',
                'renderer' => 'Lengow_Connector_Block_Adminhtml_Order_Renderer_Total',
                'width' => '70px',
            )
        );
        return parent::_prepareColumns();
    }

    /**
     *
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($this->getCollection()) {
            $field = $column->getFilterIndex() ?: $column->getIndex();
            if ($column->getFilterConditionCallback()) {
                call_user_func($column->getFilterConditionCallback(), $this->getCollection(), $column);
            } else {
                $cond = $column->getFilter()->getCondition();
                if ($field && isset($cond)) {
                    if ($field === Lengow_Connector_Model_Import_Order::FIELD_ORDER_TYPES && isset($cond['eq'])) {
                        $value = $cond['eq'];
                        if ($value === Lengow_Connector_Model_Import_Order::TYPE_EXPRESS) {
                            $cond = array(
                                array('like' => '%' . Lengow_Connector_Model_Import_Order::TYPE_EXPRESS . '%'),
                                array('like' => '%' . Lengow_Connector_Model_Import_Order::TYPE_PRIME . '%'),
                            );
                        } elseif ($value === Lengow_Connector_Model_Import_Order::TYPE_DELIVERED_BY_MARKETPLACE) {
                            $field = array(
                                Lengow_Connector_Model_Import_Order::FIELD_ORDER_TYPES,
                                Lengow_Connector_Model_Import_Order::FIELD_SENT_MARKETPLACE,
                            );
                            $cond = array(
                                array('like' =>
                                    '%' . Lengow_Connector_Model_Import_Order::TYPE_DELIVERED_BY_MARKETPLACE . '%'
                                ),
                                array('eq' => '1'),
                            );
                        } else {
                            $cond = array('like' => '%'.$cond['eq'].'%');
                        }
                    }
                    $this->getCollection()->addFieldToFilter($field , $cond);
                }
            }
        }
        return $this;
    }

    /**
     * Prepare mass action buttons
     */
    protected function _prepareMassaction()
    {
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector');
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('order');
        $this->getMassactionBlock()->setUseAjax(true);
        $this->getMassactionBlock()->addItem(
            'reimport',
            array(
                'label' => $helper->__('order.table.button_reimport_order'),
                'url' => $this->getUrl('*/*/massReImport', array('_current' => true)),
                'complete' => 'reloadGrid',
            )
        );
        $this->getMassactionBlock()->addItem(
            'resend',
            array(
                'label' => $helper->__('order.table.button_resend_order'),
                'url' => $this->getUrl('*/*/massReSend', array('_current' => true)),
                'complete' => 'reloadGrid',
            )
        );
        return $this;
    }

    /**
     * Get grid url
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    /**
     * Get row url
     *
     * @param Varien_Object $row Magento Varien object instance
     *
     * @return string|false
     */
    public function getRowUrl($row)
    {
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            if ($row->getData('order_id') !== null) {
                return $this->getUrl('*/sales_order/view', array('order_id' => $row->getData('order_id')));
            }
        }
        return false;
    }
}
