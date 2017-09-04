<?php
abstract class Mivec_Sales_Abstract extends Mivec_Abstract
{
	protected $_orderId;
	protected $_incrementId;
	protected $_order;
	
	
	protected function getOrder()
	{
		$this->_order = Mage::getModel('sales/order');
		return $this->_order;
	}
	
	protected function setIncrementId($incrementId)
	{
		$_tmp = '';
		//if the incrementId is string of consequent
		if (strpos($incrementId,',') !== false) {
			$_tmp = split(',',$incrementId);
			$this->_incrementId = $_tmp;
		} elseif (strpos($incrementId,'-') !== false) { //if the numbers is between x-y
			$_tmp = split('-',$incrementId);
			$this->_incrementId = numberToArray($_tmp[0],$_tmp[1]);
		} elseif (intval($incrementId)) { // type of int
			$this->_incrementId = array($incrementId);
		}
	}
	
	protected function setOrderId($_orderId)
	{
		$_tmp = '';
		//if the incrementId is string of consequent
		if (strpos($_orderId,',') !== false) {
			$_tmp = split(',',$_orderId);
			$this->_orderId = $_tmp;
		} elseif (strpos($_orderId,'-') !== false) { //if the numbers is between x-y
			$_tmp = split('-',$_orderId);
			$this->_orderId = numberToArray($_tmp[0],$_tmp[1]);
		} elseif (intval($_orderId)) { // type of int
			$this->_orderId = array($_orderId);
		}
	}
	
	
	/**
	 * return payment provider
	 */
	protected function getPayment($order)
	{
		return $this->getPayment()->getMethod();
	}
}