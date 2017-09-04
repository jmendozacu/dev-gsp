<?php

class Ecom_KlarnaCheckout_Model_Observer {

    /**
     * @return Ecom_KlarnaCheckout_Helper_Data
     */
    private function _helper() {
        return Mage::helper('klarnacheckout');
    }

	/**
	 * Event name: checkout_cart_save_after
	 * @param type $observer
	 */
	public function updateKlarnaOrder($observer) {
		if(!Mage::getStoreConfig(Ecom_KlarnaCheckout_Helper_Data::XML_PATH_CARTOBSERVER)) return;

        $session = Mage::getSingleton('checkout/session');
        if(!$session->hasData('klarna_checkout')) return;
		
		try {
			$quote = $observer->getEvent()->getCart()->getQuote();
			$klarnaOrder = Mage::getModel('klarnacheckout/service')->createOrUpdateOrder($quote);
            if(is_object($klarnaOrder)) $this->_helper()->log('cart-observer',$klarnaOrder, 'Sucessfully updated Klarna');
		} catch (Exception $e){
			$this->_helper()->logMinimal('cart-observer-exception',$session->getData('klarna_checkout'), 'error communicating with klarna: '.$e->getMessage());
		}
	}
	
}