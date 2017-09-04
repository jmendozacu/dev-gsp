<?php

class Ecom_KlarnaCheckout_Model_Service extends Mage_Core_Model_Abstract {
	
	private function _quoteToCart($quote) {
		
		$cart = array();		
		
		foreach($quote->getAllVisibleItems() as $item) {
			
			$cart[] = array(
				'reference' => $item->getSku(),
				'name' => $item->getName(),
				'quantity' => $item->getQty(),
				'unit_price' => $item->getBasePriceInclTax() * 100,
				'tax_rate' => $item->getTaxPercent() * 100
			);
		}
		
		return $cart;
	}
	
	private function _getQuoteDiscount($quote) {
		return round(-(float)Mage::helper('klarnacheckout/discount')->getDiscountData($quote)->getDiscountInclTax(),2);
	}

	private function _getShippingPrice() {
		$data = Mage::getSingleton("klarnacheckout/cart")->_collectShippingInfo();
		if(Mage::getStoreConfig("tax/cart_display/shipping") == '1') return $data->getShippingExclTax();
		else return $data->getShippingInclTax();
	}
	
	public function getQuoteMap($order_uri) {
		$quotemap = Mage::getModel('klarnacheckout/quotemap')->getCollection()
			->addFieldToFilter('order_id', $order_uri)
			->getFirstItem();
		
		if($quotemap->getId() > 0)
			return $quotemap;
		else
			return null;
	}
	
	private function _addDiscount($discount, $code) {
		if(!$code) $code = 'DISCOUNT';
	
		$item = array(
			'type' => 'discount',
			'reference' => $code,
			'name' => Mage::helper("klarnacheckout")->__('Discount'),
			'quantity' => 1,
			'unit_price' => $discount * 100, // All prices in cents
			'tax_rate' => 2500 // @TODO: get from system config
		);
				
		return $item;
	}
	
	private function _addShipping($shipping) {
		$item = array(
			'type' => 'shipping_fee',
			'reference' => 'SHIPPING',
			'name' => 'Shipping fee',
			'quantity' => 1,
			'unit_price' => $shipping * 100, // All prices in cents
			'tax_rate' => 2500 // @TODO: get from system config
		);
		
		return $item;
	}
	
	public function createOrUpdateOrder(Mage_Sales_Model_Quote $quote) {

		$cart = $this->_quoteToCart($quote);
		
		if(!$cart) return;

        /* This variable is used to indicate if a saved discount was loaded.
        *  Later if,
        *  ($discountApplied == false && sweettooth discount is found) => add missing sweettooth discount.
        */
        $discountApplied = false;

        // Add discount if coupon code has been applied
        $discount = $this->_getQuoteDiscount($quote);
        if($discount){
            $cart[] = $this->_addDiscount($discount, $quote->getCouponCode());
            $discountApplied = true;
        }
				
		// Add shipping
		$shipping = $this->_getShippingPrice($quote);
		if($shipping)
			$cart[] = $this->_addShipping($shipping);

		// is there any other totals that need to be added?
		$totals = $quote->getTotals();
		//Zend_Debug::dump($totals);
		foreach ($totals as $total_key => $total) {
			if($total->getValue()==null || $total->getValue() == 0) continue;

            /* Sweettooth adds the discount after the qoute has loaded and thus the code
            *  above on line 80 doesn't add the discount (even if it should be applied)
            *
            *  Even without this code though the sweettooth discount is applied on refresh when on klarna checkout page.
            */
            if(Mage::helper('core')->isModuleEnabled('TBT_Rewards') && $total_key == 'discount' && !$discountApplied){
                $cart[] = $this->_addDiscount(($total->getValue()), 'DISCOUNT');
            }

			if(in_array($total_key,array("subtotal","shipping","tax","grand_total","discount"))) continue; // skip some totals

			$item = array(
				'type' => ($total->getValue() < 0) ? 'discount' : '',
				'reference' => $total_key,
				'name' => $total->getTitle(),
				'quantity' => 1,
				'unit_price' => $total->getValue() * 100, // All prices in cents
				'tax_rate' => 2500 // @TODO: get from ??
			);
			if(!$item['type']) unset($item['type']);
			$cart[]=$item;
		}


		$session = Mage::getSingleton('checkout/session');		
		$customer_session = Mage::getSingleton('customer/session');	
		$klarnaOrder = null;
		
		// set postcode & email
		$customer_postcode = Mage::helper('klarnacheckout')->getCustomerPostcode();
		$customer_email = '';
		if (!$customer_session->isLoggedIn() && $customer_session->getKcoEmail()) $customer_email = $customer_session->getKcoEmail();
		elseif($customer_session->isLoggedIn()) $customer_email = $customer_session->getCustomer()->getEmail();
		
		
		if ($session->hasData('klarna_checkout')) {
			
			// Resume session
			try {

                // verify that the quote that quotemap points to, is still active
                $explodedKcoId = explode("/",$session->getData('klarna_checkout'));
                $existingQuoteId = Mage::getModel("klarnacheckout/quotemap")->loadFromKlarnaId(array_pop($explodedKcoId))->getQuoteId();

                if($existingQuoteId != $quote->getId()) throw new Exception("Ecom_KlarnaCheckout_Model_Service: trying to reuse klarnaOrder for incorrect Quote (".$existingQuoteId." != ".$quote->getId()." )");

                if(!$quote->getIsActive()) throw new Exception("Ecom_KlarnaCheckout_Model_Service: trying to reuse inactive Quote");

                $klarnaOrder = Mage::getModel('klarnacheckout/klarnaorder');
                $klarnaOrder->fetch($session->getData('klarna_checkout'));
				$klarnaOrder->update($cart,$customer_postcode,$customer_email);
				//Mage::log('updating order from session');
			} catch (Exception $e) {
				Mage::logException($e);
				
				unset($klarnaOrder);
				$klarnaOrder = null;
				$session->unsetData('klarna_checkout');
			}
		}
		
		if($klarnaOrder == null) {
			
			$klarnaOrder = Mage::getModel('klarnacheckout/klarnaorder');						
			$klarnaOrder->create(
					$cart, 
					Mage::getStoreConfig('general/country/default'),
					Mage::app()->getStore()->getCurrentCurrencyCode(),
					Mage::helper('klarnacheckout')->getLocaleCode(),
					$customer_email,
					$customer_postcode
				);

		}

		// Store location of checkout session
		$session->setData('klarna_checkout', $klarnaOrder->getOrderUri());

		// Store DB link between quote and klarna order
		Mage::getModel('klarnacheckout/quotemap')->add($quote->getId(), $klarnaOrder->getOrderId());
		
		return $klarnaOrder;
	}
	
