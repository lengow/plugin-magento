<?php

class Lengow_Marketplace_Test_Model_Log extends EcomDev_PHPUnit_Test_Case
{

    /**
     * Test log model
     *
     * @test
     * @loadFixture
     * @doNotIndexAll
     */
    public function log()
    {
        $collection = Mage::getModel('lengow/log')->getCollection();
        foreach($collection as $log){
            $this->assertRegExp('/Test message/',$log->getMessage(), '[Log - Read] Message data should contain "Test message"');
        }
        $this->assertEquals(count($collection), 3, '[Log - Fixture] contain 3 messages');

        $data = array(
            'created_at' => '2015-09-01 23:59:59',
            'message' => 'Test message 4'
        );
        $model = Mage::getModel('lengow/log')->setData($data);
        $insertId = $model->save()->getId();
        $this->assertTrue($insertId > 0, '[Log - Insert] Insert data into log');

        $insertLog = Mage::helper('lengow_marketplace')->log('Test message by helper');
        $this->assertTrue((boolean)$insertLog, '[Log - Helper Insert] Insert message');

        $log = Mage::getModel('lengow/log')->load($insertLog->getId());
        $this->assertEquals($log->getId(), $insertLog->getId(), '[Log - Helper Insert] check data');
        $this->assertEquals($log->getMessage(), 'Test message by helper', '[Log - Helper Insert] check data');
    }

}