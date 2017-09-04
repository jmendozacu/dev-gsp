<?php

class Ecom_KlarnaCheckout_Model_Mysql4_Lock_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {
	
	public function _construct() {
		$this->_init('klarnacheckout/lock');
	}	

}