	public function fetchOrder($order_uri) {
        /** @var Ecom_KlarnaCheckout_Model_Klarnaorder $klarnaOrder */
		$klarnaOrder = Mage::getModel('klarnacheckout/klarnaorder');
		$klarnaOrder->fetch($order_uri);
		
		return $klarnaOrder;
	}
	
	public function completeOrder($quote, $klarnaOrder) {
		
		// For debug - to test duplicate orders
		//$this->debugMakeSync();

		// Only finalize orders when they are complete
		if($klarnaOrder->getStatus() != 'checkout_complete')
			return $klarnaOrder;
		
		// Get customer info (name, email and billing address) from klarnaOrder
		$billingInfo = $klarnaOrder->getBillingInfo();
		
		$forceComplete = false;
		
		$reservation = $klarnaOrder->getReservation();
		
		$orderService = Mage::getModel('klarnacheckout/service_order');
		
		// Check if there is an active lock (locks only active for 25 sec)
		// if there is, wait a secund and check again, til the lock is inactive
		while(Mage::getModel('klarnacheckout/lock')->checkActive($quote->getId())){
			Mage::log('waiting on lock - quote '.$quote->getId(),null,'kco.log');
			sleep(1);
		}
		
		// check if an order exists
		if(!$orderService->orderExistsWithQuote($quote->getId())) { // if it doesnt:
			
			// place a quote-lock
			Mage::getModel('klarnacheckout/lock')->lock($quote->getId());

            // Create customer if not registered already - then assign customer to quote
            $customer = Mage::getModel('klarnacheckout/service_customer')
                ->createOrLoadCustomer($billingInfo['first_name'], $billingInfo['last_name'], $billingInfo['email']);

            // Failed to create or load customer
            if($customer == null || !$customer->getId()) {
                Mage::log('No customer could be created or found');

                $quote->setCustomerEmail($billingInfo['email']);
            } else {
                $quote->assignCustomer($customer);
            }
			
			// create the order
			$order = $orderService->createOrder($quote, $billingInfo, $reservation);
			
			// unlock the quote
			Mage::getModel('klarnacheckout/lock')->removeMylock($quote->getId());
		} else {
			// log this
			Mage::log('Quote id: ' . $quote->getId() . ' already used. Cannot turn klarna order ' . $klarnaOrder->getOrderId() . ' into Magento order',null,'kco.log');
			
			// load the existing order
			$order = $orderService->getOrderFromQuoteId($quote->getId());
			
			// make sure that it's completed @ klarna
			$forceComplete = true;
		}
		
		// Magento order created successfully - complete the Klarna order
		if( ($order != null && $order->getId()) || $forceComplete) {
			Mage::log('Completing klarna order: ' . $klarnaOrder->getOrderId() . ' with Magento order: ' . $order->getIncrementId(), null, 'kco.log');
			$klarnaOrder->complete($order->getIncrementId());
		} else {
			Mage::log('No Magento order created - could not complete klarna order ' . $klarnaOrder->getOrderId(),null,'kco.log');
			return $klarnaOrder;
		}
				
		return $klarnaOrder;
	}
	
	/*
	 * Function that can delay script execution to sync two separate requests
	 * ONLY USED FOR DEBUGGING!
	 */
	function debugMakeSync(){
		
		return; // failsafe, remove if you need this feature
		
		while(time() % 5){
			Mage::log('trying to sync '.time(),null,'kco.log');
			sleep(1);
		}
		
		$time = microtime(true);
		$wait = ((ceil($time) - $time)*1000);
		usleep($wait);
		Mage::log('waited '.$wait.' from: '.$time,null,'kco.log');
		Mage::log('synced: '.microtime(true),null,'kco.log');
	}
	
}