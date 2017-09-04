<?php

class Ecom_KlarnaCheckout_Model_Service_Order extends Mage_Core_Model_Abstract {
	
	const PAYMENT_METHOD_CODE = 'klarnapayment';
	
	public function orderExistsWithQuote($quote_id) {

		$order = Mage::getModel('sales/order')->getCollection()
				->addFieldToFilter('quote_id', $quote_id);

		return $order->count() ? true : false;
	}
	
	public function getOrderFromQuoteId($quote_id) {

		$order = Mage::getModel('sales/order')->getCollection()
				->addFieldToFilter('quote_id', $quote_id)
				->getFirstItem();

		return $order;
	}

	public function createOrder($quote, $billingInfo, $reservation) {
		
		// Create billing and shipping address
		$addressData = array(
			'firstname' => $billingInfo['first_name'],
			'lastname' => $billingInfo['last_name'],
			'street' => $billingInfo['street'],
			'city' => $billingInfo['city'],
			'postcode' => $billingInfo['postcode'],
			'telephone' => $billingInfo['phone'],
			'country_id' => strtoupper($billingInfo['country']),
		);

		// Set billing address
		$quote->getBillingAddress()->addData($addressData);
		
        // Shipping method
        // Get selected from quote, or fall back to preselected in admin
        if(!($shippingMethod = $quote->getShippingAddress()->getShippingMethod())) $shippingMethod = Mage::getStoreConfig(Ecom_KlarnaCheckout_Helper_Data::XML_PATH_SHIPPING_METHOD);
        
		// Shipping and payment methods				
		$quote->getShippingAddress()
			->addData($addressData)
			->setShippingMethod($shippingMethod)
			->setPaymentMethod(self::PAYMENT_METHOD_CODE)
			->setCollectShippingRates(true)
			->collectTotals();
			
		$quote->getPayment()->importData(array('method' => self::PAYMENT_METHOD_CODE));
		$quote->getPayment()->setAdditionalInformation('kco_reservation', $reservation);
		//$quote->save();

		$service = Mage::getModel('sales/service_quote', $quote);
		$service->submitAll();
		$order = $service->getOrder();
		$quote->save(); // sets quote to inactive, so it's not used again..
		
		if($order->getId())
			$order->sendNewOrderEmail();
		
		return $order;
	}

}