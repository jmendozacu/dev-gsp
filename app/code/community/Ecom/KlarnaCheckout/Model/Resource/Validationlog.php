<?php

class Ecom_KlarnaCheckout_Model_Resource_Validationlog extends Mage_Core_Model_Resource_Db_Abstract {
		
	public function _construct() {
		$this->_init('klarnacheckout/validationlog', 'id');
	}

}