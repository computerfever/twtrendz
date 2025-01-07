<?php

namespace Acelle\Library\Everification;

use Exception;
use GuzzleHttp\Client;
use Acelle\Library\Exception\VerificationTakesLongerThanNormal;
use Acelle\Library\Contracts\VerifyInterface;
use Acelle\Library\Contracts\BulkVerifyInterface;
use Illuminate\Database\Eloquent\Builder;
use Acelle\Library\InMemoryRateTracker;
use Acelle\Library\RateLimit;
use Closure;

use function Acelle\Helpers\execute_with_limits;

class Emailable implements VerifyInterface, BulkVerifyInterface
{
    protected $apiKey;
    protected $client;
    protected $map = [
        'deliverable' => 'deliverable',
        'undeliverable' => 'undeliverable',
        'risky' => 'risky',
        'unknown' => 'unknown',
    ];

    public const BASE_URI = 'https://api.emailable.com/v1/';

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getCredits(): ?int
    {
        $getCreditsUri = "account";
        $response = $this->getClient()->request('GET', $getCreditsUri);

        if ($response->getStatusCode() != 200) {
            throw new Exception('Error getting available credits from emailable.com: '.$response->getStatusCode().' - '.$response->getReasonPhrase());
        }

        $raw = (string)$response->getBody();
        $json = json_decode($raw, true);

        $credits = (int)$json['available_credits'];

        if ($credits < 0) { // sometimes emailable.com returns a negative number
            return 0;
        } else {
            return $credits;
        }
    }

    public function getClient()
    {
        if ($this->client) {
            return $this->client;
        }

        // build the request URI
        $this->client = new Client([
            'base_uri' => static::BASE_URI,
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$this->apiKey}",
            ],
        ]);

        return $this->client;
    }

    public function verify($email)
    {
        $verifyUri = "verify?email={$email}";

        // Request
        $response = $this->getClient()->request('POST', $verifyUri, []);
        $result = $this->parseResult($response);

        return [$result, (string)$response->getBody()];
    }

    public function bulkSubmit(Builder $subscriberQuery): string
    {
        // Likely to throw RateLimitExceeded
        $fileId = execute_with_limits([$this->initRateLimitTracker()], $pool = null, $creditTrackers = [], function () use ($subscriberQuery) {
            return $this->submit($subscriberQuery);
        });

        return $fileId;
    }

    private function submit(Builder $subscriberQuery): string
    {
        $batchVerifyUri = "batch";
        $response = $this->getClient()->request('POST', $batchVerifyUri, [
            'json' => [
                'emails' => implode(",", $subscriberQuery->pluck('email')->toArray()),
            ]
        ]);

        if ($response->getStatusCode() != 200) {
            throw new Exception('Error executing batch API to emailable.com: '.$response->getStatusCode().' - '.$response->getReasonPhrase());
        }

        $raw = (string)$response->getBody();
        $json = json_decode($raw, true);

        return $json['id'];
    }

    public function bulkCheck(string $token, Closure $doneCallback, Closure $waitCallback = null): bool
    {
        $getBatchStatusUri = "batch?id={$token}";
        //$getBatchStatusUri = "batch?id={$token}&simulate=paused";
        $response = $this->getClient()->request('GET', $getBatchStatusUri);

        if ($response->getStatusCode() != 200) {
            throw new Exception('Error executing Get-batch-status API to emailable.com: '.$response->getStatusCode());
        }

        $raw = (string)$response->getBody();
        $json = json_decode($raw, true);

        if (!array_key_exists('emails', $json)) {
            /*

            {
                "message": "Your batch is being processed.",
                "processed": 1,
                "total": 2
            }

            */

            if ($waitCallback) {
                $waitCallback($raw);
            }

            return false;
        } else {
            // fetch results
            foreach ($json['emails'] as $emailRaw) {
                $email = $emailRaw['email'];
                $status = $this->getVerificationStatus($emailRaw);
                $doneCallback($email, $status, json_encode($emailRaw));
            }

            return true;
        }
    }

    private function getVerificationStatus($emailRaw)
    {
        if (!array_key_exists('state', $emailRaw)) {
            throw new Exception('The "state" value is not found: '.json_encode($emailRaw['Result']));
        }

        if (!array_key_exists($emailRaw['state'], $this->map)) {
            throw new Exception('Unknown status code (state) returned from MyEmailVerifier.com: '.$emailRaw['state']);
        }

        return $this->map[ $emailRaw['state'] ];
    }

    public function parseResult($response)
    {
        // Get raw response
        $raw = (string)$response->getBody();

        // Verify result
        if (empty($raw)) {
            throw new Exception('EMPTY RESPONSE FROM VERIFICATION SERVICE: emailable.com');
        }

        // Convert raw response into json
        $json = json_decode($raw, true);

        if (!array_key_exists('state', $json)) {
            if (array_key_exists('message', $json)) {
                throw new VerificationTakesLongerThanNormal($raw);
            } else {
                throw new Exception('Unexpected result from emailable.com: '.$raw);
            }
        }

        if (!array_key_exists($json['state'], $this->map)) {
            throw new Exception('Unexpected "state" value from emailable.com: '.$raw);
        }

        return $this->map[$json['state']];
    }

    public function isBulkVerifySupported(): bool
    {
        return true;
    }

    public function initRateLimitTracker()
    {
        $limits = [
            new RateLimit(15, 1, 'minute', '15 per minute limit'),
        ];

        // Uniq per API KEY
        $key = 'rate-tracker-log-myemailverifier-account-'.md5($this->apiKey);
        $tracker = new InMemoryRateTracker($key, $limits);
        $tracker->cleanup('24 hours');

        return $tracker;
    }

    public function getServiceName(): string
    {
        return 'Emailable';
    }

    public function getServiceUrl(): string
    {
        return 'https://emailable.com';
    }
}
