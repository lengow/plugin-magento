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
//        $this->addColumn(
//            'name',
//            array(
//                'header' => Mage::helper('catalog')->__('Name'),
//                'index' => 'name',
//            )
//        );
//        $store = $this->_getStore();
//        if ($store->getId()) {
//            $this->addColumn(
//                'custom_name',
//                array(
//                    'header' => Mage::helper('catalog')->__('Name In %s', $store->getName()),
//                    'index' => 'custom_name',
//                )
//            );
//        }
//        $this->addColumn(
//            'type',
//            array(
//                'header' => Mage::helper('catalog')->__('Type'),
//                'width' => '60px',
//                'index' => 'type_id',
//                'type' => 'options',
//                'options' => Mage::getSingleton('catalog/product_type')->getOptionArray(),
//            )
//        );
//        $sets = Mage::getResourceModel('eav/entity_attribute_set_collection')
//            ->setEntityTypeFilter(Mage::getModel('catalog/product')->getResource()->getTypeId())
//            ->load()
//            ->toOptionHash();
//        $this->addColumn(
//            'set_name',
//            array(
//                'header' => Mage::helper('catalog')->__('Attrib. Set Name'),
//                'width' => '100px',
//                'index' => 'attribute_set_id',
//                'type' => 'options',
//                'options' => $sets,
//            )
//        );
//        $this->addColumn(
//            'sku',
//            array(
//                'header' => Mage::helper('catalog')->__('SKU'),
//                'width' => '80px',
//                'index' => 'sku',
//            )
//        );
//        $store = $this->_getStore();
//        $this->addColumn(
//            'price',
//            array(
//                'header' => Mage::helper('catalog')->__('Price'),
//                'type' => 'price',
//                'currency_code' => $store->getBaseCurrency()->getCode(),
//                'index' => 'price',
//            )
//        );
//        $this->addColumn(
//            'qty',
//            array(
//                'header' => Mage::helper('catalog')->__('Qty'),
//                'width' => '100px',
//                'type' => 'number',
//                'index' => 'qty',
//            )
//        );
//        $this->addColumn(
//            'visibility',
//            array(
//                'header' => Mage::helper('catalog')->__('Visibility'),
//                'width' => '70px',
//                'index' => 'visibility',
//                'type' => 'options',
//                'options' => Mage::getModel('catalog/product_visibility')->getOptionArray(),
//            )
//        );
//        $this->addColumn(
//            'status',
//            array(
//                'header' => Mage::helper('catalog')->__('Status'),
//                'width' => '70px',
//                'index' => 'status',
//                'type' => 'options',
//                'options' => Mage::getSingleton('catalog/product_status')->getOptionArray(),
//            )
//        );
//        $options = array(
//            0 => Mage::helper('catalog')->__('No'),
//            1 => Mage::helper('catalog')->__('Yes')
//        );
//        if (!Mage::app()->isSingleStoreMode()) {
//            $this->addColumn(
//                'websites',
//                array(
//                    'header' => Mage::helper('catalog')->__('Websites'),
//                    'width' => '100px',
//                    'sortable' => false,
//                    'index' => 'websites',
//                    'type' => 'options',
//                    'options' => Mage::getModel('core/website')->getCollection()->toOptionHash(),
//                )
//            );
//        }
//        $this->addColumn(
//            'lengow_product',
//            array(
//                'header' => $this->helper('lengow_connector')->__('product.table.publish_on_lengow'),
//                'width' => '70px',
//                'index' => 'lengow_product',
//                'type' => 'options',
//                'options' => $options,
//                'renderer' => 'Lengow_Connector_Block_Adminhtml_Product_Renderer_Lengow'
//            )
//        );
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
