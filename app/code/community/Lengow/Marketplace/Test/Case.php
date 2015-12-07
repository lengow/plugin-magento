<?php

class Lengow_Marketplace_Test_Case extends EcomDev_PHPUnit_Test_Case_Controller
{

    public function mockAdminUserSession()
    {
        Mage::unregister('_singleton/index/indexer');
        parent::mockAdminUserSession();
    }

}