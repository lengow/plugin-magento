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
        /** @var Lengow_Connector_Model_Export $export */
        $export = Mage::getModel(
            'lengow/export',
            array(
                'store_id' => 1,
                'currency' => 'USD',
            )
        );
        $this->assertEquals(4, $export->getTotalProduct());
    }

    /**
     * Test getTotalExportProduct
     *
     * @test
     * @loadFixture
     * @doNotIndexAll
     */
    public function getTotalExportProduct()
    {
        /** @var Lengow_Connector_Model_Export $export */
        $export = Mage::getModel(
            'lengow/export',
            array(
                'store_id' => 1,
                'currency' => 'USD',
                'types' => 'simple',
                'out_of_stock' => '1',
                'status' => '',
            )
        );
        $this->assertEquals(7, $export->getTotalExportProduct(), 'Test All Products');
        /** @var Lengow_Connector_Model_Export $export */
        $export = Mage::getModel(
            'lengow/export',
            array(
                'store_id' => 1,
                'currency' => 'USD',
                'types' => 'simple',
                'out_of_stock' => '0',
                'status' => '',
            )
        );
        $this->assertEquals(5, $export->getTotalExportProduct(), 'Test Out of Stock Products');
        /** @var Lengow_Connector_Model_Export $export */
        $export = Mage::getModel(
            'lengow/export',
            array(
                'store_id' => 1,
                'currency' => 'USD',
                'types' => 'simple',
                'out_of_stock' => '1',
                'status' => '1',
            )
        );
        $this->assertEquals(4, $export->getTotalExportProduct(), 'Test Active Products');
        /** @var Lengow_Connector_Model_Export $export */
        $export = Mage::getModel(
            'lengow/export',
            array(
                'store_id' => 1,
                'currency' => 'USD',
                'types' => 'simple',
                'out_of_stock' => '1',
                'status' => '2',
            )
        );
        $this->assertEquals(3, $export->getTotalExportProduct(), 'Test Inactive Products');
        /** @var Lengow_Connector_Model_Export $export */
        $export = Mage::getModel(
            'lengow/export',
            array(
                'store_id' => 1,
                'currency' => 'USD',
                'types' => 'simple',
                'out_of_stock' => '1',
                'status' => '1,2',
            )
        );
        $this->assertEquals(7, $export->getTotalExportProduct(), 'Test Inactive And Active Products');
    }
}
