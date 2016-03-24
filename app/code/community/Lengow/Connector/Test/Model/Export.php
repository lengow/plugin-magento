<?php

class Lengow_Connector_Test_Model_Export extends EcomDev_PHPUnit_Test_Case
{

    /**
     * Test getTotalProduct
     *
     * @test
     * @loadFixture
     * @doNotIndexAll
     */
    public function getTotalProduct()
    {
        $export = Mage::getModel('lengow/export', array(
            "store_id" => 1,
            "currency" => "USD"
        ));
        $this->assertEquals(4, $export->getTotalProduct());
    }


    /**
     * Test getTotalExportedProduct
     *
     * @test
     * @loadFixture
     * @doNotIndexAll
     */
    public function getTotalExportedProduct()
    {
//        $export = Mage::getModel('lengow/export', array(
//            "store_id" => 1,
//            "currency" => "USD",
//            "types" => "simple",
//        ));
//        $this->assertEquals(7, $export->getTotalExportedProduct());


//        $export = Mage::getModel('lengow/export', array(
//            "store_id" => 1,
//            "currency" => "USD",
//            "types" => "simple",
//            "out_of_stock" => "0"
//        ));
//        $this->assertEquals(5, $export->getTotalExportedProduct());
    }
}
