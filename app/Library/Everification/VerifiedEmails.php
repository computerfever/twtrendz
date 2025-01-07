<?php

namespace Acelle\Library\Everification;

use Exception;
use Acelle\Library\Contracts\VerifyInterface;
use Acelle\Library\Contracts\BulkVerifyInterface;
use Illuminate\Database\Eloquent\Builder;
use GuzzleHttp\Client as GuzzleClient;
use Closure;

class VerifiedEmails implements VerifyInterface
{
    protected $username;
    protected $apiToken;
    protected $enpoint;
    protected $client;

    protected $map = [
        'Valid' => 'deliverable',
        'Invalid' => 'undeliverable',
        'Catch All' => 'risky',
        'Unknown' => 'unknown',
    ];

    public const DEFAULT_ENPOINT = 'https://verifiedemails.io/api/v';

    public function __construct($username, $apiToken)
    {
        $this->username = $username;
        $this->apiToken = $apiToken;
        $this->enpoint = static::DEFAULT_ENPOINT;
    }

    public function isBulkVerifySupported(): bool
    {
        return false;
    }

    private function getClient()
    {
        if (!$this->client) {
            $this->client = new GuzzleClient([
                'base_uri' => $this->enpoint,
                'verify' => false,
                'headers' => [],
            ]);
        }

        return $this->client;
    }

    private function makeRequest($method, $uri, $params = [])
    {
        $client = $this->getClient();

        // API key
        $params = array_merge($params, [
            // 'user' => 'iamanantgupta',
            // 'api_token' => '6fb6f064a046350710e04617', // $this->apiToken,
            'user' => $this->username,
            'api_token' => $this->apiToken,
        ]);

        //
        try {
            $options = [
                // 'headers' => [
                //     'Authorization' => "Bearer {$this->apiKey}",
                //     'Accept' => 'application/json',
                // ],
            ];

            // If the method is POST, add form_params
            if (strtoupper($method) == 'POST') {
                $options['form_params'] = $params;
            } elseif (strtoupper($method) == 'GET') {
                $options['query'] = $params;
            }

            $response = $client->request($method, $uri, $options);

            // Get the status code of the response
            $statusCode = $response->getStatusCode();

            // Get the response body
            $body = $response->getBody();

            // Decode the JSON response if necessary
            $data = json_decode($body, true);

            // Output the data
            return $data;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // // Handle client exceptions
            throw new \Exception($e->getResponse()->getBody()->getContents());
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // // Handle request exceptions
            throw new \Exception($e->getResponse()->getBody()->getContents());
        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function verify($email)
    {
        $data = $this->makeRequest('GET', '', [
            'verify' => $email,
        ]);

        if (!isset($data) || !isset($data[0]) || !isset($data[0]['verify']) || !isset($data[0]['response'])) {
            throw new \Exception("Something went wrong. Reponse: " . json_encode($data));
        }

        return [$this->map[$data[0]["response"]['status']], (string) json_encode($data[0]["response"])];
    }


}
