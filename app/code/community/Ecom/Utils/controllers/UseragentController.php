<?php

class Ecom_Utils_UseragentController extends Mage_Core_Controller_Front_Action {

	/**
	 * Set a specific mode (and redirect to referer)
	 * @return \Ecom_Utils_UseragentController
	 */
	public function setmodeAction() {
		
		// check if new (valid) type is set:
		if($mode = $this->getRequest()->getParam('mode')){
			switch($mode){
				case Ecom_Utils_Helper_Useragent::TYPE_MOBILE:
				case Ecom_Utils_Helper_Useragent::TYPE_TABLET:
				case Ecom_Utils_Helper_Useragent::TYPE_DESKTOP:
					// get a session
					$session = Mage::getSingleton('core/session');

					// store in session
					$session->setData('utils_useragent', $mode);
				break;
			}
		}

		// send back to previous page
		$refererUrl = $this->_getRefererUrl();
		$this->getResponse()->setRedirect($refererUrl);
		return $this;
	}
	
	/**
	 * unset mode, so its figured out again
	 * @return \Ecom_Utils_UseragentController
	 */
	public function resetAction() {
		
		// get a session
		$session = Mage::getSingleton('core/session');

		// store in session
		$session->setData('utils_useragent', null);

		// send back to previous page
		$refererUrl = $this->_getRefererUrl();
		$this->getResponse()->setRedirect($refererUrl);
		return $this;
	}
	
	public function testAction() {
		header("Content-type:text/plain");
		echo 'Type:		'.Mage::helper("utils/useragent")->getType()."\n";
		echo 'isMobile:	'.(Mage::helper("utils/useragent")->isMobile()?'true':'false')."\n";
		echo 'isTablet:	'.(Mage::helper("utils/useragent")->isTablet()?'true':'false')."\n";
		echo 'isDesktop:	'.(Mage::helper("utils/useragent")->isDesktop()?'true':'false')."\n";
		exit();
	}
}
