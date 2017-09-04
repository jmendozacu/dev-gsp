<?php

/**
 * Model used to prevent duplicate orders
 * When a quote-contertion is started, a lock is placed on that quotes ID
 * This moden handles thouse locks (sets, checks, removes)
 */
class Ecom_KlarnaCheckout_Model_Lock extends Mage_Core_Model_Abstract {
		
	public function _construct() {
		parent::_construct();
		$this->_init('klarnacheckout/lock');
	}

	/**
	 * Check if a lock is active for $quote_id
	 * @param int $quote_id
	 * @return boolean
	 */
	public function checkActive($quote_id){
		$collection = Mage::getModel('klarnacheckout/lock')->getCollection()
				  ->addFieldToFilter('quote_id',$quote_id)
				  ->addFieldToFilter('time',array('gt'=>date('Y-m-d H:i:s',strtotime('-50 seconds'))));
		
		return $collection->count() ? true : false;
	}
	
	/**
	 * Set a lock, if none are active
	 * @param type $quote_id
	 * @return \Ecom_KlarnaCheckout_Model_Lock
	 */
	public function lock($quote_id){
		if($this->checkActive($quote_id)) return $this;
		
		$lock = Mage::getModel('klarnacheckout/lock')->setQuoteId($quote_id)->setTime(date('Y-m-d H:i:s'))->save();
		Mage::register('kco_i_made_a_lock',true);
		
		return $this;
	}
	
	/**
	 * Remove all locks, if this request has set a lock
	 * @param type $quote_id
	 * @return \Ecom_KlarnaCheckout_Model_Lock
	 */
	public function removeMyLock($quote_id){
		if(!Mage::registry('kco_i_made_a_lock')) return $this;
		
		$this->removeAllLocks($quote_id);
		
		return $this;
	}
	
	/**
	 * Remove all locks for $quote_id
	 * @param type $quote_id
	 * @return \Ecom_KlarnaCheckout_Model_Lock
	 */
	public function removeAllLocks($quote_id){
		$collection = Mage::getModel('klarnacheckout/lock')->getCollection()
				  ->addFieldToFilter('quote_id',$quote_id);
		
		foreach($collection as $row) $row->delete();
		
		return $this;
	}
}