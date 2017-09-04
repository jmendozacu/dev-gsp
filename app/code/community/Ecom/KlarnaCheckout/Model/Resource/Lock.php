<?php

class Ecom_KlarnaCheckout_Model_Resource_Lock extends Mage_Core_Model_Resource_Db_Abstract {
		
	public function _construct() {
		$this->_init('klarnacheckout/lock', 'id');
	}

}