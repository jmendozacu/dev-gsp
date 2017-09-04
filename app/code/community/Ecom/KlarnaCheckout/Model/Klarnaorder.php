<?php

class Ecom_KlarnaCheckout_Model_Klarnaorder extends Mage_Core_Model_Abstract {
	
	const ORDER_STATUS_CHECKOUT_INCOMPLETE = 'checkout_incomplete';
	
	const XML_MERCHANT_EID = 'klarnacheckout/account/merchant_eid';
	const XML_MERCHANT_SHARED_SECRET = 'klarnacheckout/account/shared_secret';
	const XML_SERVER_TYPE = 'klarnacheckout/account/server';
	
	const BASE_URI_LIVE = 'https://checkout.klarna.com/checkout/orders';
	const BASE_URI_TEST = 'https://checkout.testdrive.klarna.com/checkout/orders';
		
	private $_order = null;
	private $_orderUri = null;

	private function _getApi() {
		require_once(Mage::getModuleDir('', 'Ecom_KlarnaCheckout') . DS . 'api' . DS . 'Checkout.php');
		
		if(Mage::getStoreConfig(self::XML_SERVER_TYPE) == Ecom_KlarnaCheckout_Model_System_Config_Source_Klarna::KLARNA_SERVER_LIVE) {
			$baseUri = self::BASE_URI_LIVE;
		} else {
			$baseUri = self::BASE_URI_TEST;
		}
		
		Klarna_Checkout_Order::$baseUri	= $baseUri;
		Klarna_Checkout_Order::$contentType	= "application/vnd.klarna.checkout.aggregated-order-v2+json";

		$this->_connector = Klarna_Checkout_Connector::create(Mage::getStoreConfig(self::XML_MERCHANT_SHARED_SECRET));
	}

    /**
     * @return Klarna_Checkout_Order
     */
    public function getOrder(){
        /** @var Klarna_Checkout_Order $order */
        $order = $this->_order;

        return $order;
    }
	
	public function getCart() {
		return isset($this->_order['cart']) ? $this->_order['cart'] : array();
	}
	
	public function getStatus() {
		return $this->_order['status'];
	}
	
	public function getSnippet() {
		return $this->_order['gui']['snippet'];
	}
	
	public function getOrderId() {
		if($this->_order != null && isset($this->_order['id']))
			return $this->_order['id'];
		else
			return 0;
	}
	
	public function getMerchantReference1() {
		if(isset($this->_order['merchant_reference']['orderid1'])) {
			return $this->_order['merchant_reference']['orderid1'];
		} else {
			return null;
		}
	}
	
	public function getOrderUri() {
		return $this->_order->getLocation();
	}
	
	public function getReservation() {
		return isset($this->_order['reservation']) ? $this->_order['reservation'] : 0;
	}
	
	public function getBillingInfo() {
		$info = array();
		
		if(isset($this->_order['billing_address'])) {
			
			$billing = $this->_order['billing_address'];
			
			$info['first_name'] = $billing['given_name'];
			$info['last_name'] = $billing['family_name'];
			$info['email'] = $billing['email'];
			$info['phone'] = $billing['phone'];	
			$info['street'] = $billing['street_address'];
			$info['postcode'] = $billing['postal_code'];
			$info['city'] = $billing['city'];
			$info['country'] = $billing['country'];
		}
		
		return $info;
	}
	
	public function create($cart, $country, $currency, $locale, $email = '', $postcode = '') {
		
		$this->_getApi();
		
		if ($this->_order == null) {
						
			$create['purchase_country'] = $country;
			$create['purchase_currency'] = $currency;
			$create['locale'] = $locale;		
			$create['merchant']['id'] = Mage::getStoreConfig('klarnacheckout/account/merchant_eid');
			$create['merchant']['terms_uri'] = Mage::helper('klarnacheckout')->getTermsUri();
			$create['merchant']['checkout_uri'] = Mage::helper('klarnacheckout')->getCheckoutUri();
			$create['merchant']['confirmation_uri']	= Mage::helper('klarnacheckout')->getConfirmationUri();
			$create['merchant']['push_uri'] = Mage::helper('klarnacheckout')->getPushUri();
			
			if(Mage::getStoreConfig(Ecom_KlarnaCheckout_Helper_Data::XML_PATH_VALIDATION))
				$create['merchant']['validation_uri'] = Mage::helper('klarnacheckout')->getValidationUri();
			
			// Check if a mobile phone is being used (needs Ecom_Utils version 1.1.1 to be installed and active)
			if(Mage::helper('core')->isModuleEnabled('Ecom_Utils') && 
				(string) Mage::getConfig()->getModuleConfig("Ecom_Utils")->version >= '1.1.1') {
				if(Mage::helper('utils/useragent')->isMobile()) {
					 $create['gui']['layout'] = 'mobile';
				}
			}
			
			// disable autofocus
			if(Mage::getStoreConfig(Ecom_KlarnaCheckout_Helper_Data::XML_PATH_DISABLE_AUTOFOCUS))
				$create['gui']['options'][] = 'disable_autofocus';
			
			// Nedan ska göras om kunden är inloggad
			if($email) {
				$create['shipping_address']['email'] = $email;
				if($postcode) $create['shipping_address']['postal_code'] = $postcode; // postnummer från förvald leveransadress
			}
			
			foreach ($cart as $item) {
				$create['cart']['items'][] = $item;
			}
			$this->_order = new Klarna_Checkout_Order($this->_connector);
			$this->_order->create($create);
			$this->_order->fetch();
		}
		
		$this->_orderUri = $this->_order->getLocation();
		
		return $this;
	}
	
	public function update($cart, $postcode = '', $email = '') {				
		$update = array();

		// Reset cart
		$update['cart']['items'] = array();
		
		foreach ($cart as $item) {
			$update['cart']['items'][] = $item;
		}
		
		if($postcode){
			$update['shipping_address']['postal_code'] = $postcode;
		}
		
		if($email){
			$update['shipping_address']['email'] = $email;
		}
		
		$this->_order->update($update);
	}
	
	public function fetch($order_uri) {
		$this->_getApi();

		$this->_order = new Klarna_Checkout_Order($this->_connector, $order_uri);
		$this->_order->fetch();
		
		$this->_orderUri = $this->_order->getLocation();
	}
	
	public function complete($increment_id) {
		$update = array();
		
		$update['status'] = 'created';
		$update['merchant_reference'] = array('orderid1' => $increment_id);
	
		$this->_order->update($update);		
	}

    public function cancel() {


        //Mage::log($this->_order["reservation"]);

        // Activate via Klarna API
        $klarna = Mage::getModel('klarnacheckout/klarna');

        $success = $klarna->cancel($this->_order["reservation"]);
        if(!$success) {
            Mage::log("KCO: cant cancel reservation: ".$this->_order["reservation"]);
        }

        //cancelReservation($this->_order["reservation"]);
    }
	
}