<?php

class Lengow_Connector_Test_Controller_Adminhtml_Lengow_LogController extends Lengow_Connector_Test_Case
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
        $this->dispatch('adminhtml/lengow_log/index');
        $this->assertRequestRoute('adminhtml/lengow_log/index', '[Log - Url]');
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
        $this->dispatch('adminhtml/lengow_log/grid');
        $this->assertRequestRoute('adminhtml/lengow_log/grid', '[Log - Url]');
    }
}
