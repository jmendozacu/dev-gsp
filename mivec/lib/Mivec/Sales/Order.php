<?php
class Mivec_Sales_Order extends Mivec_Sales_Abstract
{
	protected $_collection;
	
	public function __construct() 
	{
		self::__prepare();
	}
	
	protected function __prepare()
	{
		parent::getOrder();
		
		if ($this->_order) {
			$this->_collection = $this->_order->getCollection();
		}
	}
	
	public function loadByIncrementId($_incrementId)
	{
		parent::setIncrementId($_incrementId);
		if ($this->_incrementId) {
			foreach ($this->_incrementId as $incrementId) {
				$arr[] = $this->_order->loadByIncrementId($incrementId);
			}
			return $arr;
		}
	}
	
	public function load($id)
	{
		return intval($id) ? $this->_order->load($id) : "";
	}
	
	public function getCollection()
	{
		return $this->_collection;
	}
	
	public function getData()
	{
		return $this->_order->getData();
	}
}