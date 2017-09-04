<?php

class Ecom_SmartBreadcrumbs_Block_Html_Breadcrumbs extends Mage_Page_Block_Html_Breadcrumbs
{
	
	var $_cat = null;
	var $_prd = null;
	
	/**
	 * http://stackoverflow.com/a/16406119
	 * 
	 * this prevents a very specific scenario in regards to Full Page Cache
	 * If you go through a anchor category to a product first (and teh product isn't assigned to the category)
	 * After that you go to the product again from a different session, now there won't be any breadcrumbs at all
	 * 
	 */
    protected function _toHtml()
    {
		if(Mage::getEdition() != Mage::EDITION_ENTERPRISE) return parent::_toHtml(); // this block is only needed on EE
		
        if (!is_array($this->_crumbs)) {
			
			$cat = $this->_getCategoryFromProduct();
			if(!Mage::registry("current_category") && $cat->getId()) Mage::register('current_category',$cat);
			else return parent::_toHtml();
        
			
            $this->addCrumb('home', array(
                'label'=>Mage::helper('catalog')->__('Home'),
                'title'=>Mage::helper('catalog')->__('Go to Home Page'),
                'link'=>Mage::getBaseUrl()
            ));

            $path  = Mage::helper('catalog')->getBreadcrumbPath();

            foreach ($path as $name => $breadcrumb) {
                $this->addCrumb($name, $breadcrumb);
            }
        }
        return parent::_toHtml();
    }
	
	/**************************************************************************************
	 * Everything below this point is copied from Ecom_SmartBreadcrumbs_Block_Breadcrumbs *
	 *************************************************************************************/
	
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
}
