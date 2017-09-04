<?php
class Evalent_IwdOpc_Block_Checkout_Onepage_Link extends IWD_Opc_Block_Onepage_Link {
	
    public function getCheckoutUrl(){
    	return Mage::helper('checkout/url')->getOPCheckoutUrl();
    }
}
