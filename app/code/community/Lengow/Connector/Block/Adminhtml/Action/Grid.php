<?php
/**
 * Copyright 2017 Lengow SAS.
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
 * Block adminhtml action grid
 */
class Lengow_Connector_Block_Adminhtml_Action_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('LengowActionGrid');
        $this->setUseAjax(true);
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    /**
     * Prepare collection
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('lengow/import_action')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare columns
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'id',
            array(
                'header' => Mage::helper('lengow_connector')->__('toolbox.table.id'),
                'index' => 'id',
                'width' => '60px',
                'type' => 'text',
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            'order_id',
            array(
                'header' => Mage::helper('lengow_connector')->__('toolbox.table.order_id'),
                'index' => 'order_id',
                'width' => '100px',
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            'action_id',
            array(
                'header' => Mage::helper('lengow_connector')->__('toolbox.table.action_id'),
                'index' => 'action_id',
                'width' => '100px',
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            'order_line_sku',
            array(
                'header' => Mage::helper('lengow_connector')->__('toolbox.table.order_line_sku'),
                'index' => 'order_line_sku',
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            'action_type',
            array(
                'header' => Mage::helper('lengow_connector')->__('toolbox.table.action_type'),
                'index' => 'action_type',
                'width' => '100px',
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            'retry',
            array(
                'header' => Mage::helper('lengow_connector')->__('toolbox.table.retry'),
                'index' => 'retry',
                'width' => '100px',
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            'parameters',
            array(
                'header' => Mage::helper('lengow_connector')->__('toolbox.table.parameters'),
                'index' => 'parameters',
                'renderer' => 'Lengow_Connector_Block_Adminhtml_Action_Renderer_Parameter',
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            'state',
            array(
                'header' => Mage::helper('lengow_connector')->__('toolbox.table.state'),
                'index' => 'state',
                'renderer' => 'Lengow_Connector_Block_Adminhtml_Action_Renderer_Status',
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            'created_at',
            array(
                'header' => Mage::helper('lengow_connector')->__('toolbox.table.created_at'),
                'index' => 'created_at',
                'type' => 'datetime',
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            'updated_at',
            array(
                'header' => Mage::helper('lengow_connector')->__('toolbox.table.updated_at'),
                'index' => 'updated_at',
                'type' => 'datetime',
                'column_css_class' => 'lengow_table_center',
            )
        );
        return parent::_prepareColumns();
    }

    /**
     * Get grid url
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }
}
