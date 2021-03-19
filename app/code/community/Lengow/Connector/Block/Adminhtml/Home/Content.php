<?php
/**
 * Copyright 2021 Lengow SAS
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
 * @copyright   2021 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Block adminhtml home content
 */
class Lengow_Connector_Block_Adminhtml_Home_Content extends Mage_Core_Block_Template
{
    /**
     * @var Lengow_Connector_Helper_Data Lengow helper instance
     */
    protected $_helper;

    /**
     * Construct
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_helper = Mage::helper('lengow_connector');
    }

    /**
     * Prepare and return block's html output
     *
     * @return Lengow_Connector_Block_Adminhtml_Home_Content
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        return $this;
    }

    /**
     * Get Lengow solution url
     *
     * @return string
     */
    public function getLengowSolutionUrl()
    {
        return '//my.' . Lengow_Connector_Model_Connector::LENGOW_URL;
    }

    /**
     * Check if plugin is a preprod version
     *
     * @return boolean
     */
    public function isPreprodPlugin()
    {
        return Lengow_Connector_Model_Connector::LENGOW_URL === 'lengow.net';
    }
}
