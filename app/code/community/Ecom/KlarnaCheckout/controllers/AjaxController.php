<?php

class Ecom_KlarnaCheckout_AjaxController extends Mage_Core_Controller_Front_Action {

    const XML_SENDER_NAME = 'trans_email/ident_general/name';
    const XML_SENDER_EMAIL = 'trans_email/ident_general/email';
    const XML_HELP_EMAIL_RECIPIENTS = 'klarnacheckout/checkout/helpform_email_recipients';

	public function updatecheckoutAction() {

        // the help-form is handled separatly
        if($this->getRequest()->getPost('help_form') == 'true'){
            $this->_forward("sendHelpForm");
            return;
        }

		$itemId = $this->getRequest()->getPost('item_id');
		$newQty = $this->getRequest()->getPost('item_qty');
		$couponCode = $this->getRequest()->getPost('coupon_code');
		$postCode = $this->getRequest()->getPost('postcode');
		$email = $this->getRequest()->getPost('email');
		$shippingMethod = $this->getRequest()->getPost('shipping_method');
		$resetShippingMethod = false;
        $session = Mage::getSingleton('checkout/session');
        $klarnaUri = $session->hasData('klarna_checkout') ? $session->getData('klarna_checkout') : "EMPTY";
		
		$response = array();

		$cart = Mage::getSingleton('checkout/cart');
		$quote = $cart->getQuote();
		
		// Change item QTY
		if($itemId != 'NULL' && $newQty != 'NULL'){
			# what to set
			$cartData = $cart->suggestItemsQty(array($itemId => array("qty" => $newQty)));

			# check stock
			$orgQty = $quote->getItemById($itemId)->getQty();

			$cart->updateItems($cartData)->save();


			# find out how it went
			foreach ($quote->getAllItems() as $item)
				Mage::dispatchEvent('sales_quote_item_qty_set_after', array('item' => $item));

			$messages = $quote->getMessages();
			if (isset($messages['qty']) && $messages['qty']->getType() == 'error') {
				# it went wrong
				$msg = $messages['qty']->getCode();

				#reset qty
				$cartData = $cart->suggestItemsQty(array($itemId => array("qty" => $orgQty)));
				$cart->updateItems($cartData)->save();
				foreach ($quote->getAllItems() as $item)
					Mage::dispatchEvent('sales_quote_item_qty_set_after', array('item' => $item));
			}
		}
		
		// Add email
		$customer_session = Mage::getSingleton('customer/session');			
		if(!$customer_session->isLoggedIn()){
			if($email && $email != 'NULL') {
				$customer_session->setKcoEmail($email);
                $this->_helper()->logMinimal('ajax-update',$klarnaUri, 'Add email: '.$email);
			}
		}

        // Add postcode
		if($postCode && $postCode != 'NULL') {
			$quote->getShippingAddress()->setPostcode($postCode);
			$quote->save();
			$customer_session->setKcoHasSetPostcode(true);
			$resetShippingMethod = true;
            $this->_helper()->logMinimal('ajax-update',$klarnaUri, 'Add postcode: '.$postCode);
		}

		// Add or remove coupon code
		if($couponCode != 'NULL') {
			$couponResponse = $this->addOrRemoveCoupon($couponCode);
			$response['success'] = $couponResponse['success'];
			$response['message'] = $couponResponse['message'];
            $this->_helper()->logMinimal('ajax-update',$klarnaUri, 'Add/Remove coupon: '.print_r($couponResponse,true));
		}
                
                
        // change shipping method
		if($shippingMethod != 'NULL') {
            $result = Mage::getSingleton('checkout/type_onepage')->saveShippingMethod($shippingMethod);

            $this->_helper()->logMinimal('ajax-update',$klarnaUri, 'Set shippingmethod: '.$shippingMethod);
		}
		
		// reset shipping method
		if($resetShippingMethod) {
			Mage::helper('klarnacheckout/shipping')->_collectShippingInfo($quote);
            $this->_helper()->logMinimal('ajax-update',$klarnaUri, 'CollectShippingInfo');
		}
                
		// All done - prepare for content update
		$this->loadLayout(false);
		
		// test for any general errors
		if (!$quote->hasItems() || $quote->getHasError()) {
			$response['redirect'] = Mage::getUrl('checkout/cart');
            $this->_helper()->logMinimal('ajax-update',$klarnaUri, 'External error Redirect: '.$response["redirect"]);
		}
		
		// check minimum amount in cart
		if (!$quote->validateMinimumAmount()) {
			$error = Mage::getStoreConfig('sales/minimum_order/error_message');
			Mage::getSingleton('checkout/session')->addError($error);
			$response['redirect'] = Mage::getUrl('checkout/cart');
            $this->_helper()->logMinimal('ajax-update',$klarnaUri, 'Minimum error: '.$error." redirect: ".$response['redirect']);
		}
		
		// save the quote
		$cart->save();
		
		// work with klarna
		try {
			$klarnaOrder = Mage::getModel('klarnacheckout/service')->createOrUpdateOrder($quote);
            $this->_helper()->log('ajax-update',$klarnaOrder, 'Updated klarna');
		} catch (Exception $e) {
			Mage::getSingleton('checkout/session')->addError($this->__('An error occured in communication with out paymentprovider Klarna, Please try again at a later time.'));

            if(isset($klarnaOrder)) $this->_helper()->log('ajax-update-exception',$klarnaOrder, 'error communicating with klarna: '.$e->getMessage());
            else $this->_helper()->logMinimal('ajax-update-exception',$klarnaUri, 'error communicating with klarna: '.$e->getMessage());

            $response['redirect'] = Mage::getUrl('checkout/cart');
		}
		
		// set klarna order to block
		$this->getLayout()->getBlock('klarna.ajax.checkout')->setData('order', $klarnaOrder);
		
		// Make response
		$block = $this->getLayout()->getBlock('klarna.ajax.placeholder');
		$response['summary'] = $block->getChildHtml('klarna.ajax.summary');
        $response['coupon'] = $block->getChildHtml('klarna.ajax.coupon');
		$response['snippet'] = $block->getChildHtml('klarna.ajax.checkout');
		if (isset($msg) && !empty($msg)) $response['msg'] = $msg;
		$this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody(Mage::helper('core')->jsonEncode($response));

        if(isset($klarnaOrder)) $this->_helper()->log('ajax-update',$klarnaOrder, 'Update finished');
        else $this->_helper()->logMinimal('ajax-update',$klarnaUri, 'Update finished (No $klarnaOrder?)');
	}
	
