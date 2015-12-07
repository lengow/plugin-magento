<?php

class Lengow_Marketplace_Test_Controller_Adminhtml_Lengow_LogController extends Lengow_Marketplace_Test_Case
{

    /**
     * test log index
     *
     * @test
     *
     */
    public function logIndex(){
        $this->mockAdminUserSession();

        //test page
        $this->dispatch('adminhtml/lengow_log/index');
        $this->assertRequestRoute('adminhtml/lengow_log/index', '[Log - Url]');
    }

    /**
     * test flush logs
     *
     * @test
     *
     */
    public function flushLog(){
        $this->mockAdminUserSession();

        Mage::helper('lengow_marketplace')->log('Test message by helper');
        $this->dispatch('adminhtml/lengow_log/delete');
        $collection = Mage::getModel('lengow/log')->getCollection();
        $this->assertEquals(0, count($collection), '[Log - Collection] should be empty');
    }

}