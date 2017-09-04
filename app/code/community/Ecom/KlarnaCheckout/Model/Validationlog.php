<?php

class Ecom_KlarnaCheckout_Model_Validationlog extends Mage_Core_Model_Abstract {
		
	public function _construct() {
		parent::_construct();
		$this->_init('klarnacheckout/validationlog');
	}

	/**
	 * @param int $quote_id
	 * @param array $messages list of messages
	 * @return type boolean false if row already exists
	 */
	public function add($quote_id, array $messages) {
		if(!count($messages)) return $this;
		
		$log = $this->getCollection()
			->addFieldToFilter('quote_id', $quote_id)
			->getFirstItem();
		
		if($log->getId() > 0) {
			$model = $log;
			$messages = array_unique(array_merge($model->getMessages(), $messages));
		}
		else $model = $this;
		
		
		
		$model->setQuoteId($quote_id);
		$model->setMessages($messages);
		$model->setTime(Mage::getModel('core/date')->date());
		$model->save();
		
		return $model;
	}

	/**
	 * @param int $quote_id
	 * @return object ($this)
	 */
	public function loadByQuoteId($quote_id) {
		$this->setQuoteId($quote_id);
		
		if(count($this->getMessages())) return $this;
		
		return $this->getCollection()->addFieldToFilter('quote_id',$this->getQuoteId())->getFirstItem();
	}
	
	public function getMessages(){
		
		if(is_array($messages = unserialize($this->getData('messages')))) return $messages;
		
		return array();
	}
	
	public function setMessages(array $msg){
		$this->setData('messages',serialize($msg));
		return $this;
	}
	
	public function clearMessages(){
		foreach($this->getCollection()->addFieldToFilter('quote_id',$this->getQuoteId()) as $row){
			$row->delete();
		}
		return $this;
	}
}