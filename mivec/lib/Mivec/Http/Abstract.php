<?php
abstract class Mivec_Http_Abstract extends Mivec_Abstract
{
	protected $_adapter;
	
	protected function init()
	{
		$this->_adapter = new Zend_Http_Client();
	}
	
	protected function request($url)
	{
		self::init();
		
		$request = $this->_adapter->setUri($url);
		$request->setConfig(array(
			'timeout'	=> 30
		));
		
		return $request;
	}
	
	protected function post($_url , array $_postData)
	{
		self::init();
		$request = $this->_adapter->setUri($url);
		$this->_adapter->setMethod(Zend_Http_Client::POST);
		$this->_adapter->setParameterPost($_postData);
		
		$response = $this->_adapter->request();
		print_r($response);exit;	
	}
}