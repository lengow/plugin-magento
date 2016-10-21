<?php

class Lengow_Connector_Test_Controller_Adminhtml_Lengow_ProductController extends Lengow_Connector_Test_Case
{

    /**
     * test index action
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


    /**
     * test grid action
     *
     * @test
     *
     */
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
     * @loadFixture store.yaml
     * @loadFixture mass_publish_action.yaml
     *
     */
    public function massPublishAction()
    {
        $this->mockAdminUserSession();

        $this->publishProductGlobal();
        $this->unPublishProductGlobal();
        $this->publishProductStore();
        $this->unPublishProductStore();
        $this->unPublishProductStoreGlobal();
    }

    /**
     *
     *  Publish one product Global
     *
     */

    private function publishProductGlobal()
    {
        //set lengow product to 0
        $product_action = Mage::getSingleton('catalog/product_action');
        $product_action->updateAttributes(array(1, 2), array('lengow_product' => 0), 0);

        $product = Mage::getModel('catalog/product')->load(1);
        $this->assertTrue(!(boolean)$product->getLengowProduct());

        $product = Mage::getModel('catalog/product')->load(2);
        $this->assertTrue(!(boolean)$product->getLengowProduct());

        $this->getRequest()->setMethod('POST')
            ->setPost(array(
                "product" => array(1),
                "publish" => 1
            ));
        $this->dispatch('adminhtml/lengow_product/massPublish/');

        $product = Mage::getModel('catalog/product')->load(1);
        $this->assertTrue((boolean)$product->getLengowProduct());

        $product = Mage::getModel('catalog/product')->load(2);
        $this->assertTrue(!(boolean)$product->getLengowProduct());
    }

    /**
     *
     *  UnPublish one product Global
     *
     */
    private function unPublishProductGlobal()
    {
        //set lengow product to 0
        $product_action = Mage::getSingleton('catalog/product_action');
        $product_action->updateAttributes(array(1), array('lengow_product' => 1), 0);

        $product = Mage::getModel('catalog/product')->load(1);
        $this->assertTrue((boolean)$product->getLengowProduct());

        // --------------------------------------
        // Test : UnPublish first product to global
        $this->getRequest()->setMethod('POST')
            ->setPost(array(
                "product" => array(1),
                "publish" => 0
            ));
        $this->dispatch('adminhtml/lengow_product/massPublish/');

        $product = Mage::getModel('catalog/product')->load(1);
        $this->assertTrue(!(boolean)$product->getLengowProduct());

        $product = Mage::getModel('catalog/product')->load(2);
        $this->assertTrue(!(boolean)$product->getLengowProduct());
    }

    /**
     *
     *  Publish one product to Store
     *
     */
    private function publishProductStore()
    {

        //reset product
        $product_action = Mage::getSingleton('catalog/product_action');
        $product_action->updateAttributes(array(1, 2), array('lengow_product' => 0), 0);

        $product = Mage::getModel('catalog/product')->setStoreId(1)->load(1);
        $this->assertTrue(!(boolean)$product->getLengowProduct());

        $product = Mage::getModel('catalog/product')->setStoreId(1)->load(2);
        $this->assertTrue(!(boolean)$product->getLengowProduct());

        // --------------------------------------
        // Test : Publish first product to store 1
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
    }

    /**
     *
     *  UnPublish one product to Store
     *
     */
    public function unPublishProductStore()
    {

        //reset product
        $product_action = Mage::getSingleton('catalog/product_action');
        $product_action->updateAttributes(array(1, 2), array('lengow_product' => 1), 1);

        $product = Mage::getModel('catalog/product')->setStoreId(1)->load(1);
        $this->assertTrue((boolean)$product->getLengowProduct());

        $product = Mage::getModel('catalog/product')->setStoreId(1)->load(2);
        $this->assertTrue((boolean)$product->getLengowProduct());

        // --------------------------------------
        // Test : UnPublish first product to store 1
        $this->getRequest()->setMethod('POST')
            ->setPost(array(
                "product" => array(1),
                "publish" => 0,
                "store" => 1
            ));
        $this->dispatch('adminhtml/lengow_product/massPublish/');

        $product = Mage::getModel('catalog/product')->setStoreId(1)->load(1);
        $this->assertTrue(!(boolean)$product->getLengowProduct());

        $product = Mage::getModel('catalog/product')->setStoreId(1)->load(2);
        $this->assertTrue((boolean)$product->getLengowProduct());

    }

    /**
     *
     *  UnPublish one product to Store with global
     *
     */
    public function unPublishProductStoreGlobal()
    {

        //reset product
        $product_action = Mage::getSingleton('catalog/product_action');
        $product_action->updateAttributes(array(1,2), array('lengow_product' => 1), 2);
        //$product_action->updateAttributes(array(1), array('lengow_product' => 0), 0);

        $product = Mage::getModel('catalog/product')->setStoreId(2)->load(1);
        $this->assertTrue((boolean)$product->getLengowProduct());

        $product = Mage::getModel('catalog/product')->setStoreId(2)->load(2);
        $this->assertTrue((boolean)$product->getLengowProduct());

        // --------------------------------------
        // Test : UnPublish first product to store 1
        $this->getRequest()->setMethod('POST')
            ->setPost(array(
                "product" => array(1),
                "publish" => 0
            ));
        $this->dispatch('adminhtml/lengow_product/massPublish/');

        $product = Mage::getModel('catalog/product')->setStoreId(2)->load(1);
        $this->assertTrue((boolean)$product->getLengowProduct());

        $product = Mage::getModel('catalog/product')->setStoreId(2)->load(2);
        $this->assertTrue((boolean)$product->getLengowProduct());

    }

}