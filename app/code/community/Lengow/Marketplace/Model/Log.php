<?php
/**
 * Lengow sync model log
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Marketplace_Model_Log extends Mage_Core_Model_Abstract {

	protected function _construct()
	{
		$this->_init('lengow/log');
	}
	
	/**
	 * Save message event
	 * @param $message string
	 *
	 * @return boolean
	 */
	public function log($message)
	{
		if (strlen($message)>0){
			$this->setMessage($message);
			return $this->save();
		}else{
			return false;
		}
	}
	
}