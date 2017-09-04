<?php

class Ecom_KlarnaCheckout_IndexController extends Mage_Core_Controller_Front_Action {

	public function getOnepage() {
		return Mage::getSingleton('checkout/type_onepage');
	}

    /**
     * @return Ecom_KlarnaCheckout_Model_Service
     */
	private function _getApiService() {
        return Mage::getModel('klarnacheckout/service');
	}

    /**
     * @return Ecom_KlarnaCheckout_Helper_Data
     */
	private function _helper() {
		return Mage::helper('klarnacheckout');
	}
	
	/***
	 * Render the checkout page
	 */
	public function indexAction() {

		if(!Mage::getStoreConfig("klarnacheckout/checkout/enabled")) {
			$this->norouteAction();
			return;
		}

        $session = Mage::getSingleton('checkout/session');


        if(Mage::getStoreConfig(Ecom_KlarnaCheckout_Helper_Data::XML_PATH_FORCE_AUTHENTICATED_CHECKOUT) && !Mage::getSingleton('customer/session')->isLoggedIn()){
            Mage::getSingleton('core/session')->addNotice($this->_helper()->__('Please login or create an account before completing your purchase'));
            $this->_redirect('customer/account/login');
            return;
        }


		$quote = Mage::getSingleton('checkout/cart')->getQuote();

		if (!$quote->hasItems() || $quote->getHasError()) {
			$this->_redirect('checkout/cart');
			return;
		}

		if (!$quote->validateMinimumAmount()) {
			$error = Mage::getStoreConfig('sales/minimum_order/error_message');
			Mage::getSingleton('checkout/session')->addError($error);
			$this->_redirect('checkout/cart');
			return;
		}

		try {

			$klarnaOrder = Mage::getModel('klarnacheckout/service')->createOrUpdateOrder($quote);
            $this->_helper()->log('index',$klarnaOrder, 'checkout shown');

		} catch (Exception $e) {
			Mage::getSingleton('checkout/session')->addError($this->_helper()->__('An error occured in communication with out paymentprovider Klarna, Please try again at a later time.'));
			Mage::log('Ecom_KlarnaCheckout_IndexController: error communicating with klarna: '.$e->getMessage());


            if(isset($klarnaOrder)) $this->_helper()->log('index-exception',$klarnaOrder, $e->getMessage());
            elseif($session->getHas('klarna_checkout')) $this->_helper()->logMinimal('index-exception',$session->getGet('klarna_checkout'), $e->getMessage());
            else $this->_helper()->logMinimal('index-exception',"EMPTY", $e->getMessage());

			$this->_redirect('checkout/cart');
			return;
		}

		$this->loadLayout();
		$this->getLayout()->getBlock('klarna.checkout')->setData('order', $klarnaOrder);
		$this->renderLayout();
	}

	public function termsAction() {
		$this->loadLayout();
		$this->renderLayout();
	}

	public function pushAction() {

		//sleep(4); // delay for a short while, so the thankyou page gets a chance to create the order
		// otherwise theres a risk that two orders are created

		try {

            $order_uri = $this->getRequest()->getParam('klarna_order');
			if ($order_uri == null || $order_uri == '') {
				$this->_helper()->logMinimal('push','EMPTY','Invalid or missing order uri sent from Klarna');
				return;
			}

            $this->_helper()->logMinimal('push',$order_uri,'push initialized');

			$service = $this->_getApiService();

			$klarnaOrder = $service->fetchOrder($order_uri);
			if (!$klarnaOrder->getOrderId() > 0) {
                $this->_helper()->log('push',$klarnaOrder,$this->__('Push action could not find an order from Klarna with URI: {0}', $order_uri));
				return;
			}

            $this->_helper()->log('push',$klarnaOrder,'order fetched');

			if ($klarnaOrder->getStatus() == 'checkout_incomplete') {
                $this->_helper()->log('push',$klarnaOrder,$this->__('Checkout not completed, operation canceled'));
				return;
			}

			$quotemap = $service->getQuoteMap($klarnaOrder->getOrderId());
			if ($quotemap == null)
				return;

			$quote = Mage::getModel('sales/quote')->load($quotemap->getQuoteId());

			// check the quote time, if it is less than one hour, ignore this push
			//if ($quote->getUpdatedAt() > date("Y-m-d H:i:s",strtotime("-1 hour"))) throw new Exception("Ignoring the first push");

			$this->_getApiService()->completeOrder($quote, $klarnaOrder);

            $this->_helper()->log('push',$klarnaOrder,'order done');

		} catch (Exception $e) {

            if(isset($klarnaOrder)) $this->_helper()->log('push-exception',$klarnaOrder, $e->getMessage());
            elseif(isset($order_uri)) $this->_helper()->logMinimal('push-exception',$order_uri, $e->getMessage());
            else $this->_helper()->logMinimal('push-exception','EMPTY', $e->getMessage());

            throw $e;
		}
	}

