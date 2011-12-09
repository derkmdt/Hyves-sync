<?php

require_once 'MediaMonks/Service/Hyves/Response/Upload/Exception.php';

class MediaMonks_Service_Hyves_Response_Upload
{
	protected $_token = null;
	
	protected $_raw = null;
	protected $_body = null;
	
	protected $_parsed = false;
	protected $_items = array();
	protected $_currentTime = 0;
	
	public function __construct($response, $token)
	{
		$this->_raw = $response;
		$this->_token = $token;
	}
	
	public function parse()
	{
		$this->_body = json_decode($this->_raw, true);
		$this->_parsed = true;
	}
	
	private function parseCheck($token = null)
	{
		if(false == $this->_parsed) {
			$this->parse();
		}
		
		if(null == $token) {
			return;	
		}
		
		if(!empty($this->_items[$token])) {
			return;
		}
		
		if(empty($this->_items[$token])) {
			if(empty($this->_body['data'][$token][0])) {
				throw new MediaMonks_Service_Hyves_Response_Upload_Exception('No data for token found');
			}
			$this->_items[$token] = $this->_body['data'][$token][0];
		}
		
		if(!empty($this->_items[$token]['error']['errornumber'])) {
			throw new MediaMonks_Service_Hyves_Response_Upload_Exception(
				$this->_items[$token]['error']['errormessage'], $this->_items[$token]['error']['errornumber']);
		}
	}
	
	public function getBody()
	{
		$this->parseCheck();
		return $this->_body;
	}
	
	public function getCurrentState($token)
	{
		$this->parseCheck($token);
		return $this->_items[$token]['currentstate'];	
	}
	
	public function isDone($token)
	{
		if($this->getCurrentState($token) == 'done') {
			return true;
		}
		return false;
	}
	
	public function getMediaId($token)
	{
		$this->parseCheck($token);
		return $this->_items[$token]['done']['mediaid']; // null or mediaid
	}
	
	// not reliable at all
	public function getExpectedEndTime($token)
	{
		$this->parseCheck($token);
		if(!empty($this->_items[$token]['rendering']['expected_endtime'])) {
			return $this->_items[$token]['rendering']['expected_endtime'];
		}
		return false;
	}
	
	// not reliable at all
	public function getSecondsTillExpectedEndTime($token)
	{
		$expectedEndTime = $this->getExpectedEndTime($token);
		if(false == $expectedEndTime) {
			return false;
		}
		$currentTime = $this->getCurrentTime();
		return ((float)$expectedEndTime - (float)$currentTime);
	}
	
	public function getCurrentTime()
	{
		$this->parseCheck();
		return $this->_body['currenttime'];
	}
}