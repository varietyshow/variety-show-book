<?php

namespace MagpieApi;

/**
 * This class holds the APIs anything related to customer.
 */
class Customer
{
    const URI = '/v1/customers';
    private $baseUrl;
    private $isSandbox;
    private $request;
    private $key;

    /**
     * Class constructor.
     *
     * @param bool   $isSandbox Determines if the API will use sandbox URL or production URL
     * @param string $key       The authorization key to be used for calling endpoints
     */
    public function __construct($isSandbox = false, $key = null)
    {
        $this->isSandbox = $isSandbox;
        $this->request = new Request($this->isSandbox, self::URI);
        $this->key = $key;
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
        }
    }

    /**
     * Creates a customer.
     *
     * @param string $email       E-mai address of the customer
     * @param string $description Description about the customer
     *
     * @return Magpie\Response Response from the API
     */
    public function create($email, $description)
    {
        $response = $this->request->post(
            self::URI,
            $this->key,
            array(
                'email' => $email,
                'description' => $description,
            )
        );
        $response->successCode(201);

        return $response;
    }

    /**
     * Gets a customer.
     *
     * @param string $id ID of the customer to fetch
     *
     * @return Magpie\Response Response from the API
     */
    public function get($id)
    {
        $response = $this->request->get(self::URI.'/'.$id, $this->key);
        $response->successCode(200);

        return $response;
    }

    /**
     * Updates a customer.
     *
     * @param string $id      ID of the customer to update
     * @param string $tokenId Token ID to add as a source for the customer
     *
     * @return Magpie\Response Response from the API
     */
    public function update($id, $tokenId)
    {
        $response = $this->request->put(
            self::URI.'/'.$id,
            $this->key,
            array(
                'source' => $tokenId,
            )
        );
        $response->successCode(200);

        return $response;
    }

    /**
     * Deletes a customer.
     *
     * @param string $id ID of the customer to delete
     *
     * @return Magpie\Response Response from the API
     */
    public function delete($id)
    {
        $response = $this->request->delete(
            self::URI.'/'.$id,
            $this->key
        );
        $response->successCode(200);

        return $response;
    }

    /**
     * Deletes a source from a customer.
     *
     * @param string $id     ID of the customer to delete source
     * @param string $cardId Card ID to delete from the customer
     *
     * @return Magpie\Response Response from the API
     */
    public function deleteSource($id, $cardId)
    {
        $response = $this->request->delete(
            self::URI.'/'.$id.'/sources/'.$cardId,
            $this->key
        );
        $response->successCode(200);

        return $response;
    }
}