	public function addOrRemoveCoupon($couponCode) {
		$quote = Mage::getSingleton('checkout/cart')->getQuote();

		// Make response
		$response = array(
			'success' => false,
			'message' => false,
		);
						
		try {
			$quote->getShippingAddress()->setCollectShippingRates(true);
            $quote->setCouponCode(strlen($couponCode) ? $couponCode : '')
				->collectTotals()
				->save();

            if ($couponCode) {
                if ($couponCode == $quote->getCouponCode()) {
                    $response['success'] = true;
                    $response['message'] = $this->__('Coupon code "%s" was applied successfully.', Mage::helper('core')->htmlEscape($couponCode));
                }
                else {
                    $response['success'] = false;
                    $response['message'] = $this->__('Coupon code "%s" is not valid.', Mage::helper('core')->htmlEscape($couponCode));
                }
            } else {
                $response['success'] = true;
                $response['message'] = $this->__('Coupon code was canceled successfully.');
            }

        }
        catch (Mage_Core_Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = $this->__('Can not apply coupon code.');
        }
		
		return $response;
	}


    /**
     * Send the help-form*/
    public function sendHelpFormAction() {

        # Get the list of e-email recipients
        $recievers = Mage::getStoreConfig(self::XML_HELP_EMAIL_RECIPIENTS);


        if($recievers == '' || $recievers == null)
            return;

        # Set the template to be used
        $emailTemplate = Mage::getModel('core/email_template')->loadDefault('klarnacheckout_helprequest');

        # Pass the site-object to email template
        $emailTemplateVariables = array(
            'help_email' => $this->getRequest()->getPost('help_email'),
            "help_phone"=>$this->getRequest()->getPost('help_phone'),
            "help_message"=>$this->getRequest()->getPost('help_message'),
            "time"=>date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time())),
            "ip"=> Mage::helper('core/http')->getRemoteAddr()
        );

        # Dunno - is this being used at all ?
        $processedTemplate = $emailTemplate->getProcessedTemplate($emailTemplateVariables);

        # Set the sender and subject of this e-mail
        $emailTemplate->setSenderName(Mage::getStoreConfig(self::XML_SENDER_NAME));
        $emailTemplate->setSenderEmail(Mage::getStoreConfig(self::XML_SENDER_EMAIL));
        $emailTemplate->setTemplateSubject(Mage::helper("klarnacheckout")->__('KlarnaCheckout Help Request'));
        #print_r($processedTemplate);die;

        $recievers=explode(',',$recievers);
        foreach($recievers as $key => $mail) $recievers[$key] = trim($mail);

        return $emailTemplate->send($recievers, $recievers, $emailTemplateVariables);

    }

    /**
     * @return Ecom_KlarnaCheckout_Helper_Data
     */
    private function _helper() {
        return Mage::helper('klarnacheckout');
    }

}