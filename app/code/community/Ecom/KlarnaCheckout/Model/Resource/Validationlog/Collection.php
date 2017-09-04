<?php

class Ecom_KlarnaCheckout_Model_Resource_Validationlog_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract {
	
	public function _construct() {
		$this->_init('klarnacheckout/validationlog');
	}	

}