	/***
	 * Render the order confirmation page
	 */
	public function confirmationAction() {

		if(!Mage::getStoreConfig("klarnacheckout/checkout/enabled")) {
			$this->norouteAction();
			return;
		}
		
		$order_uri = $this->getRequest()->getParam('klarna_order');
		
		if($order_uri) {
			Mage::getSingleton('checkout/session')->setOrderUri($order_uri);

            $this->_helper()->logMinimal('confirmation',$order_uri, 'Redirected to Success');

			$this->_redirect('*/*/success',array('_secure'=>true));	
		}
			
		else
			$this->_redirect('/');
	}
	
	public function successAction() {

        if(!Mage::getStoreConfig("klarnacheckout/checkout/enabled")) {
			$this->norouteAction();
			return;
		}
		
		try {
			$order_uri = Mage::getSingleton('checkout/session')->getOrderUri();

            $this->_helper()->logMinimal('success',$order_uri,$this->__('Success-page start'));

			Mage::getSingleton('checkout/session')->setOrderUri(null);
			$service = $this->_getApiService();


			
			if(isset($order_uri)) {
				$quote = Mage::getSingleton('checkout/cart')->getQuote();
                $this->_helper()->logMinimal('success',$order_uri,$this->__('Fetching order'));

				$klarnaOrder = $service->fetchOrder($order_uri);
				if(!$klarnaOrder->getOrderId() > 0) {
					$this->_helper()->logMinimal('success',$order_uri,$this->__('KlarnaOrder not found!'));
					$this->_redirect('/');
					return;
				}
				
				if ($klarnaOrder->getStatus() == 'checkout_incomplete' ) {
					Mage::getSingleton('checkout/session')->addError($this->__('Checkout not completed, redirect to cart'));
					$this->_helper()->log('success',$klarnaOrder,'Klarnaorder not complete');
					$this->_redirect('checkout/cart');					
					return;
				}

				//$klarnaOrder = $service->completeOrder($quote, $klarnaOrder);

                // close the quote if push hasn't closed it already
                if ($quote->getId() && $quote->getIsActive()) {
                    $this->_helper()->log('success',$klarnaOrder,'Close cart');
                    /** @var Mage_Core_Model_Resource $resource */
                    $resource = Mage::getSingleton('core/resource');
                    $read = $resource->getConnection('core_read');
                    $read->update($resource->getTableName('sales/quote'), array('is_active' => 0), 'entity_id = ' . $quote->getId());
                }
								
				Mage::getSingleton('checkout/cart')->truncate();
				Mage::getSingleton('checkout/session')->clear();
				Mage::getSingleton('customer/session')->setKcoHasSetPostcode(null);
				
				$this->loadLayout();
				
				// prepare for google analytics tracking
                /*
				if($incrementId = $klarnaOrder->getMerchantReference1()){
					$lastOrderId = Mage::getModel('sales/order')->load($incrementId, 'increment_id')->getId();
					Mage::getSingleton('checkout/session')->setLastOrderId($lastOrderId);
					Mage::dispatchEvent('klarnacheckout_controller_success_action', array('order_ids' => array($lastOrderId)));
				}
                */
				
				$this->getLayout()->getBlock('klarna.success')->setData('order', $klarnaOrder);
                Mage::register("klarna_checkout_order_success",$klarnaOrder);

				$this->renderLayout();
				Mage::getSingleton('checkout/session')
                    ->unsetData('klarna_checkout')
                    ->setLastOrderId(null);

                $this->_helper()->log('success',$klarnaOrder,'Action End');
			} else {
				Mage::log('No order uri found in request');
				$this->_redirect('/');
			}
		} catch(Exception $e) {

            // if we ended up here, the customer as an active order with klarna, but we failed to create one in Magento
            //if($klarnaOrder) $klarnaOrder->cancel(); // this cancels the active order with klarna so a new one can be created

			Mage::logException($e);
			Mage::getSingleton('checkout/session')->addError($this->__($e->getMessage()));
			$this->_redirect('checkout/cart');					
			return;
			
		}
		
	}
	
