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
 * Block adminhtml log
 */
class Lengow_Connector_Block_Adminhtml_Log extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Set template
     */
    public function __construct()
    {
        parent::__construct();
        $this->_controller = 'adminhtml_log';
        $this->_blockGroup = 'lengow';
        $this->_headerText = $this->helper('lengow_connector')->__('log.screen.title');
        $this->_removeButton('add');
    }
}
