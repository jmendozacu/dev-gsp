<?php
class Evalent_IwdOpc_Helper_Url extends Mage_Checkout_Helper_Url{

    /**
     * Retrieve checkout url
     *
     * @return string
     */
    public function getCheckoutUrl(){

        // if klarna checkout is enabled, use that
        if(Mage::helper('core')->isModuleEnabled('Ecom_KlarnaCheckout')) {
            if (Mage::helper('klarnacheckout')->isRewriteCheckoutLinksEnabled()){
                return $this->_getUrl('klarnacheckout', array('_secure'=>true));
            }
        }

    	if (Mage::helper('opc')->isEnable()){
        	return $this->_getUrl('onepage', array('_secure'=>true));
    	}else{
    		return parent::getCheckoutUrl();
    	}
    }

  


    /**
     * One Page (OP) checkout urls
     */
    public function getOPCheckoutUrl(){
    	return $this->getCheckoutUrl();
    }
}
