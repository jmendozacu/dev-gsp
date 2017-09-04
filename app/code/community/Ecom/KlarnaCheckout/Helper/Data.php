<?php

class Ecom_KlarnaCheckout_Helper_Data extends Mage_Core_Helper_Abstract {

	const LOG_FILENAME = 'klarnacheckout.log';
    const ORDER_LOG_FILENAME = 'klarnacheckout_object.log';
	
	const XML_MERCHANT_EID = 'klarnacheckout/account/merchant_eid';
	const XML_MERCHANT_SHARED_SECRET = 'klarnacheckout/account/shared_secret';
	const XML_SERVER_TYPE = 'klarnacheckout/account/server';
	const XML_TEST_MERCHANT_EID = 'klarnacheckout/account/test_merchant_eid';
	const XML_TEST_MERCHANT_SHARED_SECRET = 'klarnacheckout/account/test_shared_secret';
	const XML_PATH_SHIPPING_METHOD = 'klarnacheckout/checkout/default_shipping_method';
    const XML_PATH_ENABLED = 'klarnacheckout/checkout/enabled';
	const XML_PATH_REWRITE_LINKS = 'klarnacheckout/checkout/rewrite_links';
    const XML_PATH_REWRITE_SWEETTOOTH_LINKS = 'klarnacheckout/checkout/rewrite_sweettooth_links';
    const XML_PATH_FORCE_AUTHENTICATED_CHECKOUT = 'klarnacheckout/checkout/force_authenticated_checkout';
	const XML_PATH_DISABLE_AUTOFOCUS = 'klarnacheckout/checkout/disable_autofocus';
	const XML_PATH_TERMS_URI = 'klarnacheckout/checkout/terms_uri';
	const XML_PATH_SHOW_SHIPPING_METHOD = 'klarnacheckout/checkout/show_shipping_method';
	const XML_PATH_USE_TWOSTEP = 'klarnacheckout/checkout/use_twostep';
	const XML_PATH_SHOW_POSTCODE = 'klarnacheckout/checkout/show_postcode';
	const XML_PATH_VALIDATION = 'klarnacheckout/secure/validation';
	const XML_PATH_CARTOBSERVER = 'klarnacheckout/secure/cart_observer';
	
	public function isRewriteCheckoutLinksEnabled() {
        if (Mage::getStoreConfig(self::XML_PATH_ENABLED)!='1') return false;
		if (Mage::getStoreConfig(self::XML_PATH_REWRITE_LINKS)!='1') return false;
		return true;
	}

	public function getTermsUri() {		
		$uri = Mage::getBaseUrl() . Mage::getStoreConfig(self::XML_PATH_TERMS_URI);
		return $uri;
	}

	public function getCheckoutUri() {
		$uri = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, true) . 'klarnacheckout/';
		return $uri;
	}

	public function getConfirmationUri() {
		$uri = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, true) . 'klarnacheckout/index/confirmation/' . '?sid=123&klarna_order={checkout.order.uri}';
		return $uri;
	}

	public function getValidationUri() {
		/* NOTE: HAS TO BE httpS !!!*/
		$uri = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, true) . 'klarnacheckout/index/validation/';
		return $uri;
	}

	public function getPushUri() {
		$uri = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, true) . 'klarnacheckout/index/push/' . '?sid=123&klarna_order={checkout.order.uri}';
		return $uri;
	}
	
	// Returns the current store locale code adjusted for use with Klarna API
	public function getLocaleCode() {
		$code = strtolower(Mage::app()->getLocale()->getLocaleCode());
		return str_replace('_', '-', $code);
	}

    /**
     * Use for normal loggin
     * Will log to two files, one for normal flow-log, and one that prints the order object for each row
     * The two are linked together by unique ID
     * Also notice the little star at the end, that indicates that there is a linked object loged
     *
     * @param string $action
     * @param Ecom_KlarnaCheckout_Model_Klarnaorder $order
     * @param string $message
     */
	public function log($action, Ecom_KlarnaCheckout_Model_Klarnaorder $order, $message) {
        $log_id = uniqid();

        if(!is_object($order)) $order = new Varien_Object();

        $res = $order->getReservation() ? $order->getReservation() : "no_reservation";

		Mage::log("[".$log_id."] ".$this->uriToId($order->getOrderUri())." -> ".$action." -> ".$res." -> ".$message." *", null, self::LOG_FILENAME);
        Mage::log("[".$log_id."] ".print_r($order,true), null, self::ORDER_LOG_FILENAME);
	}

    /**
     * Use when no orderobject is available
     *
     * @param string $action
     * @param string $uri
     * @param string $message
     */
    public function logMinimal($action, $uri, $message) {
        $log_id = uniqid();
        Mage::log("[".$log_id."] ".$this->uriToId($uri)." -> ".$action." -> no_reservation -> ".str_replace("\n","",$message), null, self::LOG_FILENAME);
    }

    public function uriToId($uri){
        $parts = explode("/",$uri);
        return array_pop($parts);
    }
	
	public function getMinimumMonthlyPrice($totalAmount) {
		$factor = 24.00275103163686;
		$minimumMonthlyPrice = $totalAmount / $factor;
		
		if($minimumMonthlyPrice < 50){
			$minimumMonthlyPrice = 50;
		}
		
		return $minimumMonthlyPrice;
	}
	
	public function getCustomerPostcode(){
		$customer_session = Mage::getSingleton('customer/session');
		$quote = Mage::getSingleton('checkout/cart')->getQuote();
		
		if(!$customer_session->isLoggedIn()){
			
			// if the quotes postcode is equal to the default AND the customer has not enered one, return empty.
			if($quote->getShippingAddress()->getPostcode() == Mage::getStoreConfig('tax/defaults/postcode') && !$customer_session->getKcoHasSetPostcode()) return '';
			
			return $quote->getShippingAddress()->getPostcode();
		}
		else {
			$customer = $customer_session->getCustomer();
			$shippingId = $customer->getDefaultShipping();

            if($customer_session->getKcoHasSetPostcode()){
                return $quote->getShippingAddress()->getPostcode();
            }
			elseif($shippingId) {
				$address = Mage::getModel('customer/address')->load($shippingId);
				return $address->getPostcode();
			}
		}
		
		return null;
	}
	
	public function getShippingMethodCode() {
		return Mage::getStoreConfig(Ecom_KlarnaCheckout_Helper_Data::XML_PATH_SHIPPING_METHOD);
	}
	
	public function stepOneComplete(){
		$customer_session = Mage::getSingleton('customer/session');
		
		if(!Mage::getStoreConfig(Ecom_KlarnaCheckout_Helper_Data::XML_PATH_USE_TWOSTEP)) return true;
		
		if(!$customer_session->isLoggedIn() && !$customer_session->getKcoEmail()) return false;
		
		if (Mage::getStoreConfig(Ecom_KlarnaCheckout_Helper_Data::XML_PATH_SHOW_POSTCODE) && !Mage::helper('klarnacheckout')->getCustomerPostcode()) return false;
		
		return true;
	}

    //Determine if redirect should be used for checkout links
    public function useSweetToothCartRedirect(){
        $userLoggedIn = Mage::getSingleton('customer/session')->isLoggedIn();
        $rewriteEnabled = Mage::getStoreConfig(self::XML_PATH_REWRITE_SWEETTOOTH_LINKS);
        $sweetToothEnabled = Mage::helper('core')->isModuleEnabled('TBT_Rewards');

        return $userLoggedIn && $rewriteEnabled && $sweetToothEnabled;
    }

}