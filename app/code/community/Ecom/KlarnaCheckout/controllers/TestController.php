<?php

class Ecom_KlarnaCheckout_TestController extends Mage_Core_Controller_Front_Action {

	const BASE_URI_LIVE = 'https://checkout.klarna.com/checkout/orders';
	const BASE_URI_TEST = 'https://checkout.testdrive.klarna.com/checkout/orders';
	
	const XML_SERVER_TYPE = 'klarnacheckout/account/server';
	
	public function indexAction() {
		
		$cart = array();
		
		$cart[] = array(
				'reference' => '$item->getSku()',
				'name' => '$item->getName()',
				'quantity' => '$item->getQty()',
				'unit_price' => '$item->getBasePriceInclTax() * 100',
				'tax_rate' => '$item->getTaxPercent() * 100'
			);
		
		echo count($cart) . "<br>";
		echo (!$cart) ? 'nothing in cart' : 'stuff in cart';
	}
	
	public function clearAction() {
		Mage::getSingleton('checkout/cart')->truncate();
		Mage::getSingleton('checkout/session')->clear();
		
		echo 'cart and checkout session cleared';
	}	
		
}