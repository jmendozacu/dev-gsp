<?php

class Ecom_Klarnacheckout_Block_Payment_Form extends Mage_Payment_Block_Form {

	protected function _construct() {
		parent::_construct();
		#$this->setTemplate('invoicepayment/form.phtml');
	}

}