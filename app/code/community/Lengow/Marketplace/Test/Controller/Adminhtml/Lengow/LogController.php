<?php

class Lengow_Marketplace_Test_Controller_Adminhtml_Lengow_LogController extends EcomDev_PHPUnit_Test_Case_Controller
{

    public function testLogIndex(){
        $this->mockAdminUserSession();

        //test page
        $this->dispatch('adminhtml/lengow_log/index');
        $this->assertRequestRoute('adminhtml/lengow_log/index', '[Log - Url]');

        //test flush logs
        Mage::helper('lengow_marketplace')->log('Test message by helper');
        $this->dispatch('adminhtml/lengow_log/delete');
        $collection = Mage::getModel('lengow/log')->getCollection();
        $this->assertEquals(count($collection), 0, '[Log - Collection] should be empty');

    }

}