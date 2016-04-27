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
        $this->setId('orderGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('order_filter');
    }

    protected function _getStore()
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
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
        return parent::_prepareCollection();
        // return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'id',
            array(
                'header' => Mage::helper('catalog')->__('ID'),
                'width' => '50px',
                'type' => 'number',
                'index' => 'id',
            )
        );
        $this->addColumn(
            'lengow_state',
            array(
                'header' => $this->helper('lengow_connector')->__('order.table.lengow_state'),
                'index' => 'order_lengow_state',
            )
        );
        $this->addColumn(
            'marketplace_name',
            array(
                'header' => $this->helper('lengow_connector')->__('order.table.marketplace_name'),
                'index' => 'marketplace_name',
            )
        );
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'    => $this->helper('lengow_connector')->__('order.table.store'),
                'index'     => 'store_id',
                'type'      => 'store',
                'store_view'=> true,
                'display_deleted' => true,
            ));
        }
        $this->addColumn(
            'marketplace_sku',
            array(
                'header' => $this->helper('lengow_connector')->__('order.table.marketplace_sku'),
                'index' => 'marketplace_sku',
            )
        );
        $this->addColumn(
            'order_sku',
            array(
                'header' => $this->helper('lengow_connector')->__('order.table.magento_sku'),
                'index' => 'order_sku',
            )
        );
        $this->addColumn(
            'order_date',
            array(
                'header' => $this->helper('lengow_connector')->__('order.table.order_date'),
                'index' => 'order_date',
                'type' => 'datetime',
            )
        );
        $this->addColumn(
            'delivery_country_iso',
            array(
                'header' => $this->helper('lengow_connector')->__('order.table.country'),
                'index' => 'delivery_country_iso',
            )
        );
        $this->addColumn(
            'order_item',
            array(
                'header' => $this->helper('lengow_connector')->__('order.table.items'),
                'index' => 'order_item',
            )
        );
        $this->addColumn(
            'total_paid',
            array(
                'header' => $this->helper('lengow_connector')->__('order.table.total_paid'),
                'index' => 'total_paid',
                'type' => 'price',
            )
        );
        $this->addColumn('status', array(
            'header' => $this->helper('lengow_connector')->__('order.table.magento_status'),
            'index' => 'status',
            'type'  => 'options',
            'width' => '70px',
            'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
        ));
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }
}
