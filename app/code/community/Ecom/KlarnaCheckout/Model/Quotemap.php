<?php

class Ecom_KlarnaCheckout_Model_Quotemap extends Mage_Core_Model_Abstract {
		
	public function _construct() {
		parent::_construct();
		$this->_init('klarnacheckout/quotemap');
	}
	
	/**
	 * 
	 * @param type $quote_id
	 * @param type $order_id KCO order reference ID
	 * @return type boolean false if row already exists
	 */
	public function add($quote_id, $order_id) {
		$quotemap = $this->getCollection()
			->addFieldToFilter('quote_id', $quote_id)
			->addFieldToFilter('order_id', $order_id)
			->getFirstItem();
		
		if($quotemap->getId() > 0)
			return false;
		
		$this->setQuoteId($quote_id);
		$this->setOrderId($order_id);
		$this->save();
		
		return true;
	}

    /**
     * @param $order_id
     *
     * @return Ecom_KlarnaCheckout_Model_Quotemap
     */
    public function loadFromKlarnaId($order_id) {
        return $this->getCollection()
            ->addFieldToFilter('order_id', $order_id)
            ->getFirstItem();
    }

}