<?php
class Mivec_Catalog_Product extends Mivec_Catalog_Abstract
{
	protected $_pids;
	protected $_product;
	protected $_collection;
	
	public function __construct($ids="")
	{
		if ($ids) {
			self::setProductIds($ids);
		}
		self::__prepare();
	}
	
	protected function __prepare()
	{
		$this->_product = Mage::getModel('catalog/product');
		$this->_collection = $this->_product->getCollection();
		
		if (!empty($this->_pids)) {
			$this->_collection->addAttributeToFilter('entity_id',$this->_pids);
		}
	}
	
	public function load($id) 
	{
		return intval($id) ? $this->_product->load($id) : "";
	}
	
	protected function setProductIds($ids)
	{
		//如果逗号分开则拆分
		if (strpos($ids,',') !== false) {
			$_tmp = split(',',$ids);
			$this->_pids = $_tmp;
		}
		//如果是填写范围
		elseif(strpos($ids,'-') !== false){
			$_tmp = split('-',$ids);
			$this->_pids = numberToArray($_tmp[0],$_tmp[1]);
		}
		elseif(intval($ids)) {
			$this->_pids = array($ids);
		}
	}
	
	public function getData()
	{
		return $this->_product->getData();
	}

	public function getCollection()
	{
		return $this->_collection;
	}
}