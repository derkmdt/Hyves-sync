<?php

require_once 'MediaMonks/Service/Hyves/Authorization/Exception.php';
require_once 'MediaMonks/Service/Hyves/Authorization/Storage/Abstract.php';
require_once 'MediaMonks/Service/Hyves/Authorization/Storage/Interface.php';

class MediaMonks_Service_Hyves_Authorization extends MediaMonks_Service_Hyves
{
	const EXPIRATION_TYPE_USER = 'user';
	const EXPIRATION_TYPE_DEFAULT = 'default';
	const EXPIRATION_TYPE_INFINITE = 'infinite';

	protected $_hyves = null;

	public function __construct(MediaMonks_Service_Hyves $hyves)
	{
		$this->_hyves = $hyves;
		return $this;
	}

	public function requestUserAuthorization(
		$callbackUrl, $methods,
		MediaMonks_Service_Hyves_Authorization_Storage_Interface $storage,
		$expirationType = self::EXPIRATION_TYPE_DEFAULT, $options = array()
	)
	{
		// validate callback url
		if (empty($callbackUrl)) {
			throw new MediaMonks_Service_Hyves_Authorization_Exception('$callbackUrl should not be empty');
		}

		// validate methods
		if (empty($methods) || !is_array($methods)) {
			throw new MediaMonks_Service_Hyves_Authorization_Exception('$methods should be an array of methods');
		}

		// validate expiration type
		$availableExpirationTypes = array(
			self::EXPIRATION_TYPE_USER,
			self::EXPIRATION_TYPE_DEFAULT,
			self::EXPIRATION_TYPE_INFINITE
		);

		if (!in_array($expirationType, $availableExpirationTypes)) {
			throw new MediaMonks_Service_Hyves_Authorization_Exception('$expirationType should contain a valid' .
					' expiration type ("' . implode('", "', $availableExpirationTypes) . '")');
		}

		// see if tokens are present
		if (($requestToken = $storage->getTokens()) !== false) {
			try {

				// get access tokens with signed request tokens
				$accessTokens = $this->getAccessTokens($requestToken['oauth_token'], $requestToken['oauth_token_secret']);

				// destroy request tokens
				$storage->destroyTokens();
			} catch (Exception $e) {

				// destroy request tokens
				$storage->destroyTokens();

				throw new MediaMonks_Service_Hyves_Authorization_Exception('error getting authorization "' . $e->getMessage() . '"', $e->getCode());
			}

			// return access tokens to app, we can use these to make calls with
			return $accessTokens;
		} else {
			// retrieve request tokens from hyves
			$requestToken = $this->getRequestToken($methods, $expirationType);

			// save the unsigned request tokens
			$storage->saveTokens($requestToken);

			// redirect user to authorization page
			$this->redirectAuthorize($requestToken['oauth_token'], $callbackUrl);
		}
	}

	private function getRequestToken($methods, $expirationType)
	{
		if (!is_array($methods) || empty($methods)) {
			throw new MediaMonks_Service_Hyves_Exception('Array of methods should be specified');
		}

		if (empty($expirationType)) {
			$expirationType = 'default';
		} else {
			if (!in_array($expirationType, array('default', 'infinite', 'user'))) {
				throw new MediaMonks_Service_Hyves_Exception('A valid expiration type should be specified');
			}
		}

		$response = $this->_hyves->call('auth.requesttoken', array(
					'params' => array('methods' => implode(',', $methods), 'expirationtype' => $expirationType)
				));

		return $response->getBody();
	}

	private function redirectAuthorize($token, $callback, $mobile = false)
	{
		// build url and redirect user to hyves to authorize the url
		if ($this->_hyves->getSecureConnection() == true) {
			if ($mobile === true) {
				$authorizeUrl = self::URL_AUTHORIZE_SECURE_MOBILE;
			} else {
				$authorizeUrl = self::URL_AUTHORIZE_SECURE;
			}
		}
		else {
			if ($mobile === true) {
				$authorizeUrl = self::URL_AUTHORIZE_MOBILE;
			} else {
				$authorizeUrl = self::URL_AUTHORIZE;
			}
		}

		$authorizeUrl .= '?oauth_token=' . OAuthUtil::urlencode_rfc3986($token);
		$authorizeUrl .= '&oauth_callback=' . OAuthUtil::urlencode_rfc3986($callback);

		echo 'Geef <a href="' . $authorizeUrl . '">hier</a> toestemming om je foto(\'s) te uploaden.';
		//header('Location: ' . $authorizeUrl);
		exit; // exit is important because not all client respect headers
	}

	private function getAccessTokens($token, $secret)
	{
		$this->_hyves->setToken($token, $secret);
		$response = $this->_hyves->call('auth.accesstoken');
		return $response->getBody();
	}

}