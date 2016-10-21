<?php

class Lengow_Connector_Test_Controller_FeedController extends Lengow_Connector_Test_Case
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
        $this->dispatch('lengow/feed/index');
        $this->assertRequestRoute('lengow/feed/index', '[Log - Url]');
    }

}
