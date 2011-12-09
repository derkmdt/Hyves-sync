<?php

class MediaMonks_Service_Hyves_Response
{
	protected $_raw = null;
	protected $_body = null;
	protected $_info = null;
	protected $_method = null;
	
	protected $_format = null;
	
	protected $_parsed = false;
	protected $_paginated = false;
	
	public function __construct($format, $data, $method = null)
	{
		$this->_format = $format;
		$this->_raw = $data;
		
		if(null !== $method) {
			// convert to real Hyves method
			$method = str_replace('_result', '', $method);
			$method = str_replace('_', '.', $method);
			$this->_method = $method;
		}
	}
	
	public function parse()
	{
		// data can be already parsed by response batch
		if(is_string($this->_raw)) {
			$response = self::toArray($this->_format, $this->_raw);
		}
		else {
			$response = $this->_raw;
		}

		if(!empty($response['error_code'])) {
			throw new MediaMonks_Service_Hyves_Response_Exception($response['error_message'], $response['error_code']);
		}
		
		if(null == $this->_method && !empty($response['method'])) {
			$this->_method = $response['method'];
			unset($response['method']);
		}
		
		$this->_info = $response['info'];
		unset($response['info']);
		
		$this->_body = $response;
		
		// pagination
		if(!empty($this->_info['totalresults'])) {
			$this->_paginated = true;
		}
		
		$this->_parsed = true;
		return $this;
	}
	
	public static function toArray($format, $data)
	{
		switch($format) {
			case 'json': {
				$parsed = json_decode($data, true);
				break;
			}
			case 'xml': {
				$parsed = new SimpleXMLElement($data);
				$parsed = self::xml2array($parsed);
				break;
			}
			default: {
				throw new MediaMonks_Service_Hyves_Response_Exception('Invalid parse method "' . $format . '"');	
			}
		}

		return $parsed;
	}
	
	// code borrowed from http://php.net/manual/en/book.simplexml.php (Ashok dot 893 at gmail dot com)
	private static function xml2array($arrObjData, $arrSkipIndices = array())
	{
		$arrData = array();
	
		// if input is object, convert into array
		if (is_object($arrObjData)) {
			$arrObjData = get_object_vars($arrObjData);
		}
		
		if(empty($arrObjData))
		{
			return '';
		}
		
		if (is_array($arrObjData)) {
			foreach ($arrObjData as $index => $value) {
				if (is_object($value) || is_array($value)) {
					$value = self::xml2array($value, $arrSkipIndices); // recursive call
				}
				if (in_array($index, $arrSkipIndices)) {
					continue;
				}
				$arrData[$index] = $value;
			}
		}
		
		return $arrData;
	}
	
	public function isPaginated()
	{
		if(false == $this->_parsed) {
			$this->parse();
		}
		return $this->_paginated;
	}

	public function getPagination()
	{
		if(false == $this->_parsed) {
			$this->parse();
		}
		return array(
			'totalresults' => $this->_info['totalresults'],
			'totalpages' => $this->_info['totalpages'],
			'resultsperpage' => $this->_info['resultsperpage'],
			'currentpage' => $this->_info['currentpage']
		);
	}

	public function getResultsPerPage()
	{
		if($this->isPaginated() == true) {
			return $this->_info['resultsperpage'];
		}
		return null;
	}

	public function getCurrentPage()
	{
		if($this->isPaginated() == true) {
			return $this->_info['currentpage'];
		}
		return null;
	}

	public function getTotalPages()
	{
		if($this->isPaginated() == true) {
			return $this->_info['totalpages'];
		}
		return null;
	}

	public function getTotalResults()
	{
		if($this->isPaginated() == true) {
			return $this->_info['totalresults'];
		}
		return null;
	}

	public function countResults($name)
	{
		if(false == $this->_parsed) {
			$this->parse();
		}
		if(!empty($this->_body[$name])) {
			return count($this->_body[$name]);
		}
		return 0;
	}
	
	public function getMethod()
	{
		if(false == $this->_parsed) {
			$this->parse();
		}
		return $this->_method;
	}
	
	public function getBody()
	{
		if(false == $this->_parsed) {
			$this->parse();
		}
		return $this->_body;
	}
	
	public function getRaw()
	{
		return $this->_raw;
	}
	
	public function getInfo()
	{
		if(false == $this->_parsed) {
			$this->parse();
		}
		return $this->_info;
	}

	public function getCombined($key, $fields, $separators)
	{
		if(false == $this->_parsed) {
			$this->parse();
		}
		
		$return = array();
		$fieldCount = count($fields);
		
		foreach($this->_body[$key] as $itemNr => $item) {
			$line = '';
			for($i = 0; $i <= $fieldCount; $i++) {
				$field = $fields[$i];
				if(is_array($field)) {
					$tmp = $this->$key($itemNr, $field[0]);
					$field = array_slice($field, 1);
					foreach($field as $fieldKey) {
						$tmp = $tmp[$fieldKey];
						if(!is_array($tmp)) {
							break;
						}
					}
					$line .= $tmp;
				}
				else {
					$line .= $this->$key($itemNr, $field);	
				}
				$line .= $separators[$i];
			}
			$return[] = $line;
		}
		
		return $return;
	}

	public function __get($name)
	{
		if(false == $this->_parsed) {
			$this->parse();
		}
		if(!empty($this->_body[$name])) {
			return $this->_body[$name];
		}
		return $this->getFirst($this->_body, $name);
	}
	
	public function getFirst($data, $name)
	{
		foreach($data as $k => $v) {
			
			if(!is_array($v)) {
				continue;
			}
			
			if(!empty($v[$name])) {
				return $v[$name];
			}
			
			return $this->getFirst($v, $name);
		}
	}
	
	public function __call($name, $arguments)
	{
		if(false == $this->_parsed) {
			$this->parse();
		}
		
		$data = $this->_body[$name];
		if(!is_int($arguments[0]) && $this->countResults($name) == 1) {
			$data = $this->_body[$name][0];
		}
		
		if(count($arguments) == 1) {
			// single item
			if(!empty($data[$arguments[0]])) {
				return $data[$arguments[0]];
			}
			return null;
		}
		else {
			if(!empty($data)) {
				$value = $data;
			}
			else {
				$value = $this->_body;
			}
			
			foreach($arguments as $key) {
				if(!empty($value[$key]) && is_array($value)) {
					$value = $value[$key];
					if(!is_array($value)) {
						return $value;
					}
				}
				else {
					return null;
				}
			}
			return $value;
		}
	}
}