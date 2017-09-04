<?php

class Ecom_KlarnaCheckout_Block_Checkout_Cart extends Mage_Checkout_Block_Cart
{
    public function getCheckoutUrl()
    {
		if(Mage::helper('core')->isModuleEnabled('Idev_OneStepCheckout')) {
			if ($this->helper('onestepcheckout')->isRewriteCheckoutLinksEnabled()) return $this->getUrl('onestepcheckout', array('_secure'=>true));
		}
		
        if (!$this->helper('klarnacheckout')->isRewriteCheckoutLinksEnabled()){
            return parent::getCheckoutUrl();
        }
        return $this->getUrl('klarnacheckout', array('_secure'=>true));
    }

}
