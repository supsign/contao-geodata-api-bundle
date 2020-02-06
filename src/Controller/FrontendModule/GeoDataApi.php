<?php

namespace Supsign\ContaoGeoDataApiBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
use Contao\ModuleModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * @FrontendModule(category="Bottom")
 */


class GeoDataApi
{
	protected
		$ch 				= null,
		$clientId   		= 'TEST_8f7a0338-d4e4-493b-b83c-733fe41cdd02',
		$clientSecret 		= 'TEST_0b7f92ae-41af-4ba3-94bb-083c5016aa6e',
		$endpoints			= ['authorization' => 'https://wedec.post.ch/WEDECOAuth/authorization/', 'address' => 'https://wedec.post.ch/api/address/v1/'],
		$inputData 			= null,
		$parameterData		= null,
		$parameterString 	= null,
		$response			= null,
		$accessToken		= null,
		$accessTokenType	= null;

	public function __construct() {
		$this->endpoints = (object)$this->endpoints;

		return $this->createAccessToken();
	}

	protected function addInputData($key, $value) {
		$this->inputData->$key = $value;

		return $this;
	}

	protected function addParameterData($key, $value) {
		if (!$this->parameterData)
			$this->newParameterData();
			
		$this->parameterData->$key = $value;

		return $this;
	}

	protected function clearInputData() {
		$this->inputData = new \stdClass;

		return $this;
	}

	protected function clearParameterData() {
		$this->parameterData = new \stdClass;

		return $this;
	}

	protected function createAccessToken() {
		$this
			->createAuthRequest()
			->sendRequest();

		if (!$this->getResponse()->access_token)
			throw new \Exception('Couldn\'t fetch access token', 1);
			
		$this->accessToken 		= $this->getResponse()->access_token;
		$this->accessTokenType 	= $this->getResponse()->token_type;

		return $this;
	}

	protected function createAddressRequest($type = 'zips') {
		$this
			->createApiRequest('GET')
			->newParameterData()
			->setParameterData($this->inputData);

		curl_setopt($this->ch, CURLOPT_URL, $this->endpoints->address.$type.$this->getParameterString() );

		return $this;
	}

	protected function createApiRequest($type = null) {
		$this->ch = curl_init();

		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);

		if ($this->accessToken)
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, ['Authorization: '.$this->getAccessTokenType().' '.$this->getAccessToken()]);

		if ($type)
			curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, strtoupper($type) );
		else
			curl_setopt($this->ch, CURLOPT_POST, true);

		return $this;
	}

	protected function createAuthRequest() {
		$this->createApiRequest();

		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded') );
		curl_setopt($this->ch, CURLOPT_URL, $this->endpoints->authorization);

		$data = [
			'grant_type' 	=> 'client_credentials',
			'scope' 		=> 'WEDEC_AUTOCOMPLETE_ADDRESS',
			'client_id'		=> $this->clientId,
			'client_secret' => $this->clientSecret
		];

		$this
			->newParameterData()
			->setParameterData($data);

        curl_setopt($this->ch, CURLOPT_POSTFIELDS, ltrim($this->getParameterString(), '?') );

        return $this;
	}

	protected function createParameterString() {
		$this->parameterString = '?';

		foreach ($this->parameterData AS $key => $value)
			$this->parameterString .= $key.'='.$value.'&';

		$this->parameterString = rtrim($this->parameterString, '&');

		return $this;
	}

	protected function getAccessToken() {
		if (!$this->accessToken)
			$this->createAccessToken();

		return $this->accessToken;
	}

	protected function getAccessTokenType() {
		if (!$this->accessTokenType)
			$this->createAccessToken();

		return $this->accessTokenType;
	}

	public function getCityOrZip($input, $limit = 20) {
		if (!is_string($input) )
			throw new \Exception('Input has to be a string', 1);

		$this
			->setInputData(['zipCity' => $input, 'limit' => $limit, 'type' => 'DOMICILE'])
			->createAddressRequest('zips')
			->sendRequest();

		// var_dump(
		// 	$this->getResponse()
		// );

		$results = new \stdClass;

		foreach ($this->getResponse()->zips AS $result)
			if (!isset($results->{$result->zip}) )
				$results->{$result->zip} = $result->city18;

		return $results;
	}

	protected function getParameterString() {
		if (!$this->parameterData)
			return $this->parameterString ?: '';
		else
			$this->createParameterString();

		return $this->parameterString;
	}

	public function getResponse() {
		return $this->response;
	}

	protected function newInputData() {
		return $this->clearInputData();
	}

	protected function newParameterData() {
		return $this->clearParameterData();
	}

	protected function sendRequest() {
		$this->response = json_decode(curl_exec($this->ch) );

		curl_close($this->ch);

		return $this;
	}

	protected function setInputData($data) {
		if (!$this->inputData)
			$this->newInputData();

		if (is_iterable($data) )
			foreach ($data AS $key => $value)
				$this->addInputData($key, $value);

		return $this;
	}

	protected function setParameterData($data) {
		if (!$this->parameterData)
			$this->newParameterData();

		foreach ($data AS $key => $value)
			$this->addParameterData($key, $value);

		return $this;
	}
}