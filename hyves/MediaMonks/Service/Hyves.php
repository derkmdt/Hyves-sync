<?php

/**
 * MediaMonks_Service_Hyves
 *
 * Another library for the Hyves API
 *
 * Currently supported features:
 * - Responsefields
 * - Returnfields
 * - Multi calls
 * - Batch calls
 * - Combination of multi batch calls
 * - XML and JSON parsing
 * - Supports all API versions
 * - Forced language
 * - Fancy layout
 * - Secure communication over https
 * - Easy file uploads
 * - Easy authorization with multiple backends
 * - HMAC-SHA1 & PLAINTEXT signature methods
 *
 * @package MediaMonks_Service
 * @author Robert Slootjes <robert@mediamonks.com>
 * @version 1.1
 *
 * Changelog
 *	version 1.1
 *		- added PLAINTEXT signature method
 *		- added https support for all calls
 *		- added method to pass curl options
 *		- set 2.1 as default api version
 *			methods: http://www.hyves-developers.nl/version/2.1/all
 *		- set json as default format
 *		- removed ssl certificate (can be set now using curl options)
 */
require_once 'MediaMonks/Service/OAuth.php';
require_once 'MediaMonks/Service/Hyves/Exception.php';
require_once 'MediaMonks/Service/Hyves/Request.php';
require_once 'MediaMonks/Service/Hyves/Request/Exception.php';
require_once 'MediaMonks/Service/Hyves/Response.php';
require_once 'MediaMonks/Service/Hyves/Response/Exception.php';
require_once 'MediaMonks/Service/Hyves/Response/Batch.php';
require_once 'MediaMonks/Service/Hyves/Response/Batch/Exception.php';
require_once 'MediaMonks/Service/Hyves/Response/Batch/Exception.php';

class MediaMonks_Service_Hyves
{
	const URL_API = 'http://data.hyves-api.nl/';
	const URL_API_SECURE = 'https://data.hyves-api.nl/';

	const URL_AUTHORIZE = 'http://www.hyves.nl/api/authorize/';
	const URL_AUTHORIZE_MOBILE = 'http://www.hyves.nl/mobile/api/authorize/';

	const URL_AUTHORIZE_SECURE = 'https://secure.hyves.nl/api/authorize/';
	const URL_AUTHOTIZE_SECURE_MOBILE = 'https://secure.hyves.nl/mobile/api/authorize/';

	protected $_consumer = null;
	protected $_token = null;

	// default settings
	protected $_version = '2.1';
	protected $_format = 'json';
	protected $_fancylayout = false;
	protected $_forcedLanguage = false;
	
	// security options
	protected $_secureConnection = false;
	protected $_signatureMethod = 'HMAC-SHA1';

	// call options
	protected $_autoExecute = true;
	protected $_callStack = array();
	protected $_batch = false;
	protected $_batchStack = array();
	protected $_curlOptions = array();

	/**
	 * __construct
	 *
	 * @param string $consumerKey consumer key as provided by Hyves
	 * @param string $consumerSecret consumer secret as provided by Hyves
	 * @param array $options
	 * @return MediaMonks_Service_Hyves
	 */
	public function __construct($consumerKey, $consumerSecret, $options = array())
	{
		// consumer can't be empty
		if (empty($consumerKey)) {
			throw new MediaMonks_Service_Hyves_Exception('Consumerkey not specified');
		}

		// set consumer
		$this->setConsumer($consumerKey, $consumerSecret);

		// parse options
		if (!empty($options['version'])) {
			$this->setVersion($options['version']);
		}
		if (!empty($options['format'])) {
			$this->setFormat($options['format']);
		}
		if (isset($options['fancylayout'])) {
			$this->setFancyLayout($options['fancylayout']);
		}
		if (!empty($options['secureConnection'])) {
			$this->setSecureConnection($options['secureConnection']);
		}
		if(!empty($options['signatureMethod'])) {
			$this->setSignatureMethod($options['signatureMethod']);
		}
		if(!empty($options['curlOptions'])) {
			$this->setCurlOptions($options['curlOptions']);
		}

		// set token is available, otherwise set empty token
		$this->_token = new StdClass();
		if (!empty($options['token'])) {
			$this->setToken($options['token']['key'], $options['token']['secret']);
		} else {
			$this->setToken(null, null);
		}

		return $this;
	}

	/**
	 *  setVersion
	 *
	 * @param string|float|integer $version
	 * @return MediaMonks_Service_Hyves
	 */
	public function setVersion($version)
	{
		$this->_version = (string)$version;
		return $this;
	}

