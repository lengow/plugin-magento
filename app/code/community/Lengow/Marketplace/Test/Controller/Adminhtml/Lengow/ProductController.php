<?php

class Lengow_Marketplace_Test_Controller_Adminhtml_Lengow_ProductController extends Lengow_Marketplace_Test_Case
{

    /**
     * test product index
     *
     * @test
     *
     */
    public function indexAction()
    {
        $this->mockAdminUserSession();

        //test page
        $this->dispatch('adminhtml/lengow_product/index');
        $this->assertRequestRoute('adminhtml/lengow_product/index', '[Log - Url]');
    }

    public function gridAction()
    {
        $this->mockAdminUserSession();

        //test page
        $this->dispatch('adminhtml/lengow_product/grid');
        $this->assertRequestRoute('adminhtml/lengow_product/grid', '[Log - Url]');
    }


    /**
     * test mass publish action
     *
     * @test
     * @doNotIndexAll
     * @loadFixture mass_publish_action.yaml
     *
     */
    public function massPublishAction()
    {
        $this->mockAdminUserSession();

        //set lengow product to 0
        $product_action = Mage::getSingleton('catalog/product_action');
        $product_action->updateAttributes(array(1,2), array('lengow_product' => 0), 0);

        $product = Mage::getModel('catalog/product')->load(1);
        $this->assertTrue(!(boolean)$product->getLengowProduct());

        // --------------------------------------
        // Test : Publish first product to global
        $this->getRequest()->setMethod('POST')
            ->setPost(array(
                "product" => array(1),
                "publish" => 1,
                "store" => 0
            ));
        $this->dispatch('adminhtml/lengow_product/massPublish/');

        $product = Mage::getModel('catalog/product')->load(1);
        $this->assertTrue((boolean)$product->getLengowProduct());

        $product = Mage::getModel('catalog/product')->load(2);
        $this->assertTrue(!(boolean)$product->getLengowProduct());

        // --------------------------------------
        // Test : UnPublish first product to global
        $this->getRequest()->setMethod('POST')
            ->setPost(array(
                "product" => array(1),
                "publish" => 0,
                "store" => 0
            ));
        $this->dispatch('adminhtml/lengow_product/massPublish/');

        $product = Mage::getModel('catalog/product')->load(1);
        $this->assertTrue(!(boolean)$product->getLengowProduct());

        $product = Mage::getModel('catalog/product')->load(2);
        $this->assertTrue(!(boolean)$product->getLengowProduct());


        //reset product
        $product_action->updateAttributes(array(1,2), array('lengow_product' => 0), 0);

        // set product first product to false
        $this->getRequest()->setMethod('POST')
            ->setPost(array(
                "product" => array(1),
                "publish" => 1,
                "store" => 1
            ));
        $this->dispatch('adminhtml/lengow_product/massPublish/');

        $product = Mage::getModel('catalog/product')->setStoreId(1)->load(1);
        $this->assertTrue((boolean)$product->getLengowProduct());

        $product = Mage::getModel('catalog/product')->setStoreId(1)->load(2);
        $this->assertTrue(!(boolean)$product->getLengowProduct());


        //reset product
        $product_action->updateAttributes(array(1,2), array('lengow_product' => 1), 1);

        // set product first product to false
        $this->getRequest()->setMethod('POST')
            ->setPost(array(
                "product" => array(1),
                "publish" => 0,
                "store" => 0
            ));
        $this->dispatch('adminhtml/lengow_product/massPublish/');

        $product = Mage::getModel('catalog/product')->setStoreId(1)->load(1);
        $this->assertTrue(!(boolean)$product->getLengowProduct());

        $product = Mage::getModel('catalog/product')->setStoreId(1)->load(2);
        $this->assertTrue(!(boolean)$product->getLengowProduct());


    }



}