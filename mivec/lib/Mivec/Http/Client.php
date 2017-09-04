<?php
class Mivec_Http_Client extends Mivec_Http_Abstract
{
	protected $_request;
	protected $_reponse;
	protected $_url;
	
	public function __construct()
	{
		
	}
	
	public function request($url)
	{
		$this->_url = $url;
		try {
			$_request = parent::request($this->_url);
			$this->_response = $_request->request();
			return $this;
		} catch (Exception $e) {
			echo $e->getCode() . "\r\n" . $e->getMessage();
		}
	}
	
	public function getResponse()
	{
		if ($this->_response) {
			return $this->_response;
		}
	}
	
	public function saveToImage($file)
	{
		ob_start();
		@readfile($this->_url);
		$img = ob_get_contents();
		ob_end_clean();
		
		if ($fp2 = @fopen($file, "wb")) {
			fwrite($fp2,$img);
			fclose($fp2);
			return $file;
		}
	}
	
	public function saveToFile($file)
	{
		return file_put_contents($file,$this->_response->getBody());
	}
	
	public function toString()
	{
		return !empty($this->_url) ? $this->_url : "";
	}
}