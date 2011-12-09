<?php

interface MediaMonks_Service_Hyves_Authorization_Storage_Interface
{
	public function setName($name);
	
	public function saveTokens($requestTokens);
	
	public function tokensPresent();
	
	public function getTokens();
	
	public function destroyTokens();
}