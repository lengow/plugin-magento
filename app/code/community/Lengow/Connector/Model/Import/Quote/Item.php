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
 * @subpackage  Model
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Model resource import quote item
 */
class  Lengow_Connector_Model_Import_Quote_Item extends Mage_Sales_Model_Quote_Item
{
    /**
     * Specify item price (base calculation price and converted price will be refreshed too)
     *
     * @param float $value price value
     *
     * @return Mage_Sales_Model_Quote_Item_Abstract
     */
    public function setPrice($value)
    {
        $this->setBaseCalculationPrice(null);
        // dont set converted price to 0
        //$this->setConvertedPrice(null);
        return $this->setData('price', $value);
    }
}
