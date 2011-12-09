<?php

require_once 'MediaMonks/Service/Hyves/Authorization/Storage/Interface.php';

abstract class MediaMonks_Service_Hyves_Authorization_Storage_Abstract implements MediaMonks_Service_Hyves_Authorization_Storage_Interface
{
	public function setName($name)
	{
		if(false == ctype_alnum($name)) {
			throw new MediaMonks_Service_Hyves_Authorization_Storage_Exception();
		}
		
		$this->_name = $name;
	}
	
	public function saveTokens($requestTokens)
	{
	}
	
	public function tokensPresent()
	{
	}
	
	public function getTokens()
	{	
	}
	
	public function destroyTokens()
	{
	}
}