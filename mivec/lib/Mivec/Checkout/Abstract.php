<?php
abstract class Mivec_Checkout_Abstract extends Mivec_Abstract
{
	protected $_cart;
	
	protected function init()
	{
		$this->_cart = Mage::getModel('checkout/cart');
	}
}