	/**
	 * getVersion
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return $this->_version;
	}

	/**
	 * setFormat
	 *
	 * @param string $format
	 * @return MediaMonks_Service_Hyves
	 */
	public function setFormat($format)
	{
		if($format !== 'xml' && $format !== 'json') {
			throw new MediaMonks_Service_Hyves_Exception('format "' . $format . '" unsupported');
		}

		$this->_format = $format;
		return $this;
	}

	/**
	 * getFormat
	 * 
	 * @return string
	 */
	public function getFormat()
	{
		return $this->_format;
	}

	/**
	 * setFancyLayout
	 *
	 * @param boolean $fancyLayout
	 * @return MediaMonks_Service_Hyves
	 */
	public function setFancyLayout($fancyLayout)
	{
		if(!is_bool($fancyLayout)) {
			throw new MediaMonks_Service_Hyves_Exception('fancylayout should be a boolean');
		}
		$this->_fancylayout = $fancyLayout;
		return $this;
	}

	/**
	 * getFancyLayout
	 * @return string
	 */
	public function getFancyLayout()
	{
		return $this->_fancylayout;
	}

	/**
	 * setSecureConnection
	 * 
	 * @param boolean $secureConnection
	 * @return MediaMonks_Service_Hyves
	 */
	public function setSecureConnection($secureConnection)
	{
		$this->_secureConnection = $secureConnection;
		return $this;
	}
	
	/**
	 * getSecureConnection
	 * 
	 * @return  boolean
	 */
	public function getSecureConnection()
	{
		return $this->_secureConnection;
	}

	/**
	 * setSignatureMethod
	 * 
	 * currently supported:
	 * - HMAC-SHA1
	 * - PLAINTEXT (only over HTTPS!)
	 *
	 * @param string $signatureMethod
	 * @return MediaMonks_Service_Hyves
	 */
	public function setSignatureMethod($signatureMethod)
	{
		if(!in_array($signatureMethod, array('HMAC-SHA1', 'PLAINTEXT'))) {
			throw new MediaMonks_Service_Hyves_Exception(
				'Unsupported signature method "' . $signatureMethod . '"');
		}
		$this->_signatureMethod = $signatureMethod;
		return $this;
	}

	/**
	 * getSignatureMethod
	 *
	 * @return string
	 */
	public function getSignatureMethod()
	{
		return $this->_signatureMethod;
	}

	/**
	 * setCurlOptions
	 * @param array $curlOptions 
	 */
	public function setCurlOptions(array $curlOptions)
	{
		$this->_curlOptions = $curlOptions;
		return $this;
	}

	/**
	 * getFancyLayoutAsString
	 *
	 * @return string
	 */
	public function getFancyLayoutAsString()
	{
		if (true == $this->_fancylayout) {
			return 'true';
		}
		return 'false';
	}

	/**
	 * setConsumer
	 *
	 * @param string $consumerKey
	 * @param string $consumerSecret
	 * @return MediaMonks_Service_Hyves
	 */
	public function setConsumer($consumerKey, $consumerSecret)
	{
		$this->_consumer = new StdClass();
		$this->_consumer->key = $consumerKey;
		$this->_consumer->secret = $consumerSecret;
		return $this;
	}

	/**
	 * setToken
	 *
	 * the token which belongs to a user
	 *
	 * @param string $key
	 * @param string $secret
	 * @return MediaMonks_Service_Hyves 
	 */
	public function setToken($key = null, $secret = null)
	{
		$this->_token->key = $key;
		$this->_token->secret = $secret;
		return $this;
	}

	/**
	 * setForcedLanguage
	 *
	 * force a language to use
	 *
	 * @param string $language (nl, en, dutch or english)
	 * @return MediaMonks_Service_Hyves
	 */
	public function setForcedLanguage($language)
	{
		$allowedLanguages = array('nl', 'en', 'dutch', 'english');

		if (empty($language) || !in_array($language, $allowedLanguages)) {
			throw new MediaMonks_Service_Hyves_Exception('Language "' . $language . '" not supported by Hyves API');
		}
		
		$this->_forcedLanguage = $language;
		return $this;
	}

