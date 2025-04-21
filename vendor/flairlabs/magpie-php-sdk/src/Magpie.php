<?php

namespace MagpieApi;

class Magpie
{
    private $isSandbox = false;
    private $currentUrl = '';
    private $pkKey = '';
    private $skKey = '';
    private $curl;
    private $token;
    private $charge;
    private $customer;
    private $version;

    public function __construct($pkKey = null, $skKey = null, $isSandbox = false, $version = 'v1')
    {
        if (!in_array($version, ['v1', 'v1.1'])) {
            throw new \Exception('Invalid version. Choose either v1 or v1.1');
        }

        $this->pkKey = $pkKey;
        $this->skKey = $skKey;
        $this->isSandbox = $isSandbox;
        $this->version = $version;
        $this->token = new Token($this->isSandbox, $this->pkKey);
        $this->charge = new Charge($this->version, $this->isSandbox, $this->skKey);
        $this->customer = new Customer($this->isSandbox, $this->skKey);
    }

    public function __set($name, $value) {
        switch($name)
        {
            case 'pkKey':
                $this->pkKey = $value;
                $this->token->key = $value;
                break;
            case 'skKey':
                $this->skKey = $value;
                $this->charge->key = $value;
                $this->customer->key = $value;
                break;
            case 'isSandbox':
                $this->isSandbox = $value;
                $this->token->isSandbox = $value;
                $this->charge->isSandbox = $value;
                $this->customer->isSandbox = $value;
                break;
            case 'version':
                if (!in_array($value, ['v1', 'v1.1'])) {
                    throw new \Exception('Invalid version. Choose either v1 or v1.1');
                }

                $this->version = $value;
                $this->charge->version = $value;
        }
    }

    public function __get($name) {
        switch($name) {
            case 'token':
                return $this->token;
                break;
            case 'charge':
                return $this->charge;
                break;
            case 'customer':
                return $this->customer;
                break;
            default:
                return null;
        }
    }
}
