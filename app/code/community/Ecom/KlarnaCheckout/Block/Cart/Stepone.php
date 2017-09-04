<?php

class Ecom_KlarnaCheckout_Block_Cart_Stepone extends Ecom_KlarnaCheckout_Block_Cart {
	public function getPostcode(){
		return Mage::helper('klarnacheckout')->getCustomerPostcode();
	}
	
	public function getEmail(){
		return Mage::getSingleton('customer/session')->getKcoEmail();
	}
	
	public function isLoggedIn(){
		$customer_session = Mage::getSingleton('customer/session');			
		if($customer_session->isLoggedIn()) return true;
		else return false;
	}
}
