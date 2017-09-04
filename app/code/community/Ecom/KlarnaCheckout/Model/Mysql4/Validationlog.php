<?php

class Ecom_KlarnaCheckout_Model_Mysql4_Validationlog extends Mage_Core_Model_Mysql4_Abstract {
		
	public function _construct() {
		$this->_init('klarnacheckout/validationlog', 'id');
	}

}