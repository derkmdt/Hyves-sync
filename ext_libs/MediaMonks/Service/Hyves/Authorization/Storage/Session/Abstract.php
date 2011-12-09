<?php

require_once 'MediaMonks/Service/Hyves/Authorization/Storage/Abstract.php';

abstract class MediaMonks_Service_Hyves_Authorization_Storage_Session_Abstract extends MediaMonks_Service_Hyves_Authorization_Storage_Abstract
{
	public function __construct($options = array())
	{
		@session_start();
	}
	
	public function saveTokens($requestTokens)
	{
		$_SESSION[$this->_name] = urlencode(serialize($requestTokens));
	}
	
	public function tokensPresent()
	{
		if(!empty($_SESSION[$this->_name])) {
			return true;
		}
		return false;
	}
	
	public function getTokens()
	{
		if(true == $this->tokensPresent()) {
			$data = unserialize(urldecode($_SESSION[$this->_name]));
			return array(
				'oauth_token' => $data['oauth_token'],
				'oauth_token_secret' => $data['oauth_token_secret']
			);
		}
		return false;
	}
	
	public function destroyTokens()
	{
		unset($_SESSION[$this->_name]);
	}
}