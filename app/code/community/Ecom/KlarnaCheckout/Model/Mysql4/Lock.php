<?php

class Ecom_KlarnaCheckout_Model_Mysql4_Lock extends Mage_Core_Model_Mysql4_Abstract {
		
	public function _construct() {
		$this->_init('klarnacheckout/lock', 'id');
	}

}