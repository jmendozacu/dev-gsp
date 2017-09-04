<?php

class Ecom_UltraCart_Block_Cart extends Mage_Checkout_Block_Cart_Sidebar {
	
	private $_shippingPriceCache = array();
	
	function showGrandTotal(){
		return (bool) Mage::getStoreConfig("ultracart/general/show_grand_total");
	}

	function showSubTotal(){
		return (bool) Mage::getStoreConfig("ultracart/general/show_sub_total");
	}
	
	function showShipping(){
		if(!$this->_collectShippingInfo()->getShippingExclTax()) return false; // if no price is found, don't show it
		
		return (bool) Mage::getStoreConfig("ultracart/general/show_shipping");
	}
	
	function showShippingWeight(){
		if(!$this->getPackageWeight()) return false; // if no weight is found, don't show it
		
		return (bool) Mage::getStoreConfig("ultracart/general/show_shipping_weight");
	}
	
	function showTotalTax(){
		return (bool) Mage::getStoreConfig("ultracart/general/show_total_tax");
	}
	
	function getShippingMethodCode(){
		return Mage::getStoreConfig("ultracart/general/default_shipping_method");
	}
	
	/*
	 * get the shippingprices
	 * in local currency, as numbers.
	 */
	private function _collectShippingInfo(){
		if(!empty($this->_shippingPriceCache)) return $this->_shippingPriceCache;
		$return = Mage::getSingleton("ultracart/cart")->_collectShippingInfo();
		$this->_shippingPriceCache = $return;
		
		return $return;
		
	}
	
	/*
	 * Get what to acctually print for shipping
	 * will return NULL if no price is found
	 */
	function getShippingPrice(){
		$data = $this->_collectShippingInfo();
		if(Mage::getModel("tax/config")->displayCartShippingExclTax()) return $data->getShippingExclTax();
		else return $data->getShippingInclTax();
	}

	/*
	 * Get what to acctually print for grand total
	 */
	function getGrandTotal(){
		$data = $this->_collectShippingInfo();
		return $data->getGrandTotalInclTax();
	}

	/*
	 * Get what to acctually print for total tax
	 */
	function getTotalTax(){
		$data = $this->_collectShippingInfo();
		return $data->getTotalTax();
	}
	
	/*
	 * Get weight
	 */
	function getPackageWeight(){
		$data = $this->_collectShippingInfo();
		return $data->getPackageWeight();
	}
	
	/*
	 * Compatibility with OneStepCheckout & Klarnacheckout
	 */
	public function getCheckoutUrl() {
		if(Mage::helper('core')->isModuleEnabled('Idev_OneStepCheckout')) {
			if ($this->helper('onestepcheckout')->isRewriteCheckoutLinksEnabled()) return $this->getUrl('onestepcheckout', array('_secure'=>true));
		}
		
		if(Mage::helper('core')->isModuleEnabled('Ecom_KlarnaCheckout')) {
			if ($this->helper('klarnacheckout')->isRewriteCheckoutLinksEnabled()) return $this->getUrl('klarnacheckout', array('_secure'=>true));
		}
		return parent::getCheckoutUrl();
    }
	
}