	/**
	 * call
	 *
	 * calls the Hyves API
	 *
	 * @param string $method
	 * @param array $options
	 * @return MediaMonks_Service_Hyves_Response
	 */
	public function call($method, $options = array())
	{
		$request = new MediaMonks_Service_Hyves_Request($this, $method, $options);

		if (true == $this->_batch) {
			if (count($this->_batchStack) == 10) {
				// 10 is the maximum of calls to be batched, more is not supported by the Hyves API
				throw new MediaMonks_Service_Hyves_Request_Exception('Number of batchrequests limited to 10', 102);
				return;
			}
			$this->_batchStack[] = $request;
		} elseif (false == $this->_autoExecute) {
			$this->_callStack[] = $request;
		} else {
			if ('batch.process' == $method) {
				return $this->executeSingleCall($request, true);
			} else {
				return $this->executeSingleCall($request);
			}
		}
	}

	/**
	 * startBatch
	 *
	 * if a batch is started, calls will be stacked untill flushBatch is called
	 *
	 * @return MediaMonks_Service_Hyves
	 */
	public function startBatch()
	{
		$this->_batch = true;
		return $this;
	}

	/**
	 * endBatch
	 *
	 * @return MediaMonks_Service_Hyves
	 */
	public function endBatch()
	{
		$this->_batch = false;
		return $this;
	}

	/**
	 * flushBatch
	 *
	 * execute all stacked calls as batch call
	 */
	public function flushBatch()
	{
		$requests = array();
		foreach ($this->_batchStack as $request) {
			$requests[] = $request->toParams();
		}

		$options = array();
		$options['params'] = array();
		$options['params']['request'] = implode(',', $requests);

		$this->endBatch();
		$response = $this->call('batch.process', $options);
		$this->_batchStack = array();
		$this->startBatch();

		return $response;
	}

	/**
	 * setAutoExecute
	 *
	 * Turning this off will cause calls not to be executed immediatly,
	 * instead you need to call execute() for a parallel execution of calls
	 * for better performance. Default: enabled.
	 *
	 * @param <type> $value
	 * @return MediaMonks_Service_Hyves
	 */
	public function setAutoExecute($value)
	{
		$this->_autoExecute = $value;
		return $this;
	}

	/**
	 * execute
	 *
	 * execute stacked calls manually
	 *
	 * @return array of MediaMonks_Service_Hyves_Response
	 */
	public function execute()
	{
		return $this->executeMultiCall($this->_batch);
	}

	/**
	 * executeMultiCall
	 *
	 * @param boolean $isBatch
	 * @return array of MediaMonks_Service_Hyves_Response
	 */
	private function executeMultiCall($isBatch = false)
	{
		$responses = array();
		$calls = array();
		$i = 1;

		$mh = curl_multi_init();

		// add calls
		foreach ($this->_callStack as $request) {
			$calls[$i] = $this->createHandle($request);
			curl_multi_add_handle($mh, $calls[$i]);
			$i++;
		}

		// make the calls
		$active = null;
		do {
			$mrc = curl_multi_exec($mh, $active);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);

		while ($active && $mrc == CURLM_OK) {
			if (curl_multi_select($mh) != -1) {
				do {
					$mrc = curl_multi_exec($mh, $active);
				} while ($mrc == CURLM_CALL_MULTI_PERFORM);
			}
		}

		// close handlers and grab content
		for ($j = 1; $j < $i; $j++) {
			$response = curl_multi_getcontent($calls[$j]);

			if (true == $isBatch) {
				$responses[] = new MediaMonks_Service_Hyves_Response_Batch($this->_format, $response);
			} else {
				$responses[] = new MediaMonks_Service_Hyves_Response($this->_format, $response);
			}

			curl_multi_remove_handle($mh, $calls[$j]);
		}

		curl_multi_close($mh);

		return $responses;
	}

	/**
	 * executeSingleCall
	 *
	 * @param MediaMonks_Service_Hyves_Request $request
	 * @param boolean $isBatch
	 * @return MediaMonks_Service_Hyves_Response_Batch
	 */
	private function executeSingleCall(MediaMonks_Service_Hyves_Request $request, $isBatch = false)
	{
		$ch = $this->createHandle($request);
		$response = @curl_exec($ch);

		// detect error
		if (curl_errno($ch)) {
			$errorCode = curl_errno($ch);
			$errorMessage = curl_error($ch);
			curl_close($ch);
			throw new MediaMonks_Service_Hyves_Request_Exception($errorMessage, $errorCode);
		}

		curl_close($ch);

		if (true == $isBatch) {
			return new MediaMonks_Service_Hyves_Response_Batch($this->_format, $response);
		} else {
			return new MediaMonks_Service_Hyves_Response($this->_format, $response);
		}
	}

