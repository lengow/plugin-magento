<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Block_Adminhtml_Product extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Set template
     */
    public function __construct()
    {
        parent::__construct();
        $this->_controller = 'adminhtml_product';
        $this->_blockGroup = 'lengow';
        $this->_headerText = $this->helper('lengow_connector')->__('product.screen.title');
        $this->_removeButton('add');
    }
}
