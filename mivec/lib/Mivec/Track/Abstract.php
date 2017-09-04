<?php
abstract class Mivec_Track_Abstract extends Mivec_Abstract
{
	protected $_provider;
	
	protected function init()
	{
		self::setCarrier();
	}
	
	protected function setCarrier()
	{
		$this->_provider = array('hkpost','dhl','ups','ems');
	}
}