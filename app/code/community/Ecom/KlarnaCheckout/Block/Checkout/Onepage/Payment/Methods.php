<?php

class Ecom_KlarnaCheckout_Block_Checkout_Onepage_Payment_Methods extends Mage_Checkout_Block_Onepage_Payment_Methods
{
	/* delete the klarnapayment-method from default checkout */
   public function getMethods(){
		$methods = parent::getMethods();
		
		foreach($methods as $key => $method){
			if($method->getCode() == 'klarnapayment') unset($methods[$key]);
		}
		
		return $methods;
	}
}
