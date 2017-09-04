<?php
class Mivec_Checkout_Cart extends Mivec_Checkout_Abstract
//Mage_Core_Block_Template
{
	protected $_session;
	
	public function __construct()
	{
		parent::init();
		self::startSession();
	}

	protected function startSession()
	{
		//declare session
		$this->_session = Mage::getSingleton('core/session', array('name' => 'frontend'));
	}
	
	//自定义选项
	protected function checkProductOption(Mage_Catalog_Model_Product $product)
	{
		//print_r($product->getCustomOptions());
		//return $product->canAffectOptions();
	}
	
	//可配置的产品 all associated children products data
	protected function checkConfigurable(Mage_Catalog_Model_Product $product)
	{
		if ($configurable = Mage::getModel('catalog/product_type_configurable')) {
			$childProducts = $configurable->getUsedProducts(null,$product);
			return $childProducts;
		}
	}
	
	protected function checkParentProduct(Mage_Catalog_Model_Product $product)
	{
		if ($parent = $product->loadParentProductIds()->getData()) {
			$parentIds = $parent['parent_product_ids'];
			return $parentIds;
		}
		return false;
	}
	
	public function addToCart(Mage_Catalog_Model_Product $product,$additional = array())
	{
		//print_r($product->getName());exit;
		self::startSession();
		//判断如果不是可配置的产品就直接添加到购物车,否则返回父产品的地址
		if ($parent = self::checkParentProduct($product)) {
			$parent_id = $parent[0];
			$product = Mage::getModel('catalog/product')->load($parent_id);
			return $product->getProductUrl();
			//header('Location:' . $product->getProductUrl());
		}else{
			if ($this->_cart->addProduct($product,$additional)) {
				$this->_session->setLastAddedProductId($product->getId());
				$this->_session->setCartWasUpdated(true);
				
				if ($this->_cart->save()) {
					//Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
					return $product->getName();
				}
			}
		}
		//return $this->helper('checkout/cart')->getAddUrl($product);
	}
	
	public function getCartItems()
	{
		//return $this->_cart->getItemsCount();
	}
}