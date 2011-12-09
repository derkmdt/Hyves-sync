<?php

require_once 'MediaMonks/Service/Hyves/Authorization/Storage/Session/Abstract.php';

class MediaMonks_Service_Hyves_Authorization_Storage_Session extends MediaMonks_Service_Hyves_Authorization_Storage_Session_Abstract
{
	protected $_name = 'hyves_request_token';
}