<?php

class Ecom_KlarnaCheckout_Block_Payment_Info extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('klarnacheckout/payment/info.phtml');
    }
	
	public function getInvoiceNumber() {
		return $this->getInfo()->getAdditionalInformation('kco_invoice');
	}
	
}