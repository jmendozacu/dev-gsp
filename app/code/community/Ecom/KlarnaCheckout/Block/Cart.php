<?php

class Ecom_KlarnaCheckout_Block_Cart extends Mage_Core_Block_Template {
	
	protected $_totals = null;
	
	private $_shippingPriceCache = array();
	
	private $_messages = array();
	
	
	
	/*
	 * get the shippingprices
	 * in local currency, as numbers.
	 */
	private function _collectShippingInfo(){
		if(!empty($this->_shippingPriceCache)) return $this->_shippingPriceCache;
		$return = Mage::getSingleton("klarnacheckout/cart")->_collectShippingInfo();
		$this->_shippingPriceCache = $return;
		
		return $return;	
	}
	
	/*
	 * Get what to acctually print for shipping
	 * will return NULL if no price is found
	 */
	function getShippingPrice(){
		$data = $this->_collectShippingInfo();
		if(Mage::getStoreConfig("tax/cart_display/shipping") == '1') return Mage::helper('checkout')->formatPrice($data->getShippingExclTax());
		else return Mage::helper('checkout')->formatPrice($data->getShippingInclTax());
	}
	
	public function getCartHtml() {
		$quote = Mage::getSingleton('checkout/cart')->getQuote();
		
		$html = '';
		
		foreach($quote->getAllVisibleItems() as $item) {
			$html = $item->getSku() . ' <strong>' . $item->getName() . '</strong> qty:' . $item->getQty() . ' unit_price:' . $item->getPrice() . '<br>';
		}
		
		return $html;
	}
	
	public function getQuote() {
		return Mage::getSingleton('checkout/cart')->getQuote();
	}
	
	public function getTaxAmount() {
		$data = $this->_collectShippingInfo();
		return Mage::helper('checkout')->formatPrice($data->getTotalTax());
	}
	
	public function getDiscount() {
		$data = $this->_collectShippingInfo();
		$discount = $data->getDiscountAmount() ? Mage::helper('checkout')->formatPrice($data->getDiscountAmount()) : 0;
		return $discount;
	}
	
	public function getCouponCode() {
		$data = $this->_collectShippingInfo();
		return $data->getCouponCode();
	}
	
	public function getSubtotal() {
		$data = $this->_collectShippingInfo();
		return Mage::helper('checkout')->formatPrice($data->getSubtotalInclTax());
	}
	
	public function getGrandTotal() {
		return Mage::helper('checkout')->formatPrice($this->getQuote()->getGrandTotal());
	}
	
	public function getItemsHtml() {
		
		$html = "";
		$i = 0;
		foreach($this->getQuote()->getAllVisibleItems() as $item) {

            if($item->getProduct()->getTypeId() == 'configurable') $blockType = "checkout/cart_item_renderer_configurable";
            else  $blockType = "checkout/cart_item_renderer";

			$html .= $this->getLayout()->createBlock($blockType)
						->setTemplate('klarnacheckout/cart/item.phtml')
						->setItem($item)
						->setRowNumber($i++)
						->_toHtml();
		}
		
		return $html;
	}
	
	public function getItems()
    {
        return $this->getQuote()->getAllVisibleItems();
    }
	
	public function getTotals() {
		return $this->getTotalsCache();
	}
	
	public function getTotalsCache()
    {
        if (empty($this->_totals)) {
            $this->_totals = $this->getQuote()->getTotals();
        }
        return $this->_totals;
    }
	
	public function stepOneComplete(){
		return Mage::helper('klarnacheckout')->stepOneComplete();
	}
	
	public function getMessages(){
		if(count($this->_messages)) return $this->_messages;
		
		$quote_id = $this->getQuote()->getId();
		
		return $this->_messages = Mage::getModel('klarnacheckout/validationlog')->loadByQuoteId($quote_id)->clearMessages()->getMessages();
		
	}
}
