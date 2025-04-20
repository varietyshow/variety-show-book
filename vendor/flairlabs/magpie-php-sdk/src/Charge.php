<?php

namespace MagpieApi;

/**
 * This class holds the APIs anything related to charge.
 */
class Charge
{
    const URI = '/charges';
    private $baseUrl;
    private $isSandbox;
    private $request;
    private $key;
    private $version;

    /**
     * Class constructor.
     *
     * @param string $version   The API version (required)
     * @param bool   $isSandbox Determines if the API will use sandbox URL or production URL (optional)
     * @param string $key       The authorization key to be used for calling endpoints (optional)
     */
    public function __construct($version, $isSandbox = false, $key = null)
    {
        $this->isSandbox = $isSandbox;
        $this->request = new Request($this->isSandbox);
        $this->key = $key;
        $this->version = $version;
    }

    public function __set($name, $value)
    {
        switch($name) {
            case 'key':
                $this->key = $value;
                break;
            case 'isSandbox':
                $this->isSandbox = $value;
                $this->request->isSandbox = $this->isSandbox;
                break;
            case 'version':
                $this->version = $value;
                break;
        }
    }

    /**
     * Creates a charge.
     *
     * @param float  $amount              Amount of the charge. Should be: <the expected amount> times 100
     * @param string $currency            Currency of the charge
     * @param string $source              Source of the charge. Should be a token id
     * @param string $description         Description of the charge
     * @param string $statementDescriptor Statement descriptor of the charge
     * @param bool   $capture             Determines if the charge should capture or not. true or false
     *
     * @return Magpie\Response Response from the API
     */
    public function create(
        $amount,
        $currency,
        $source,
        $description,
        $statementDescriptor,
        $capture
    ) {
        $response = $this->request->post(
            '/' . $this->version . self::URI,
            $this->key,
            array(
                'amount' => $amount,
                'currency' => $currency,
                'source' => $source,
                'description' => $description,
                'statement_descriptor' => $statementDescriptor,
                'capture' => $capture,
            )
        );
        $response->successCode(201);

        return $response;
    }

    /**
     * Gets a specific charge.
     *
     * @param string $id Charge ID to fetch
     *
     * @return Magpie\Response Response from the API
     */
    public function get($id)
    {
        $response = $this->request->get(
            '/' . $this->version . self::URI.'/'.$id,
            $this->key
        );
        $response->successCode(200);

        return $response;
    }

    /**
     * Capture a charge.
     *
     * @param string $id     Charge ID to capture
     * @param float  $amount Amount to capture from $id charge id. Should be: <expected amount> times 100
     *
     * @return Magpie\Response Response from the API
     */
    public function capture($id, $amount)
    {
        $response = $this->request->post(
            '/' . $this->version . self::URI.'/'.$id.'/capture',
            $this->key,
            array(
                'amount' => $amount,
            )
        );
        $response->successCode(200);

        return $response;
    }

    /**
     * Void a charge.
     *
     * @param string $id Charge ID to void
     *
     * @return Magpie\Response Response from the API
     */
    public function void($id)
    {
        $response = $this->request->post(
            '/' . $this->version . self::URI.'/'.$id.'/void',
            $this->key
        );
        $response->successCode(200);

        return $response;
    }

    /**
     * Refunds a charge.
     *
     * @param string $id     Charge ID to refund
     * @param float  $amount Amount to refund from a charge. Should be: <expected amount> times 100
     *
     * @return Magpie\Response Response of the API
     */
    public function refund($id, $amount)
    {
        $response = $this->request->post(
            '/' . $this->version . self::URI.'/'.$id.'/refund',
            $this->key,
            array(
                'amount' => $amount,
            )
        );
        $response->successCode(200);

        return $response;
    }
}
