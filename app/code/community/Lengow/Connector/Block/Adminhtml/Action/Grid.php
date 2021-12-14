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
        $this->setDefaultSort(Lengow_Connector_Model_Import_Action::FIELD_CREATED_AT);
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
     *
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = $this->helper('lengow_connector');
        $this->addColumn(
            Lengow_Connector_Model_Import_Action::FIELD_ID,
            array(
                'header' => $helper->__('toolbox.table.id'),
                'index' => Lengow_Connector_Model_Import_Action::FIELD_ID,
                'width' => '60px',
                'type' => 'text',
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            Lengow_Connector_Model_Import_Action::FIELD_ORDER_ID,
            array(
                'header' => $helper->__('toolbox.table.order_id'),
                'index' => Lengow_Connector_Model_Import_Action::FIELD_ORDER_ID,
                'width' => '100px',
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            Lengow_Connector_Model_Import_Action::FIELD_ACTION_ID,
            array(
                'header' => $helper->__('toolbox.table.action_id'),
                'index' => Lengow_Connector_Model_Import_Action::FIELD_ACTION_ID,
                'width' => '100px',
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            Lengow_Connector_Model_Import_Action::FIELD_ORDER_LINE_SKU,
            array(
                'header' => $helper->__('toolbox.table.order_line_sku'),
                'index' => Lengow_Connector_Model_Import_Action::FIELD_ORDER_LINE_SKU,
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            Lengow_Connector_Model_Import_Action::FIELD_ACTION_TYPE,
            array(
                'header' => $helper->__('toolbox.table.action_type'),
                'index' => Lengow_Connector_Model_Import_Action::FIELD_ACTION_TYPE,
                'width' => '100px',
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            Lengow_Connector_Model_Import_Action::FIELD_RETRY,
            array(
                'header' => $helper->__('toolbox.table.retry'),
                'index' => Lengow_Connector_Model_Import_Action::FIELD_RETRY,
                'width' => '100px',
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            Lengow_Connector_Model_Import_Action::FIELD_PARAMETERS,
            array(
                'header' => $helper->__('toolbox.table.parameters'),
                'index' => Lengow_Connector_Model_Import_Action::FIELD_PARAMETERS,
                'renderer' => 'Lengow_Connector_Block_Adminhtml_Action_Renderer_Parameter',
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            Lengow_Connector_Model_Import_Action::FIELD_STATE,
            array(
                'header' => $helper->__('toolbox.table.state'),
                'index' => Lengow_Connector_Model_Import_Action::FIELD_STATE,
                'renderer' => 'Lengow_Connector_Block_Adminhtml_Action_Renderer_Status',
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            Lengow_Connector_Model_Import_Action::FIELD_CREATED_AT,
            array(
                'header' => $helper->__('toolbox.table.created_at'),
                'index' => Lengow_Connector_Model_Import_Action::FIELD_CREATED_AT,
                'type' => 'datetime',
                'column_css_class' => 'lengow_table_center',
            )
        );
        $this->addColumn(
            Lengow_Connector_Model_Import_Action::FIELD_UPDATED_AT,
            array(
                'header' => $helper->__('toolbox.table.updated_at'),
                'index' => Lengow_Connector_Model_Import_Action::FIELD_UPDATED_AT,
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
