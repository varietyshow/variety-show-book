<?php

namespace MagpieApi;

class Response
{
	private $httpCode;
	private $output;
	private $successCode;

	public function __construct($httpCode, $output) {
		$this->httpCode = $httpCode;
		$this->output = $output;
	}

	public function successCode($code) {
		$this->successCode = $code;
	}

	public function isSuccess() {
		return $this->successCode === $this->httpCode;
	}

	public function isFail() {
		return $this->successCode !== $this->httpCode;
	}

	public function httpCode() {
		return $this->httpCode;
	}

	public function raw() {
		return $this->output;
	}

	public function toArray() {
		return (array) json_decode($this->output, true);
	}

	public function toJson() {
		return $this->output;
	}
}