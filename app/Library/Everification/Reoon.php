<?php

namespace Acelle\Library\Everification;

use Exception;
use Acelle\Library\Contracts\VerifyInterface;
use Acelle\Library\Contracts\BulkVerifyInterface;
use Illuminate\Database\Eloquent\Builder;
use GuzzleHttp\Client as GuzzleClient;
use Closure;

class Reoon implements VerifyInterface, BulkVerifyInterface
{
    protected $apiKey;
    protected $client;
    protected $map = [
        'valid' => 'deliverable',
        'safe' => 'deliverable',
        'invalid' => 'undeliverable',
        'disposable' => 'risky',
        'spamtrap' => 'unknown',
    ];

    public const BASE_URI = 'https://emailverifier.reoon.com/api/v1/';

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    private function getClient()
    {
        if (!$this->client) {
            $this->client = new GuzzleClient([
                'base_uri' => static::BASE_URI,
                'verify' => false,
                'headers' => [
                    // 'Content-Type' => 'application/json',
                    // 'Authorization' => "Bearer {$this->apiKey}",
                ],
            ]);
        }

        return $this->client;
    }

    private function makeRequest($method, $uri, $params = [])
    {
        $client = $this->getClient();

        // API key
        $params = array_merge($params, [
            'key' => $this->apiKey,
        ]);

        //
        try {
            $options = [
                'headers' => [
                    // 'Authorization' => "Bearer {$this->apiKey}",
                    // 'Accept' => 'application/json',
                ],
            ];

            // If the method is POST, add form_params
            if (strtoupper($method) == 'POST') {
                $options['json'] = $params;
            } elseif (strtoupper($method) == 'GET') {
                $options['query'] = $params;
            }

            $response = $client->request($method, $uri, $options);

            // // Get the status code of the response
            // $statusCode = $response->getStatusCode();

            // // Get the response body
            // $body = $response->getBody();

            // // Decode the JSON response if necessary
            // $data = json_decode($body, true);

            // Output the data
            return $response;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // // Handle client exceptions
            // echo 'ClientException: ' . $e->getMessage() . "\n";
            // echo 'Response: ' . $e->getResponse()->getBody()->getContents() . "\n";
            // echo 'Stack Trace: ' . $e->getTraceAsString() . "\n";

            return $e->getResponse();
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // // Handle request exceptions
            // echo 'RequestException: ' . $e->getMessage() . "\n";
            // if ($e->hasResponse()) {
            //     echo 'Response: ' . $e->getResponse()->getBody()->getContents() . "\n";
            // }
            // echo 'Stack Trace: ' . $e->getTraceAsString() . "\n";

            return $e->getResponse();
        } catch (Exception $e) {
            // // Handle all other exceptions
            // echo 'Exception: ' . $e->getMessage() . "\n";
            // echo 'Stack Trace: ' . $e->getTraceAsString() . "\n";

            return $e;
        }
    }

    public function getCredits(): ?int
    {
        return null;
    }

    public function verify($email)
    {
        $response = $this->makeRequest('GET', 'verify', [
            'email' => $email,
        ]);

        if ($response->getStatusCode() != 200) {
            throw new Exception("Error verifying email {$email}!, {$response->getStatusCode()}, {$response->getReasonPhrase()}");
        }

        $raw = (string)$response->getBody();
        $json = json_decode($raw, true);

        return [$this->map[$json['status']], (string) json_encode($json)];
    }

    public function bulkSubmit(Builder $subscriberQuery): string
    {
        $response = $this->makeRequest('POST', 'create-bulk-verification-task/', [
            'name' => "Bulk Submit",
            'emails' => $subscriberQuery->pluck('email')->toArray(),
        ]);

        $raw = (string)$response->getBody();
        $result = json_decode($raw, true);

        return $result['task_id'];
    }

    public function bulkCheck(string $batchId, Closure $doneCallback, Closure $waitCallback): bool
    {
        // 1438667
        $response = $this->makeRequest('GET', 'get-result-bulk-verification-task/', [
            'task_id' => $batchId,
        ]);

        $raw = (string)$response->getBody();
        $response = json_decode($raw, true);

        if ($response['status'] != 'completed') {
            if ($waitCallback) {
                $waitCallback(json_encode($response));
            }

            return false;
        } else {
            foreach ($response['results'] as $email => $result) {
                $email = $email;
                $status = $this->map[$result['status']];
                $doneCallback($email, $status, json_encode($result));
            }

            return true;
        }

        return false;
    }

    public function isBulkVerifySupported(): bool
    {
        return true;
    }


    public function getServiceName(): string
    {
        return 'Reoon';
    }

    public function getServiceUrl(): string
    {
        return 'https://emailverifier.reoon.com';
    }
}
