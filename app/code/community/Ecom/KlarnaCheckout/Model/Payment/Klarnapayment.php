<?php

class Ecom_KlarnaCheckout_Model_Payment_Klarnapayment extends Mage_Payment_Model_Method_Abstract {

	protected $_code = 'klarnapayment';

    protected $_canAuthorize = true;
	protected $_canCapture = true;	
	protected $_canCancel = true;
    protected $_canRefund = true;
    protected $_canUseForMultishipping = false;
	
	protected $_formBlockType = 'klarnacheckout/payment_form';
	protected $_infoBlockType = 'klarnacheckout/payment_info';

    const XML_CALLBACK_CAPTURE = 'klarnacheckout/callback/capture';
    const XML_CALLBACK_REFUND = 'klarnacheckout/callback/refund';

	public function capture(Varien_Object $payment, $amount) {

        // if klarna should not be informed, skip this method
        $storeId=$payment->getOrder()->getStoreId();
        if(!Mage::getStoreConfig(self::XML_CALLBACK_CAPTURE,$storeId)) return parent::capture($payment, $amount);

		$reservation = $payment->getAdditionalInformation('kco_reservation');
		if(!$reservation) {
            $this->_helper()->logMinimal('payment-method-capture','', 'Capture error, no reservation. order: '.$payment->getOrder()->getIncrementId());
			Mage::throwException(Mage::helper('klarnacheckout')->__('klarnapayment capture - no klarna reservation number'));
		}

		$this->_helper()->logMinimal('payment-method-capture','', 'Capture reservation: '.$reservation.' order: '.$payment->getOrder()->getIncrementId());
		
		// Activate via Klarna API
		$klarna = Mage::getModel('klarnacheckout/klarna');

		// set correct storeId from The Order
		$klarna->setStoreId($payment->getOrder()->getStoreId());

		$invoice_number = $klarna->activate($reservation);
		if(is_null($invoice_number)) {
            $this->_helper()->logMinimal('payment-method-capture','', 'Capture error, Klarna responded with exception. order: '.$payment->getOrder()->getIncrementId());
			Mage::throwException(Mage::helper('klarnacheckout')->__('klarnapayment capture - Klarna responded with exception'));
		}
		
		// If all is well, save the invoice number in payment
        $payment->setTransactionId($invoice_number);
		$payment->setAdditionalInformation('kco_invoice', $invoice_number);
		$payment->save();
		
		return $this;
	}

    public function refund(Varien_Object $payment, $amount) {

        // if klarna should not be informed, skip this method
        $storeId=$payment->getOrder()->getStoreId();
        if(!Mage::getStoreConfig(self::XML_CALLBACK_REFUND,$storeId) || !Mage::getStoreConfig(self::XML_CALLBACK_CAPTURE,$storeId)) return parent::refund($payment, $amount);

        $invoice = $payment->getAdditionalInformation('kco_invoice');
        $reservation = $payment->getAdditionalInformation('kco_reservation');
        if(!$invoice) {
            $this->_helper()->logMinimal('payment-method-refund','', 'refund error, no invoice. order: '.$payment->getOrder()->getIncrementId());
            Mage::throwException(Mage::helper('klarnacheckout')->__('klarnapayment refund - no klarna invoice number'));
        }

        $this->_helper()->logMinimal('payment-method-refund','', 'refund invoice: '.$invoice.' order: '.$payment->getOrder()->getIncrementId().' reservation: '.$reservation);

        // get Klarna API
        $klarna = Mage::getModel('klarnacheckout/klarna');

        // set correct storeId from The Order
        $klarna->setStoreId($payment->getOrder()->getStoreId());

        $invoice_number = $klarna->refund($invoice);
        if(is_null($invoice_number)) {
            $this->_helper()->logMinimal('payment-method-refund','', 'Refund error, Klarna responded with exception. order: '.$payment->getOrder()->getIncrementId().' reservation: '.$reservation);
            Mage::throwException(Mage::helper('klarnacheckout')->__('klarnapayment refund - Klarna responded with exception'));
        }

        // If all is well, save the invoice number in payment
        $payment->setAdditionalInformation('kco_refunded_invoice', $invoice_number);
        $payment->save();

        return $this;
    }

    /**
     * @return Ecom_KlarnaCheckout_Helper_Data
     */
    private function _helper() {
        return Mage::helper('klarnacheckout');
    }
		
}
