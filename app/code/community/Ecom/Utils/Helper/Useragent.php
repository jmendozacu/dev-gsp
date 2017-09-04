<?php
class Ecom_Utils_Helper_Useragent extends Ecom_Utils_Helper_Data {
	
	const TYPE_MOBILE = 'mobile';
	const TYPE_TABLET = 'tablet';
	const TYPE_DESKTOP = 'desktop';
	
	/**
	 * Find out which mode is active
	 * @return string Any of the self::TYPE_ constants
	 */
	function getType(){
		// get a session
		$session = Mage::getSingleton('core/session');
		
		// see if we allready have a type in there
		if($found = $session->getData('utils_useragent',false)) return $found; // return if we do
		
		// find the type
		$detect = new Mobile_Detect;
		$detected = ($detect->isMobile() ? ($detect->isTablet() ? self::TYPE_TABLET : self::TYPE_MOBILE) : self::TYPE_DESKTOP);
		
		// store in session, and return
		$session->setData('utils_useragent', $detected);
		return $detected;
	}
	
	/**
	 * Is this a mobile (phone)
	 * @return boolean
	 */
	function isMobile(){
		if($this->getType() == self::TYPE_MOBILE) return true;
		else return false;
	}
	
	/**
	 * Is this a tablet
	 * @return boolean
	 */
	function isTablet(){
		if($this->getType() == self::TYPE_TABLET) return true;
		else return false;
	}
	
	/**
	 * Is this a computer
	 * @return boolean
	 */
	function isDesktop(){
		if($this->getType() == self::TYPE_DESKTOP) return true;
		else return false;
	}
}