	/***
	 * Handle the order validation
	 * http://developers.klarna.com/en/klarna-checkout/advanced-features/validate-a-checkout-order
	 */
	public function validationAction() {
		
		// gather info
		$service = $this->_getApiService(); // get an instance of the service
		$klarnaOrder = json_decode(file_get_contents('php://input'),true);
		$quote_id = $service->getQuoteMap($klarnaOrder['id'])->getQuoteId(); // find the matching quote in magento
		$quote = Mage::getModel('sales/quote')->load($quote_id); // load the acctual quote
		$postcode = $quote->getShippingAddress()->getPostcode();
		
		/*
		 * debug
		 
		$array = array(
			'k-id' => $klarnaOrder['id'],
			'k-grandtotal' => $klarnaOrder['cart']['total_price_including_tax'],
			'q-id' => $quote_id,
			'q-grandtotal' => ($quote->getGrandTotal()*100),
			'totals-match' => ($klarnaOrder['cart']['total_price_including_tax'] == ($quote->getGrandTotal()*100)),
			'k-postcode' => $klarnaOrder['shipping_address']['postal_code'],
			'q-postcode' => $postcode,
			'post-match' => ($klarnaOrder['shipping_address']['postal_code'] == $postcode),
		);
		Mage::log(print_r($array,true));
		 */
		
		// start the validations
		$errors = array();
		
		// check the total
		if(abs(($klarnaOrder['cart']['total_price_including_tax'] - ($quote->getGrandTotal()*100)))>1){
			$errors[] = $this->_helper()->__("Ordertotal does not match, maybe you've changed the cart in another tab? Review your order and please try again.");
		}
		
		// check the postcode
		if(Mage::getStoreConfig(Ecom_KlarnaCheckout_Helper_Data::XML_PATH_SHOW_POSTCODE) && ($klarnaOrder['shipping_address']['postal_code'] != $postcode)){
			$errors[] = $this->_helper()->__("Postcode does not match, maybe you've changed the postcode in Klarna's checkout? Please make sure that the postcodes match.");
		}
		
		// allow for external validations
		Mage::dispatchEvent('klarnacheckout_validate_order',array('klarna_order'=>&$klarnaOrder,'quote'=>&$quote,'errors'=>&$errors));
		
		// save the messages & set headers
		if(count($errors)){
			Mage::getModel('klarnacheckout/validationlog')->add($quote_id,$errors);
			
			$this->getResponse()->clearAllHeaders();
		
			$this->getResponse()
				->setHttpResponseCode(303)
				->setHeader('Location',$this->_helper()->getCheckoutUri());
		}
		else { // validation passed vithout errors
			$this->getResponse()->clearAllHeaders();
			$this->getResponse()->setHttpResponseCode(200);
		}
		
		return;
	}

}