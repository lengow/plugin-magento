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
        parent::_prepareCollection();
        return $this;
    }

//    protected function _addColumnFilterToCollection($column)
//    {
//        if ($this->getCollection()) {
//            if ($column->getId() == 'websites') {
//                $this->getCollection()->joinField(
//                    'websites',
//                    'catalog/product_website',
//                    'website_id',
//                    'product_id=entity_id',
//                    null,
//                    'left'
//                );
//            }
//        }
//        return parent::_addColumnFilterToCollection($column);
//    }

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

//    protected function _prepareMassaction()
//    {
//
//        $this->setMassactionIdField('entity_id');
//        $this->getMassactionBlock()->setFormFieldName('product');
//        $this->getMassactionBlock()->setUseAjax(true);
//        $options = array(
//            0 => Mage::helper('catalog')->__('No'),
//            1 => Mage::helper('catalog')->__('Yes')
//        );
//        $this->getMassactionBlock()->addItem('publish', array(
//            'label' => $this->__('Change Lengow\'s publication'),
//            'url' => $this->getUrl('*/*/massPublish', array('_current' => true)),
//            'complete' => 'reloadGrid',
//            'additional' => array(
//                'visibility' => array(
//                    'name' => 'publish',
//                    'type' => 'select',
//                    'class' => 'required-entry',
//                    'label' => Mage::helper('catalog')->__('Publication'),
//                    'values' => $options
//                )
//            )
//        ));
//        return $this;
//    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }
//
//    public function getRowUrl($row)
//    {
//        if (Mage::getSingleton('admin/session')->isAllowed('catalog_product/actions/edit')) {
//            return $this->getUrl(
//                '*/catalog_product/edit',
//                array(
//                'store'=>$this->getRequest()->getParam('store'),
//                'id'=>$row->getId()
//                )
//            );
//        }
//        return false;
//    }
}
