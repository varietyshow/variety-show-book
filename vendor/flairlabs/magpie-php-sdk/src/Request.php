<?php

namespace MagpieApi;

class Request
{
	private $curl;
	private $baseUrl;

    const SANDBOX_URL = 'https://sandbox.api.magpie.im';
    const PRODUCTION_URL = 'https://api.magpie.im';

    /**
     * Class constructor
     * @param [type] $isSandbox [description]
     */
	public function __construct($isSandbox) {
		if ($isSandbox) {
			$this->baseUrl = self::SANDBOX_URL;
		} else {
			$this->baseUrl = self::PRODUCTION_URL;
		}
	}

    public function __set($name, $value)
    {
        switch($name)
        {
            case 'isSandbox':
                $this->isSandbox = $value;
                if ($this->isSandbox) {
                    $this->baseUrl = self::SANDBOX_URL;
                } else {
                    $this->baseUrl = self::PRODUCTION_URL;
                }
                break;
        }
    }

    /**
     * Sets the POST data to the cURL object
     * @param [type] $curlObj [description]
     * @param [type] $data    [description]
     */
    private function setPostData($curlObj, $data) {
        curl_setopt($curlObj, CURLOPT_POSTFIELDS, json_encode($data));
    }

	private function setUrl($curlObj, $uri) {
        curl_setopt($curlObj, CURLOPT_URL, $this->baseUrl . $uri);
    }

    /**
     * Returns base 64 encoded pkey or skey
     * @param  [type] $key [description]
     * @return [type]      [description]
     */
    private function getAuthKey($key)
    {
        return base64_encode($key);
    }

    private function generateResponse($curlObj) {
    	$output = curl_exec($curlObj);

        if(curl_errno($curlObj)) {
            throw new \Exception(curl_error($curlObj));
        }

        return new Response(
        	curl_getinfo($curlObj, CURLINFO_HTTP_CODE),
        	$output
        );
    }

    private function closeCurl($curlObj) {
        curl_close($curlObj);
    }

    private function generateHeader($curlObj, $key) {
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlObj, CURLOPT_HTTPHEADER, array(
         'Content-Type: application/json',
         'Authorization: Basic ' . $this->getAuthKey($key)
        ));
    }

	public function get($uri, $key) {
		$this->curl = curl_init();

        $this->setUrl($this->curl, $uri);
        $this->generateHeader($this->curl, $key);

        $response = $this->generateResponse($this->curl);

        $this->closeCurl($this->curl);

        return $response;
	}

	public function post($uri, $key, $data = array()) {
		$this->curl = curl_init();

        $this->setUrl($this->curl, $uri);
        curl_setopt($this->curl, CURLOPT_POST, true);
        $this->generateHeader($this->curl, $key);

        if (!empty($data)) {
            $this->setPostData($this->curl, $data);
        }

        $response = $this->generateResponse($this->curl);

        $this->closeCurl($this->curl);

        return $response;
	}

	public function put($uri, $key, $data) {
		$this->curl = curl_init();

        $this->setUrl($this->curl, $uri);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        $this->generateHeader($this->curl, $key);
        $this->setPostData($this->curl, $data);

        $response = $this->generateResponse($this->curl);

        $this->closeCurl($this->curl);

        return $response;
	}

	public function delete($uri, $key) {
		$this->curl = curl_init();

        $this->setUrl($this->curl, $uri);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        $this->generateHeader($this->curl, $key);

        $response = $this->generateResponse($this->curl);

        $this->closeCurl($this->curl);

        return $response;
	}
}