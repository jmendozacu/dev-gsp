<?php
class Mivec_Acl extends Mivec_Abstract {
	const COPR = '';
	protected $_db;
	protected $_table;
	
	protected $_user;
	protected $_role;
	protected $_userRole;
	
	protected $_errorMsg = "Access Denied";
	
	public function __construct($_user , $_pass)
	{
		self::_initDb();
		self::_initUser($_user , $_pass);
	}
	
	/*
	* 页面设置权限 setPermission
	* 1 = admin
	* 2 = customer service
	* 3 = supplier
	*/
	public function setPermission($_role)
	{
		$this->_role = $_role;
		return $this;
	}
	
	public function isAllowed()
	{
		if ($this->_userRole > $this->_role) {
			die($this->_errorMsg);
		}
		return $this;
	}
	
	public function getUserInfo()
	{
		return array(
			'user'	=> $this->_user,
			'role'	=> $this->_userRole
		);
	}
	
	public function _validate()
	{
		header('WWW-Authenticate: Basic realm="'.self::COPR.'"');
		header('HTTP/1.0 401 Unauthorized');
		die($this->_errorMsg);
	}
	
	public function destroy(){
		unset($this->_user);
		$this->_initUser();
	}
	
	private function _initUser($_user , $_pass)
	{
		$sql = "SELECT * FROM " . $this->_table['user'] . " WHERE user='$_user' AND pass='$_pass'";
		$rs = $this->_db->fetchRow($sql);
		if (!empty($rs['user'])) {
			$this->_user = $_user;
			$this->_userRole = $rs['role'];
		} else{
			self::_validate();
		}
	}
	
	private function _initDb()
	{
		$this->_table = array(
			'user'	=> 'mivec_erp_user',
			'role'	=> 'mivec_erp_user_role'
		);
		
		$this->_db = Mage::getSingleton('core/resource')->getConnection('core_read');
	}
}