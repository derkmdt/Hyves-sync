<?php

require_once 'MediaMonks/Service/Hyves/Authorization/Storage/Abstract.php';

abstract class MediaMonks_Service_Hyves_Authorization_Storage_Cookie_Abstract extends MediaMonks_Service_Hyves_Authorization_Storage_Abstract
{
	protected $_name = null;
	protected $_expireTime = 60;
	protected $_path = null;
	protected $_domain = null;
	protected $_secure = false;
	protected $_httpOnly = false;
	
	public function __construct($options = array())
	{
		if(!empty($options['expire_time'])) {
			$this->setExpireTime($options['expire_time']);
		}
		if(!empty($options['name'])) {
			$this->setName($options['name']);
		}
		if(!empty($options['path'])) {
			$this->setPath($options['path']);
		}
		if(!empty($options['domain'])) {
			$this->setDomain($options['domain']);
		}
		if(!empty($options['secure'])) {
			$this->setSecure($options['secure']);
		}
		if(!empty($options['httponly'])) {
			$this->setHttpOnly($options['httponly']);
		}
	}
	
	private function setExpireTime($expireTime)
	{
		if(is_int($expireTime) && $expireTime > 0) {
			$this->_expireTime = $expireTime;
		}
	}
	
	private function setPath($path)
	{
		$this->_path = $path;
	}
	
	private function setDomain($domain)
	{
		$this->_domain = $domain;
	}
	
	private function setSecure($secure)
	{
		$this->_secure = $secure;
	}
	
	private function setHttpOnly($httpOnly)
	{
		$this->_httpOnly = $httpOnly;
	}
	
	public function saveTokens($requestTokens)
	{
		$this->writeCookie(serialize($requestTokens), time() + $this->_expireTime);
	}
	
	public function tokensPresent()
	{
		if(!empty($_COOKIE[$this->_name])) {
			return true;
		}
		return false;
	}
	
	public function getTokens()
	{
		if(true == $this->tokensPresent()) {
			$data = unserialize(urldecode($_COOKIE[$this->_name]));
			return array(
				'oauth_token' => $data['oauth_token'],
				'oauth_token_secret' => $data['oauth_token_secret']
			);
		}
		return false;
	}
	
	public function destroyTokens()
	{
		$this->writeCookie('', (time() - (60 * 60 * 48)));
	}
	
	private function writeCookie($data, $expireTime)
	{
		if($this->_httpOnly !== false) {
			setcookie($this->_name, urlencode($data), $expireTime, $this->_path, $this->_domain, $this->_secure, $this->_httpOnly);
		}
		elseif($this->_secure !== false) {
			setcookie($this->_name, urlencode($data), $expireTime, $this->_path, $this->_domain, $this->_secure);
		}
		elseif($this->_domain !== null) {
			setcookie($this->_name, urlencode($data), $expireTime, $this->_path, $this->_domain);
		}
		elseif($this->_path !== null) {
			setcookie($this->_name, urlencode($data), $expireTime, $this->_path);
		}
		else {
			setcookie($this->_name, urlencode($data), $expireTime);
		}
	}
}