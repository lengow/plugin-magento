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
 * Block adminhtml log grid
 */
class Lengow_Connector_Block_Adminhtml_Log_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('LengowLogGrid');
        $this->setUseAjax(true);
        $this->setDefaultSort(Lengow_Connector_Model_Log::FIELD_ID);
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    /**
     * Prepare collection
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('lengow/log')->getCollection();
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
            Lengow_Connector_Model_Log::FIELD_ID,
            array(
                'header' => $helper->__('log.table.id'),
                'width' => '80px',
                'type' => 'text',
                'index' => Lengow_Connector_Model_Log::FIELD_ID,
            )
        );
        $this->addColumn(
            Lengow_Connector_Model_Log::FIELD_DATE,
            array(
                'header' => $helper->__('log.table.date'),
                'index' => Lengow_Connector_Model_Log::FIELD_DATE,
                'type' => 'datetime',
                'width' => '100px',
            )
        );
        $this->addColumn(
            Lengow_Connector_Model_Log::FIELD_MESSAGE,
            array(
                'header' => $helper->__('log.table.message'),
                'index' => Lengow_Connector_Model_Log::FIELD_MESSAGE,
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
