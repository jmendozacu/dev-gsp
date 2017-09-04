<?php
class Ecom_UltraCart_Helper_Data extends Mage_Core_Helper_Abstract {
	
	/*
	 * return true if the extended info should be shown
	 * for configurable products
	 */
	function showConfigurableOptions(){
		return true; // @TODO: should be from config
	}
	
	/*
	 * selects if the products price should be for row total or for one piece
	 */
	function showPricePerPiece(){
		return false; // @TODO: should be from config
	}
	
	/*
	 * trunkates names, and if truncated it adds a span with correct title
	 * makes nicer names
	 */
	function processProductTitle($title){
		
		$truncateTo = 30; // @TODO: should be from config, 0 = dont truncate
		$suffix = '...'; // @TODO: should be from config
		
		if($truncateTo == 0) return $title;
		
		$return = '';
		if(mb_strlen($title,'UTF-8') > $truncateTo) $return = trim(mb_substr($title,0, $truncateTo,'UTF-8')).$suffix;
		else return $title;
		
		return '<span class="truncated" title="'.$title.'">'.$return.'</span>';
		
	}
	
	public function getShippingMethodCode() {
		return Mage::getStoreConfig("ultracart/general/default_shipping_method");
	}

	public function getAjaxUrl() {

		$url = Mage::getUrl('ultracart/ajax',array("_secure"=>Mage::app()->getStore()->isCurrentlySecure()));
		if(strstr($url,"?")){
			$parts = explode("?",$url);
			$url = $parts[0];
		}
		return $url;
	}
}
