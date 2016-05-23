<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Block_Adminhtml_Order_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('lengowOrderGrid');
        $this->setDefaultSort('order_date');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('order_filter');
    }

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

    protected function _prepareColumns()
    {
        $this->addColumn(
            'action',
            array(
                'header'   => $this->helper('lengow_connector')->__('order.table.lengow_action'),
                'index'    => 'is_in_error',
                'renderer' => 'Lengow_Connector_Block_Adminhtml_Order_Renderer_Action',
                'type'     => 'options',
                'options'  => array(
                    0 => $this->helper('lengow_connector')->__('order.table.action_success'),
                    1 => $this->helper('lengow_connector')->__('order.table.action_error'),
                )
            )
        );
        $this->addColumn(
            'lengow_state',
            array(
                'header'   => $this->helper('lengow_connector')->__('order.table.lengow_state'),
                'index'    => 'order_lengow_state',
                'renderer' => 'Lengow_Connector_Block_Adminhtml_Order_Renderer_State',
                'type'     => 'options',
                'options'  => array(
                    'accepted'         => $this->helper('lengow_connector')->__('order.table.status_accepted'),
                    'waiting_shipment' => $this->helper('lengow_connector')->__('order.table.status_waiting_shipment'),
                    'shipped'          => $this->helper('lengow_connector')->__('order.table.status_shipped'),
                    'closed'           => $this->helper('lengow_connector')->__('order.table.status_closed'),
                    'canceled'         => $this->helper('lengow_connector')->__('order.table.status_canceled'),
                )
            )
        );
        $this->addColumn(
            'marketplace_label',
            array(
                'header'           => $this->helper('lengow_connector')->__('order.table.marketplace_name'),
                'index'            => 'marketplace_label',
                'column_css_class' => 'lengow_table_center',
            )
        );
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn(
                'store_id',
                array(
                    'header'       => $this->helper('lengow_connector')->__('order.table.store'),
                    'index'        => 'store_id',
                    'filter_index' => 'main_table.store_id',
                    'type'         => 'store',
                    'store_view'   => true,
                )
            );
        }
        $this->addColumn(
            'marketplace_sku',
            array(
                'header'           => $this->helper('lengow_connector')->__('order.table.marketplace_sku'),
                'index'            => 'marketplace_sku',
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            'order_sku',
            array(
                'header'           => $this->helper('lengow_connector')->__('order.table.magento_sku'),
                'index'            => 'order_sku',
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            'status',
            array(
                'header'           => $this->helper('lengow_connector')->__('order.table.magento_status'),
                'index'            => 'status',
                'type'             => 'options',
                'width'            => '150px',
                'options'          => Mage::getSingleton('sales/order_config')->getStatuses(),
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            'order_date',
            array(
                'header'           => $this->helper('lengow_connector')->__('order.table.order_date'),
                'index'            => 'order_date',
                'type'             => 'datetime',
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            'customer_name',
            array(
                'header'           => $this->helper('lengow_connector')->__('order.table.customer_name'),
                'index'            => 'customer_name',
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            'delivery_country_iso',
            array(
                'header'           => $this->helper('lengow_connector')->__('order.table.country'),
                'index'            => 'delivery_country_iso',
                'renderer'         => 'Lengow_Connector_Block_Adminhtml_Order_Renderer_Country',
                'width'            => '50px',
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            'order_item',
            array(
                'header'           => $this->helper('lengow_connector')->__('order.table.items'),
                'index'            => 'order_item',
                'width'            => '50px',
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            'total_paid',
            array(
                'header'           => $this->helper('lengow_connector')->__('order.table.total_paid'),
                'index'            => 'total_paid',
                'filter_index'     => 'main_table.total_paid',
                'renderer'         => 'Lengow_Connector_Block_Adminhtml_Order_Renderer_Total',
                'width'            => '100px',
                'column_css_class' => 'lengow_table_center',
            )
        );
        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('order');
        $this->getMassactionBlock()->setUseAjax(true);
        $this->getMassactionBlock()->addItem(
            'reimport',
            array(
                'label'    => $this->helper('lengow_connector')->__('order.table.button_reimport_order'),
                'url'      => $this->getUrl('*/*/massReImport', array('_current' => true)),
                'complete' => 'reloadGrid'
            )
        );
        $this->getMassactionBlock()->addItem(
            'resend',
            array(
                'label'    => $this->helper('lengow_connector')->__('order.table.button_resend_order'),
                'url'      => $this->getUrl('*/*/massReSend', array('_current' => true)),
                'complete' => 'reloadGrid'
            )
        );
        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    public function getRowUrl($row)
    {
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            if ($row->getData('order_id') != null) {
                return $this->getUrl('*/sales_order/view', array('order_id' => $row->getData('order_id')));
            }
        }
        return false;
    }
}
