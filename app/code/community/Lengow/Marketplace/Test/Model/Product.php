<?php


class Lengow_Marketplace_Test_Model_Product extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Test attribute lengow_product
     *
     * @test
     * @doNotIndexAll
     * @loadFixture
     */
    public function lengow_product()
    {
        //load product lengow product must be false
        $product = Mage::getModel('catalog/product')->load(100);
        $this->assertTrue(!(boolean)$product->getLengowProduct());

        //we set lengow product to true, lengow product must be true
        $product = Mage::getModel('catalog/product')->load(100);
        $product->setLengowProduct(true);
        $product->save();

        $product = Mage::getModel('catalog/product')->load(100);
        $this->assertTrue((boolean)$product->getLengowProduct());

        //we set lengow product to false, lengow product must be false
        $product = Mage::getModel('catalog/product')->load(100);
        $product->setLengowProduct(0);
        $product->save();

        $product = Mage::getModel('catalog/product')->load(100);
        $this->assertTrue(!(boolean)$product->getLengowProduct());

        //set all products to 1
        $product_action = Mage::getSingleton('catalog/product_action');
        $product_action->updateAttributes(array(100), array('lengow_product' => 1), 0);
        $product = Mage::getModel('catalog/product')->load(100);
        $this->assertTrue((boolean)$product->getLengowProduct());

        //set all products to 0
        $product_action->updateAttributes(array(100), array('lengow_product' => 0), 0);
        $product = Mage::getModel('catalog/product')->load(100);
        $this->assertTrue(!(boolean)$product->getLengowProduct());

    }
}