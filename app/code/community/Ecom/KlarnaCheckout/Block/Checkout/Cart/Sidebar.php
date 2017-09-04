<?php
class Ecom_KlarnaCheckout_Block_Checkout_Cart_Sidebar extends Mage_Checkout_Block_Cart_Sidebar
{
    public function getCheckoutUrl()
    {
        // If Customer is logged in & Redirect is enabled in the configuration & SweetTooth is enabled
        if($this->helper('klarnacheckout')->useSweetToothCartRedirect()){
            if($url = $this->getUrl('checkout/cart', array('_secure'=>true))){
                return $url;
            }
        }

		if(Mage::helper('core')->isModuleEnabled('Idev_OneStepCheckout')) {
			if ($this->helper('onestepcheckout')->isRewriteCheckoutLinksEnabled()) return $this->getUrl('onestepcheckout', array('_secure'=>true));
		}
		
        if (!$this->helper('klarnacheckout')->isRewriteCheckoutLinksEnabled()){
            return parent::getCheckoutUrl();
        }
        return $this->getUrl('klarnacheckout', array('_secure'=>true));
    }


}
