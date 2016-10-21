<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Block_Tracker extends Mage_Core_Block_Template
{
    /**
     * Prepare and return block's html output
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setChild('tracker', $this->getLayout()->createBlock('lengow/tag_simple', 'simple_tag'));
        return $this;
    }

    /**
     * Prepare and return block's html output
     *
     * @return string
     */
    protected function _toHtml()
    {
        return parent::_toHtml();
    }
}
