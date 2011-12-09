<?php

class MediaMonks_Service_Hyves_Request extends MediaMonks_Service_Hyves
{
	protected $_requestMethod = 'post';
	
	protected $_format = 'json';
	protected $_method = '';
	protected $_consumer = null;
	protected $_token = null;
	protected $_url = '';

	protected $_signatureMethod = '';
	
	protected $_params = array();
	protected $_responsefields = array();
	protected $_returnfields = array();
	protected $_pagination = array();
	protected $_vars = array();
	
	protected $_isBatch = null;
	
	public function __construct($hyves, $method, $options = array())
	{
		$this->_consumer = $hyves->_consumer;
		$this->_token = $hyves->_token;
		
		if(true == $hyves->_secureConnection) {
			$this->_url = MediaMonks_Service_Hyves::URL_API_SECURE;
		}
		else {
			$this->_url = MediaMonks_Service_Hyves::URL_API;
		}
		
		// copy settings to request object because they can be different per call
		$this->_method = $method;
		$this->_format = $hyves->getFormat();
		$this->_version = $hyves->getVersion();
		$this->_fancylayout = $hyves->getFancyLayout();
		$this->_signatureMethod = $hyves->getSignatureMethod();
		
		// manditory params
		$this->addVar('ha_method', $method);
		$this->addVar('ha_version', $this->_version);
		$this->addVar('ha_format', $this->_format);
		$this->addVar('ha_fancylayout', $this->getFancyLayoutAsString());

		// method parameters
		if(!empty($options['params'])) {
			$this->_params = $options['params'];
			$this->addVars($options['params']);
		}
		
		// responsefields
		if(!empty($options['responsefields'])) {
			$this->_responsefields = $options['responsefields'];
			
			$responseFields = array();
			foreach($options['responsefields'] as $responseField) {
				$responseFields[] = $responseField;
			}
			if(!empty($responseFields)) {
				$this->addVar('ha_responsefields', implode(',', $responseFields));
			}
		}
		
		// returnfields
		if(!empty($options['returnfields'])) {
			$this->_returnfields = $options['returnfields'];
			
			$responseFields = array();
			foreach($options['returnfields'] as $returnField) {
				$responseFields[] = $returnField;
			}
			if(!empty($responseFields)) {
				$this->addVar('ha_returnfields', implode(',', $responseFields));
			}
		}
		
		// pagination
		if(!empty($options['pagination'])) {
			if(!empty($options['pagination']['page'])) {
				$this->addVar('ha_page', $options['pagination']['page']);
			}
			if(!empty($options['pagination']['perpage'])) {
				$this->addVar('ha_resultsperpage', $options['pagination']['perpage']);
			}
			if(!empty($options['pagination']['resultsperpage'])) {
				$this->addVar('ha_resultsperpage', $options['pagination']['resultsperpage']);
			}
		}
		
		// forced language
		if(!empty($hyves->_forcedLanguage)) {
			$this->addVar('ha_language', $hyves->_forcedLanguage);
		}
	}
	
	private function addVar($key, $value)
	{
		$this->_vars[$key] = $value;
	}
	
	private function addVars($vars)
	{
		foreach($vars as $key => $value) {
			$this->_vars[$key] = $value;
		}
	}
	
	public function toUrl()
	{
		$OAuthRequest = $this->getOAuthRequest($this->_requestMethod, $this->_vars);
		return $OAuthRequest->to_url();
	}
	
	public function toPostData()
	{
		$OAuthRequest = $this->getOAuthRequest($this->_requestMethod, $this->_vars);
		return $OAuthRequest->to_postdata();
	}
	
	public function toAuthorizationHeader()
	{
		$OAuthRequest = $this->getOAuthRequest($this->_requestMethod, $this->_vars);
		return $OAuthRequest->to_header();
	}
	
	public function toParams()
	{
		return $this->getPostDataWithoutOAuthParams($this->toPostData());
	}
	
	private function getOAuthRequest($requestMethod, $params)
	{
		$OAuthRequest = OAuthRequest::from_consumer_and_token(
			$this->_consumer,
			$this->_token,
			$requestMethod,
			$this->_url,
			$params
		);

		switch($this->_signatureMethod) {
			case 'HMAC-SHA1': {
				$signatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
				break;
			}
			case 'PLAINTEXT': {
				$signatureMethod = new OAuthSignatureMethod_PLAINTEXT();
				break;
			}
		}
		$OAuthRequest->sign_request($signatureMethod, $this->_consumer, $this->_token);
		return $OAuthRequest;
	}
	
	private function getPostDataWithoutOAuthParams($postData)
	{
		$pairs = explode('&', $postData);
		
		$out = array();
	    foreach($pairs as $k => $v) {
	    	if (substr($v, 0, 5) != 'oauth') {
				$out[] = $v;
			}
	    }
		
		return implode('&', $out);
	}
}