<?php

require_once 'MediaMonks/Service/Hyves/Authorization/Storage/Cookie/Abstract.php';

class MediaMonks_Service_Hyves_Authorization_Storage_Cookie extends MediaMonks_Service_Hyves_Authorization_Storage_Cookie_Abstract
{
	protected $_name = 'hyves_request_token';
	protected $_expireTime = 60;
	protected $_path = null;
	protected $_domain = null;
	protected $_secure = false;
	protected $_httpOnly = true;
}