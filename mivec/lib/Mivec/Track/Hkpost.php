<?php
class Mivec_Track_Hkpost extends Mivec_Track_Abstract
{
	protected $_client;
	
	protected $_name = 'hk post';
	protected $_trackNumber;
	
	protected $_queryUrl = 'http://app3.hongkongpost.com/CGI/mt/genresult.jsp';
	protected $_field = 'tracknbr'; //表单的文本框
	protected $_response;
	
	public function __construct($trackNumber)
	{
		$this->_tracknumber = $trackNumber;
		
		self::init();
		self::request();
	}
	
	protected function init()
	{
		parent::init();
		
		Zend_Loader::loadClass('Zend_Http_Client');
		$this->_client = new Zend_Http_Client();
		$this->_client->setMethod(Zend_Http_Client::POST);
		$this->_client->setUri($this->_queryUrl);
		$this->_client->setConfig(array(
			'timeout'	=> 30
		));
		$this->_client->setParameterPost($this->_field,$this->_tracknumber);
		//$this->_client->request();
	}
	
	protected function request()
	{
		$this->_response = $this->_client->request();
		return $this;
	}
	
	public function getResponse()
	{
		return $this->_response;
	}
	
	public function getResult()
	{
		$result = '';
		if ($content = $this->getResponse()->getBody()) {
			$pattern->container = '/<div id="clfContent" class="ieFix">(.*?)<\/div>/is';
			preg_match($pattern->container,$content,$_cc);
			$_c = $_cc[0];
			
			$pattern->content = '/<p><span class="textNormalBlack">.*?<br \/><br \/>/is';
			if (preg_match($pattern->content,$_c,$_dd)) {
				$result = $_dd[0];
			}else{
				$result = 'not found';
			}
			return $result;
		}
	}
}