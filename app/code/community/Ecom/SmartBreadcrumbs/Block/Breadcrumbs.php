<?php

class Ecom_SmartBreadcrumbs_Block_Breadcrumbs extends Mage_Catalog_Block_Breadcrumbs {
	
	var $_cat = null;
	var $_needed = null;
	var $_prd = null;
	
	/**
	 * This checks if the breadcrumbs really need to be smart
	 * Note: they only need to be smart on product-view, when accessing a product with it's root address
	 * and when the customer hasn't been in any previous category
	 * 
	 * @return bool
	 */
	public function checkIfNeeded(){
		
		if($this->_needed !== null) return $this->_needed;
		
		$result = false;
		if(Mage::app()->getRequest()->getControllerName() == 'product' && Mage::app()->getRequest()->getActionName() == 'view'){ // correct controller_action
			if(!Mage::registry("current_category")) $result = true;
		}
		
		return $this->_needed = $result;
	}
		
	public function getProduct(){
		if($this->_prd) return $this->_prd;
		elseif($prd = Mage::registry("current_product")) return $prd;
		else return new Varien_Object();
	}
	
	private function _getCategoryFromProduct(){
		
		if($this->_cat) return $this->_cat;
		
		$cat = $this->getProduct()->getCategory();
		
		/**
		 * If no category is assigned, find it
		 * This happends if the product is viewed with its root-address,
		 * and the user hasn't been to any category before
		 */
		if($cat == null) {
			return $this->_cat = Mage::getModel("catalog/category")->load(end(($this->getProduct()->getCategoryIds())));
		}
		else return $this->_cat = $cat;
	}
	
	
	/**
     * Finds the correct category for this product, and set the registry-value
	 * After that the default breadcrumb logic can kick in.
     *
     * @return Mage_Catalog_Block_Breadcrumbs
     */
    protected function _prepareLayout()
    {
		if(!$this->checkIfNeeded()) return parent::_prepareLayout();
		
		$cat = $this->_getCategoryFromProduct();
		if(!Mage::registry("current_category") && $cat->getId()) Mage::register('current_category',$cat);
        return parent::_prepareLayout();
    }
}