	/**
	 * createHandle
	 *
	 * creates a curl handle with the parameters from the request
	 *
	 * @param MediaMonks_Service_Hyves_Request $request
	 * @return resource
	 */
	private function createHandle(MediaMonks_Service_Hyves_Request $request)
	{
		$ch = curl_init();

		// set api url
		if ($this->_secureConnection == true) {
			$url = self::URL_API_SECURE;
		} else {
			$url = self::URL_API;
		}

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request->toParams());

		// add oauth authorization header, preferred method of communication
		curl_setopt($ch, CURLOPT_HTTPHEADER, array($request->toAuthorizationHeader()));

		if (true == $this->_secureConnection) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
		}

		if(!empty($this->_curlOptions)) {
			curl_setopt_array($ch, $this->_curlOptions);
		}

		return $ch;
	}

	/**
	 * uploadFile
	 *
	 * retreives an upload token from the API and uploads the file to
	 * the specified server. The response returned contains the upload token
	 * and server ip the file is uploaded to, you will need this to poll the
	 * status of the uploaded file.
	 * 
	 * documentation: http://trac.hyves-api.nl/wiki/APIMediaUpload
	 *
	 * @param string $file full path to file on local file system
	 * @param array $options
	 * @return MediaMonks_Service_Hyves_Response
	 */
	public function uploadFile($file, $options = array())
	{
		// check if file exists
		if (!file_exists($file)) {
			throw new MediaMonks_Service_Hyves_Exception('file should contain an existing file');
		}

		// request upload token at hyves
		$response = $this->call('media.getUploadToken');
		$uploadToken = $response->getBody();

		// upload file
		$data = array();
		$data['file'] = '@' . $file; // a file is prefixed with a '@' so curl knowns its a file

		$allowedOptions = array('title', 'description', 'geodata', 'angle');
		foreach ($allowedOptions as $key) {

			if (!empty($options[$key])) {
				switch ($key) {
					case 'angle': {
						$allowedAngles = array(0, 90, 180, 270);
						if (in_array($options[$key], $allowedAngles)) {
							$data[$key] = $options[$key];
						} else {
							throw new MediaMonks_Service_Hyves_Exception('Invalid angle, ' .
									'allowed: ' . implode(', ', $allowedAngles));
						}
						break;
					}
					case 'geodata': {
						// 52,3729,4,8937
						if (preg_match('/^[0-9]{1,2}\.[0-9]{0,9},[0-9]{1,2}\.[0-9]{0,9}$/', $options[$key])) {
							$data[$key] = $options[$key];
						} else {
							throw new MediaMonks_Service_Hyves_Exception('Invalid geodata, ' .
									'should be 2 floats comma-seperated without a space');
						}
						break;
					}
					default: {
							$data[$key] = $options[$key];
							break;
					}
				}
			}
		}

		// location format: http://#ip#/upload?token=#urlencode(token)#
		// as defined in the documentation(http://trac.hyves-api.nl/wiki/APIMediaUpload)
		$url = 'http://' . $uploadToken['ip'] . '/upload?token=' . urlencode($uploadToken['token']);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$response = @curl_exec($ch);

		// detect error
		if (curl_errno($ch)) {
			$errorCode = curl_errno($ch);
			$errorMessage = curl_error($ch);
			curl_close($ch);
			throw new MediaMonks_Service_Hyves_Request_Exception($errorMessage, $errorCode);
		}

		curl_close($ch);

		return $uploadToken;
	}

	/**
	 * getUploadStatus
	 *
	 * get the status of the file upload
	 *
	 * @param string $ip
	 * @param string $token
	 * @return MediaMonks_Service_Hyves_Response_Upload
	 */
	public function getUploadStatus($ip, $token)
	{
		// multiple tokens are allowed at once
		if (is_array($token)) {
			$token = implode(',', $token);
		}

		$ch = curl_init();

		// location format: http://#ip#/upload?token=#urlencode(token)#
		// as defined in the documentation(http://trac.hyves-api.nl/wiki/APIMediaUpload)
		$url = 'http://' . $ip . '/status?token=' . urlencode($token);

		curl_setopt($ch, CURLOPT_URL, $url);

		// return response on execute instead of displaying it
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$response = @curl_exec($ch);

		// detect error
		if (curl_errno($ch)) {
			$errorCode = curl_errno($ch);
			$errorMessage = curl_error($ch);
			curl_close($ch);
			throw new MediaMonks_Service_Hyves_Request_Exception($errorMessage, $errorCode);
		}

		curl_close($ch);

		require_once 'MediaMonks/Service/Hyves/Response/Upload.php';
		return new MediaMonks_Service_Hyves_Response_Upload($response, $token);
	}

}