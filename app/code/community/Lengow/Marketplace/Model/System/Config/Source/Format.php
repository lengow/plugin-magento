<?php
/**
 * Lengow export model systems config source format 
 * format of export
 *
 * @category    Lengow
 * @package     Lengow_Export
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Marketplace_Model_System_Config_Source_Format extends Mage_Core_Model_Config_Data {

    public function toOptionArray() {
        return array(
            array('value' => 'csv', 
            	  'label' => 'csv'),
            array('value' => 'xml', 
            	  'label' => 'xml'),
            array('value' => 'json', 
            	  'label' => 'json'),
            array('value' => 'yaml', 
                  'label' => 'yaml'),
        );
    }
    
    public function toSelectArray() {
        $select = array();
        foreach($this->toOptionArray() as $option) {
            $select[$option['value']] = $option['label'];
        }
        return $select;
    }
}