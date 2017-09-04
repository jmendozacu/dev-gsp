<?php

class Ecom_KlarnaCheckout_Block_Checkout extends Mage_Core_Block_Template {
	
	public function getOrderHtml() {
		if(Mage::app()->getRequest()->getActionName() != 'success' && !Mage::helper('klarnacheckout')->stepOneComplete()) return '';
		
		$order = $this->getOrder();
		
		if($order)
			return $order->getSnippet();
		else
			return 'No order to render';
	}
	
}