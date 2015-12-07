<?php

class Lengow_Marketplace_Test_Controller_Adminhtml_Lengow_ProductController extends Lengow_Marketplace_Test_Case
{

    /**
     * test product index
     *
     * @test
     *
     */
    public function productIndex(){
        $this->mockAdminUserSession();

        //test page
        $this->dispatch('adminhtml/lengow_product/index');
        $this->assertRequestRoute('adminhtml/lengow_product/index', '[Log - Url]');
    }


}