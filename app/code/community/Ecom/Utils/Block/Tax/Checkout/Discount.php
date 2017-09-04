<?php

class Ecom_Utils_Block_Tax_Checkout_Discount extends Mage_Tax_Block_Checkout_Discount
{
    //protected $_template = 'tax/checkout/subtotal.phtml';
	
	public function getTotal(){
		$total = parent::getTotal();
		$total->setValue(Mage::helper('utils/discount')->getDiscountData($this->getQuote())->getDiscountInclTax());
		return $total;
	}
}
