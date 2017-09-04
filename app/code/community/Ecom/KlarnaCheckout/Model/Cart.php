<?php

class Ecom_KlarnaCheckout_Model_Cart extends Mage_Core_Model_Abstract {
	
	private $_shippingPriceCache = array();
		
    public function getCheckout()
    {
        if (null === $this->_checkout) {
            $this->_checkout = Mage::getSingleton('checkout/session');
        }
        return $this->_checkout;
    }
    
    public function getQuote()
    {
        if (null === $this->_quote) {
            $this->_quote = $this->getCheckout()->getQuote();
        }
        return $this->_quote;
    }
	
	public function getShippingMethodCode() {
		return Mage::getStoreConfig(Ecom_KlarnaCheckout_Helper_Data::XML_PATH_SHIPPING_METHOD);
	}
	
	/*
	 * get the shippingprices
	 * in local currency, as numbers.
	 */
	public function _collectShippingInfo(){
		if(!empty($this->_shippingPriceCache)) return $this->_shippingPriceCache;
		
		$quote = $this->getQuote();
		
		Mage::helper('klarnacheckout/shipping')->_collectShippingInfo($quote);
		
		$return = new Varien_Object();
		//$return->setShippingRateFound($rateFound); // if an acctual shippingrate is found		
		$return->setGrandTotalInclTax($quote->getShippingAddress()->getGrandTotal()); // order total, incl tax (i think)
		$return->setTotalTax($quote->getShippingAddress()->getTaxAmount()); // total moms
		$return->setSubtotalInclTax($quote->getShippingAddress()->getSubtotalInclTax());
		$return->setShippingInclTax($quote->getShippingAddress()->getShippingInclTax()); // frakt inc moms
		$return->setShippingExclTax($quote->getShippingAddress()->getShippingAmount()); // frakt ex moms
		$return->setPackageWeight($quote->getShippingAddress()->getWeight()); // orderns vikt
		$return->setDiscountAmount(Mage::helper('klarnacheckout/discount')->getDiscount($quote));
		$return->setCouponCode($this->getQuote()->getCouponCode());
		
		$this->_shippingPriceCache = $return;
		
		return $return;
		
	}
	
}