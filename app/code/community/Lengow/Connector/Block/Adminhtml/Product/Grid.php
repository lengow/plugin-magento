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
 * Block adminhtml product grid
 */
class Lengow_Connector_Block_Adminhtml_Product_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('LengowProductGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('product_filter');
    }

    /**
     * Get store
     */
    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        // set default store if storeId is global
        if ($storeId === 0) {
            $storeId = Mage::app()->getWebsite(true)->getDefaultGroup()->getDefaultStoreId();
        }
        return Mage::app()->getStore($storeId);
    }

    /**
     * Prepare collection
     */
    protected function _prepareCollection()
    {
        $store = $this->_getStore();
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('lengow_product')
            ->addAttributeToSelect('attribute_set_id')
            ->addAttributeToSelect('type_id')
            ->joinField(
                'qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
            )
            ->addAttributeToFilter('type_id', array('nlike' => 'bundle'));
        if ($store->getId()) {
            $collection->setStoreId($store->getId());
            $collection->addStoreFilter($store);
            $collection->joinAttribute(
                'custom_name',
                'catalog_product/name',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
            $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner', $store->getId());
            $collection->joinAttribute(
                'visibility',
                'catalog_product/visibility',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
            $collection->joinAttribute('price', 'catalog_product/price', 'entity_id', null, 'left', $store->getId());
        } else {
            $collection->addAttributeToSelect('price');
            $collection->addAttributeToSelect('status');
            $collection->addAttributeToSelect('visibility');
        }
        $this->setCollection($collection);
        parent::_prepareCollection();
        $this->getCollection()->addWebsiteNamesToResult();
        return $this;
    }

    /**
     * Add filter to collection
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($this->getCollection() && $column->getId() === 'websites') {
            $this->getCollection()->joinField(
                'websites',
                'catalog/product_website',
                'website_id',
                'product_id=entity_id',
                null,
                'left'
            );
        }
        return parent::_addColumnFilterToCollection($column);
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
        // create type filter without bundle type product
        $types = array();
        $productTypes = Mage::getModel('lengow/system_config_source_types')->toOptionArray();
        foreach ($productTypes as $value) {
            $types[$value['value']] = $value['label'];
        }
        $this->addColumn(
            'entity_id',
            array(
                'header' => $helper->__('product.table.id'),
                'index' => 'entity_id',
                'width' => '50px',
                'type' => 'number',
            )
        );
        $this->addColumn(
            'name',
            array(
                'header' => $helper->__('product.table.name'),
                'index' => 'name',
            )
        );
        $store = $this->_getStore();
        if ($store->getId()) {
            $this->addColumn(
                'custom_name',
                array(
                    'header' => $helper->__('product.table.custom_name', array('store_name' => $store->getName())),
                    'index' => 'custom_name',
                )
            );
        }
        $this->addColumn(
            'type',
            array(
                'header' => $helper->__('product.table.type'),
                'index' => 'type_id',
                'width' => '60px',
                'type' => 'options',
                'options' => $types,
            )
        );
        $sets = Mage::getResourceModel('eav/entity_attribute_set_collection')
            ->setEntityTypeFilter(Mage::getModel('catalog/product')->getResource()->getTypeId())
            ->load()
            ->toOptionHash();
        $this->addColumn(
            'set_name',
            array(
                'header' => $helper->__('product.table.attribut_set_name'),
                'index' => 'attribute_set_id',
                'width' => '100px',
                'type' => 'options',
                'options' => $sets,
            )
        );
        $this->addColumn(
            'sku',
            array(
                'header' => $helper->__('product.table.sku'),
                'index' => 'sku',
                'width' => '80px',
            )
        );
        $store = $this->_getStore();
        $this->addColumn(
            'price',
            array(
                'header' => $helper->__('product.table.price'),
                'index' => 'price',
                'type' => 'price',
                'currency_code' => $store->getBaseCurrency()->getCode(),
            )
        );
        $this->addColumn(
            'qty',
            array(
                'header' => $helper->__('product.table.quantity'),
                'index' => 'qty',
                'width' => '100px',
                'type' => 'number',
            )
        );
        $this->addColumn(
            'visibility',
            array(
                'header' => $helper->__('product.table.visibility'),
                'width' => '70px',
                'index' => 'visibility',
                'type' => 'options',
                'options' => Mage::getModel('catalog/product_visibility')->getOptionArray(),
            )
        );
        $this->addColumn(
            'status',
            array(
                'header' => $helper->__('product.table.status'),
                'width' => '70px',
                'index' => 'status',
                'type' => 'options',
                'options' => Mage::getSingleton('catalog/product_status')->getOptionArray(),
            )
        );
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn(
                'websites',
                array(
                    'header' => $helper->__('product.table.websites'),
                    'index' => 'websites',
                    'width' => '100px',
                    'sortable' => false,
                    'type' => 'options',
                    'options' => Mage::getModel('core/website')->getCollection()->toOptionHash(),
                )
            );
        }
        $this->addColumn(
            'lengow_product',
            array(
                'header' => $helper->__('product.table.publish_on_lengow'),
                'index' => 'lengow_product',
                'width' => '70px',
                'type' => 'options',
                'renderer' => 'Lengow_Connector_Block_Adminhtml_Product_Renderer_Lengow',
                'options' => array(
                    0 => $helper->__('global.just_no'),
                    1 => $helper->__('global.just_yes'),
                ),
            )
        );
        return parent::_prepareColumns();
    }

    /**
     * Prepare mass action buttons
     */
    protected function _prepareMassaction()
    {
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector');
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('product');
        $this->getMassactionBlock()->setUseAjax(true);
        $this->getMassactionBlock()->addItem(
            'publish',
            array(
                'label' => $helper->__('product.table.change_publication'),
                'url' => $this->getUrl('*/*/massPublish', array('_current' => true)),
                'complete' => 'reloadGrid',
                'additional' => array(
                    'visibility' => array(
                        'name' => 'publish',
                        'type' => 'select',
                        'class' => 'required-entry',
                        'label' => $helper->__('product.table.publication'),
                        'values' => array(
                            0 => $helper->__('global.just_no'),
                            1 => $helper->__('global.just_yes'),
                        ),
                    ),
                )
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
        if (Mage::getSingleton('admin/session')->isAllowed('catalog_product/actions/edit')) {
            return $this->getUrl(
                '*/catalog_product/edit',
                array(
                    'store' => $this->getRequest()->getParam('store'),
                    'id' => $row->getId(),
                )
            );
        }
        return false;
    }
}
