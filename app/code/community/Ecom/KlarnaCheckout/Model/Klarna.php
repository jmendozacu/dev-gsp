<?php

/*
 * Used by Service to interface against the Klarna API *  
 */

class Ecom_KlarnaCheckout_Model_Klarna extends Mage_Core_Model_Abstract {
	
	const XML_MERCHANT_EID = 'klarnacheckout/account/merchant_eid';
	const XML_MERCHANT_SHARED_SECRET = 'klarnacheckout/account/shared_secret';
	const XML_SERVER_TYPE = 'klarnacheckout/account/server';
	
	private function _getApi() {
		$apiFolder = Mage::getModuleDir('', 'Ecom_KlarnaCheckout') . DS . 'api' . DS . 'Klarna';
		
		require_once($apiFolder . DS . 'Klarna.php');
		
		// Dependencies from http://phpxmlrpc.sourceforge.net/
		require_once($apiFolder . DS . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc');
		require_once($apiFolder . DS . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc');
	}
	
	// TODO: use the fromValue functions to convert country, language and currency accordingly	
	private function _createInstance() {
		$this->_klarnaInstance = new Klarna();

		if(Mage::getStoreConfig(self::XML_SERVER_TYPE, $this->getStoreId()) == Ecom_KlarnaCheckout_Model_System_Config_Source_Klarna::KLARNA_SERVER_LIVE) {
			$server = Klarna::LIVE;
		} else {
			$server = Klarna::BETA;
		}
		
		$this->_klarnaInstance->config(
			Mage::getStoreConfig(self::XML_MERCHANT_EID, $this->getStoreId()),
			Mage::getStoreConfig(self::XML_MERCHANT_SHARED_SECRET, $this->getStoreId()),
			KlarnaCountry::SE,    // Country
			KlarnaLanguage::SV,   // Language
			KlarnaCurrency::SEK,  // Currency
			$server,         // Server
			'json',               // PClass Storage
			'/srv/pclasses.json', // PClass Storage URI path
			true,                 // SSL
			true                  // Remote logging of response times of xmlrpc calls
		);
	}
	
	/**
	 * 
	 * @param type $order_number Magento order number
	 */	
	public function activate($reservation_number) {
		$this->_getApi();	
		$this->_createInstance();
		
		// Optional fields should be set using
		// $this->_klarnaInstance->setActivateInfo()

		try {
			$result = $this->_klarnaInstance->activate($reservation_number);
			$risk = $result[0]; // ok or no_risk
			$invoice_number = $result[1];

			Mage::log("risk: {$risk}\ninvno: {$invoice_number}");
			// Reservation is activated, proceed accordingly.
			
			return $invoice_number;
			
		} catch(Exception $e) {			
			Mage::logException($e);			
			return null;
		}
		
	}

    /**
     * Cancel a reservation
     * @param int $reservation_number Klarna reservation no
     */
    public function cancel($reservation_number) {
        $this->_getApi();
        $this->_createInstance();

        try {
            return $this->_klarnaInstance->cancelReservation($reservation_number);

        } catch(Exception $e) {
            Mage::logException($e);
            return null;
        }

    }

    /**
     * refund a invoice
     * @param int $invoice_number Klarna invoice no
     */
    public function refund($invoice_number) {
        $this->_getApi();
        $this->_createInstance();

        try {
            return $this->_klarnaInstance->creditInvoice($invoice_number);

        } catch(Exception $e) {
            Mage::logException($e);
            return null;
        }

    }
	
}