<?php

class Lengow_Marketplace_Test_Controller_Adminhtml_Lengow_LogController extends Lengow_Marketplace_Test_Case
{

    /**
     * test index action
     *
     * @test
     *
     */
    public function indexAction(){
        $this->mockAdminUserSession();

        //test page
        $this->dispatch('adminhtml/lengow_log/index');
        $this->assertRequestRoute('adminhtml/lengow_log/index', '[Log - Url]');
    }

    /**
     * test grid action
     *
     * @test
     *
     */
    public function gridAction(){
        $this->mockAdminUserSession();

        //test page
        $this->dispatch('adminhtml/lengow_log/grid');
        $this->assertRequestRoute('adminhtml/lengow_log/grid', '[Log - Url]');
    }



    /**
     * test delete action
     *
     * @test
     *
     */
    public function deleteAction(){
        $this->mockAdminUserSession();

        Mage::helper('lengow_marketplace')->log('Test message by helper');
        $this->dispatch('adminhtml/lengow_log/delete');
        $collection = Mage::getModel('lengow/log')->getCollection();
        $this->assertEquals(0, count($collection), '[Log - Collection] should be empty');
    }

}
