<?php

namespace Acelle\Library\Everification;

use Exception;
use Closure;
use Acelle\Library\Contracts\VerifyInterface;
use Acelle\Library\Contracts\BulkVerifyInterface;
use Illuminate\Database\Eloquent\Builder;
use Acelle\Library\InMemoryRateTracker;
use Acelle\Library\RateLimit;

use function Acelle\Helpers\curl_download;
use function Acelle\Helpers\read_csv;
use function Acelle\Helpers\execute_with_limits;

class MyEmailVerifier implements VerifyInterface, BulkVerifyInterface
{
    protected $apiKey;
    protected $resultMap = [
        'Valid' => 'deliverable',
        'Unknown' => 'unknown',
        'Invalid' => 'undeliverable',
        'Catch-all' => 'unknown',
        'Grey-listed' => 'unknown',
        '' => 'unknown',
    ];

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function verify($email)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://client.myemailverifier.com/verifier/validate_single/{$email}/{$this->apiKey}");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Capture the response
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  // Follow redirects (optional)
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $rawResponse = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception("Something wrong connecting to MyEmailVerifier.com: ".curl_error($ch));
        }

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (200 != $httpcode) {
            throw new Exception("Something wrong while verifying with MyEmailVerifier.com (response code != 200): ".$rawResponse);
        }

        $response = json_decode($rawResponse, true);
        if (empty($response) || !array_key_exists('Status', $response)) {
            throw new Exception("Something wrong while verifying with MyEmailVerifier.com: 'Status' field is required: {$rawResponse}");
        }

        $mappedStatus = $this->mapResult($response['Status']);

        return [$mappedStatus, $rawResponse];
    }

    private function mapResult($status)
    {
        $mappedStatus = $this->resultMap[$status] ?? null;

        if (is_null($mappedStatus)) {
            throw new Exception("Unknown status returned by MyEmailVerifier: {$status}");
        }

        return $mappedStatus;
    }


    public function getCredits(): ?int
    {
        // check if progress is done
        $url = "https://client.myemailverifier.com/verifier/getcredits/{$this->apiKey}";

        $ch = curl_init($url);

        // Set options for data retrieval
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Capture the response
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  // Follow redirects (optional)
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // Execute the curl request

        $rawResponse = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception("Something wrong while getting available credtis from MyEmailVerifier.com: ".curl_error($ch));
        }

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (200 != $httpcode) {
            throw new Exception("Something wrong while getting available credtis from MyEmailVerifier.com: ".$rawResponse);
        }

        $response = json_decode($rawResponse, true);
        if (empty($response) || !array_key_exists('credits', $response)) {
            throw new Exception("Something wrong while getting available credtis from MyEmailVerifier.com 'credits' field is required: {$rawResponse}");
        }

        return (int)$response['credits'];
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
        $basePath = storage_path('app/verification/MyEmailVerifier/upload/');
        if (!file_exists($basePath)) {
            mkdir($basePath, 0777, $recursive = true);
        }

        $filepath = join_paths($basePath, md5($this->apiKey)."-".uniqid().".txt");

        // Write
        file_put_contents($filepath, implode(PHP_EOL, $subscriberQuery->pluck('email')->toArray()));

        try {
            $data = [
              'filename' => curl_file_create($filepath),
              'api_key' => $this->apiKey,
            ];

            $url = 'https://client.myemailverifier.com/verifier/upload_file';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $rawResponse = curl_exec($ch); //return FILE ID to get file status and download url
            if (curl_errno($ch)) {
                throw new Exception('Something wrong connecting to MyEmailVerifier.com: '.curl_error($ch));
            }

            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if (200 != $httpcode) {
                throw new Exception('Something wrong from MyEmailVerifier.com: '.$rawResponse);
            }

            $response = json_decode($rawResponse, true);

            if (empty($response) || !array_key_exists('file_id', $response) || empty($response['file_id'])) {
                throw new Exception('Invalid response from MyEmailVerifier.com ("file_id" is required): '.$rawResponse);
            }

            return $response['file_id'];
        } finally {
            \Illuminate\Support\Facades\File::delete($filepath);
        }
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

    public function bulkCheck(string $token, Closure $doneCallback, Closure $waitCallback = null): bool
    {
        // check if progress is done
        $url = "https://client.myemailverifier.com/verifier/file_info/{$this->apiKey}/{$token}";

        $ch = curl_init($url);

        // Set options for data retrieval
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Capture the response
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  // Follow redirects (optional)
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // Execute the curl request

        $rawResponse = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception("Something wrong while fetching result (file ID#{$token}) from MyEmailVerifier.com: ".curl_error($ch));
        }

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (200 != $httpcode) {
            throw new Exception("Something wrong while fetching result (file ID#{$token}) from MyEmailVerifier.com: ".$rawResponse);
        }

        $response = json_decode($rawResponse, true);
        if (empty($response) || !array_key_exists('ready_for_download', $response)) {
            throw new Exception("Something wrong while fetching result (file ID#{$token}) from MyEmailVerifier.com. Invalid response ('ready_for_download' is required): {$rawResponse}");
        }

        if ($response['ready_for_download'] == "1") {
            if (!array_key_exists('file_path', $response)) {
                throw new Exception("Something wrong while fetching result (file ID#{$token}) from MyEmailVerifier.com. No 'file_path' found: {$rawResponse}");
            }

            $this->downloadResult($response, $doneCallback);
            return true;
        } else {
            if ($waitCallback) {
                $total = $response['total'];
                $processed = $response['credit_used'] ?? 0;
                $waitCallback($rawResponse, $token, $total, $processed);
            }
            return false;
        }
    }

    private function downloadResult($response, Closure $callback)
    {
        // File name looks like: c98645cbae77b49513efde4aa04fb942-669394a1d9b4e

        $basePath = storage_path('app/verification/MyEmailVerifier/');
        if (!file_exists($basePath)) {
            mkdir($basePath, 0777, $recursive = true);
        }

        $tmpResultFile = join_paths($basePath, md5($this->apiKey)."-".uniqid());

        try {
            curl_download($response['file_path'], $tmpResultFile);
        } catch (Exception $ex) {
            throw new Exception('Cannot download result from MyEmailVerifier: '.$ex->getMessage());
        }

        list($headers, $count, $results) = read_csv($tmpResultFile, $hasHeaders = true, $ignoreEmptyHeader = true);

        /* Sample result

            [
                "" => "user@example.com", // or somecase "Email" => 'user@example.com'
                "Result" => "Valid",
                "RoleBased" => "false",
                "FreeDomain" => "false",
                "Diagnosis" => "Valid mailbox",
            ]
        */

        foreach ($results as $raw) {
            if (!array_key_exists($raw['Result'], $this->resultMap)) {
                throw new Exception('Unknown status code returned from MyEmailVerifier.com: '.$raw['Result']);
            }

            $email = $raw['Email'] ?? $raw['Email'];
            $verificationResult = $this->resultMap[$raw['Result']];
            $callback($email, $verificationResult, json_encode($raw));
        }

        \Illuminate\Support\Facades\File::delete($tmpResultFile);
    }

    public function isBulkVerifySupported(): bool
    {
        return true;
    }

    public function getServiceName(): string
    {
        return 'myEmailVerifier';
    }

    public function getServiceUrl(): string
    {
        return 'https://myemailverifier.com';
    }
}
