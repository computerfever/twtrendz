<?php

namespace Acelle\Library\Everification;

use Exception;
use Acelle\Library\Contracts\VerifyInterface;
use Acelle\Library\Contracts\BulkVerifyInterface;
use Illuminate\Database\Eloquent\Builder;
use AthenaEvs\Client;
use Closure;

class AthenaEvs implements VerifyInterface, BulkVerifyInterface
{
    protected $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getClient()
    {
        return new Client($this->apiKey);
    }

    public function getCredits(): ?int
    {
        $response = $this->getClient()->getCredits();

        return $response['credits'];
    }

    public function verify($email)
    {
        $response = $this->getClient()->verify($email);

        return [$response['status'], (string) json_encode($response['result'])];
    }

    public function bulkSubmit(Builder $subscriberQuery): string
    {
        $response = $this->getClient()->batchVerify($subscriberQuery->pluck('email')->toArray());

        $batchId = $response['batch_id'];

        return $batchId;
    }

    public function bulkCheck(string $batchId, Closure $doneCallback, Closure $waitCallback): bool
    {

        $response = $this->getClient()->getBatchStatus($batchId);

        if (!$response['status']) {
            if ($waitCallback) {
                $waitCallback(json_encode($response));
            }

            return false;
        } else {
            // fetch results
            $response = $this->getClient()->getBatchResult($batchId);

            foreach ($response['result'] as $emailRaw) {
                $email = $emailRaw['email'];
                $status = $emailRaw['status'];
                $doneCallback($email, $status, json_encode($emailRaw));
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
        return 'AthenaEvs';
    }

    public function getServiceUrl(): string
    {
        return 'https://athenaevs.com';
    }
}
