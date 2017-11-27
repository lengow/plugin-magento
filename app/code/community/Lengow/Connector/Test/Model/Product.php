<?php


class Lengow_Connector_Test_Model_Product extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Test attribute lengow_product
     *
     * @test
     * @doNotIndexAll
     * @loadFixture store.yaml
     * @loadFixture lengow_product.yaml
     */
    public function lengowProduct()
    {
        //load product lengow product must be false
        $product = Mage::getModel('catalog/product')->load(100);
        $this->assertTrue(!(boolean)$product->getLengowProduct(), 'Test default value Lengow_product');

        //we set lengow product to true, lengow product must be true
        $product = Mage::getModel('catalog/product')->load(100);
        $product->setLengowProduct(true);
        $product->save();

        $product = Mage::getModel('catalog/product')->load(100);
        $this->assertTrue((boolean)$product->getLengowProduct(), 'Lengow_product must be true');

        //we set lengow product to false, lengow product must be false
        $product = Mage::getModel('catalog/product')->load(100);
        $product->setLengowProduct(0);
        $product->save();

        $product = Mage::getModel('catalog/product')->load(100);
        $this->assertTrue(!(boolean)$product->getLengowProduct());

        //set all products to 1
        $productAction = Mage::getSingleton('catalog/product_action');
        $productAction->updateAttributes(array(100), array('lengow_product' => 1), 0);
        $product = Mage::getModel('catalog/product')->load(100);
        $this->assertTrue((boolean)$product->getLengowProduct());

        //set all products to 0
        $productAction->updateAttributes(array(100), array('lengow_product' => 0), 0);
        $product = Mage::getModel('catalog/product')->load(100);
        $this->assertTrue(!(boolean)$product->getLengowProduct());

        $productAction = Mage::getSingleton('catalog/product_action');
        $productAction->updateAttributes(array(100), array('lengow_product' => 1), 2);
        $product = Mage::getModel('catalog/product')->setStoreId(2)->load(100);
        $this->assertTrue((boolean)$product->getLengowProduct());

        $productAction = Mage::getSingleton('catalog/product_action');
        $productAction->updateAttributes(array(100), array('lengow_product' => 0), 2);
        $product = Mage::getModel('catalog/product')->setStoreId(2)->load(100);
        $this->assertTrue(!(boolean)$product->getLengowProduct());

    }
}