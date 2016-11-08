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
        $export = Mage::getModel(
            'lengow/export',
            array(
                'store_id' => 1,
                'currency' => 'USD'
            )
        );
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
        $export = Mage::getModel(
            'lengow/export',
            array(
                'store_id'     => 1,
                'currency'     => 'USD',
                'types'        => 'simple',
                'out_of_stock' => '1',
                'status'       => ''
            )
        );
        $this->assertEquals(7, $export->getTotalExportedProduct(), 'Test All Products');

        $export = Mage::getModel(
            'lengow/export',
            array(
                'store_id'     => 1,
                'currency'     => 'USD',
                'types'        => 'simple',
                'out_of_stock' => '0',
                'status'       => ''
            )
        );
        $this->assertEquals(5, $export->getTotalExportedProduct(), 'Test Out of Stock Products');

        $export = Mage::getModel(
            'lengow/export',
            array(
                'store_id'     => 1,
                'currency'     => 'USD',
                'types'        => 'simple',
                'out_of_stock' => '1',
                'status'       => '1'
            )
        );
        $this->assertEquals(4, $export->getTotalExportedProduct(), 'Test Active Products');

        $export = Mage::getModel(
            'lengow/export',
            array(
                'store_id'     => 1,
                'currency'     => 'USD',
                'types'        => 'simple',
                'out_of_stock' => '1',
                'status'       => '2'
            )
        );
        $this->assertEquals(3, $export->getTotalExportedProduct(), 'Test Inactive Products');

        $export = Mage::getModel(
            'lengow/export',
            array(
                'store_id'     => 1,
                'currency'     => 'USD',
                'types'        => 'simple',
                'out_of_stock' => '1',
                'status'       => '1,2'
            )
        );
        $this->assertEquals(7, $export->getTotalExportedProduct(), 'Test Inactive And Active Products');
    }
}
