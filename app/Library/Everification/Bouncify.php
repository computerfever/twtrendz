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

class Bouncify implements VerifyInterface, BulkVerifyInterface
{
    protected $apiKey;
    protected $resultMap = [
        'deliverable' => 'deliverable',
        'unknown' => 'unknown',
        'accept-all' => 'unknown', // Acelle-All means the domain's email addresses cannot be verified.
        'accept all' => 'unknown', // Acelle-All means the domain's email addresses cannot be verified.
        'undeliverable' => 'undeliverable',
    ];

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function verify($email)
    {
        $ch = curl_init();
        curl_setopt_array($ch, array(
          // Replace API_KEY with your API Key
          CURLOPT_URL => "https://api.bouncify.io/v1/verify?apikey={$this->apiKey}&email={$email}",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $rawResponse = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception("Something wrong while verifying with Bouncify: ".curl_error($ch));
        }

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (200 != $httpcode) {
            throw new Exception("Something wrong while verifying with Bouncify, response code is != 200: ".$rawResponse);
        }

        $response = json_decode($rawResponse, true);
        if (empty($response) || !array_key_exists('result', $response)) {
            throw new Exception("Something wrong while getting available credtis from Bouncify 'result' field is required: {$rawResponse}");
        }

        $mappedStatus = $this->resultMap[$response['result']] ?? null;

        if (is_null($mappedStatus)) {
            throw new Exception("Unknown status returned by Bouncify: {$response['result']}");
        }

        return [$mappedStatus, $rawResponse];
    }

    public function getCredits(): ?int
    {
        // check if progress is done

        $url = "https://api.bouncify.io/v1/info?apikey={$this->apiKey}";
        $ch = curl_init($url);

        // Set options for data retrieval
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Capture the response
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  // Follow redirects (optional)
        curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Accept: application/json' ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        // Execute the curl request

        $rawResponse = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception("Something wrong while getting available credtis from Bouncify: ".curl_error($ch));
        }

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (200 != $httpcode) {
            throw new Exception("Something wrong while getting available credtis from Bouncify: ".$rawResponse);
        }

        $response = json_decode($rawResponse, true);
        if (empty($response) || !array_key_exists('credits_info', $response)) {
            throw new Exception("Something wrong while getting available credtis from Bouncify 'credits_info' field is required: {$rawResponse}");
        }

        return (int)$response['credits_info']['credits_remaining'];
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
        $basePath = storage_path('app/verification/Bouncify/upload/');
        if (!file_exists($basePath)) {
            mkdir($basePath, 0777, $recursive = true);
        }

        $filepath = join_paths($basePath, md5($this->apiKey)."-".uniqid().".txt");

        // Write
        file_put_contents($filepath, implode(PHP_EOL, $subscriberQuery->pluck('email')->toArray()));

        try {
            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL => "https://api.bouncify.io/v1/bulk?apikey={$this->apiKey}",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array('local_file' => curl_file_create($filepath)),
            ));

            $rawResponse = curl_exec($ch); //return FILE ID to get file status and download url

            if (curl_errno($ch)) {
                throw new Exception('Something wrong connecting to Bouncify.io: '.curl_error($ch));
            }

            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if (200 != $httpcode && 201 != $httpcode) {
                throw new Exception('Something wrong from Bouncify.io: '.$rawResponse);
            }

            $response = json_decode($rawResponse, true);

            if (empty($response) || !array_key_exists('job_id', $response) || empty($response['job_id'])) {
                throw new Exception('Invalid response from Bouncify.io ("job_id" is required): '.$rawResponse);
            }

            // JOB
            $jobId = $response['job_id'];

            return $jobId;
        } finally {
            \Illuminate\Support\Facades\File::delete($filepath);
        }
    }

    private function startVerifyingList($jobId)
    {
        // Start
        $ch = curl_init();
        curl_setopt_array($ch, array(
            // Replace jobId with your list's jobId and replace API_KEY with your API Key, you need to start verification
            CURLOPT_URL => "https://api.bouncify.io/v1/bulk/{$jobId}?apikey={$this->apiKey}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => json_encode(["action" => "start"]),
            CURLOPT_HTTPHEADER => [ 'Accept: application/json', 'Content-Type: application/json' ]
        ));

        $rawResponse = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('Something wrong running job at Bouncify.io: '.curl_error($ch));
        }

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (200 != $httpcode && 201 != $httpcode) {
            throw new Exception('Something wrong from when start running a job Bouncify.io: '.$rawResponse);
        }

        $response = json_decode($rawResponse, true);

        if (empty($response) || !array_key_exists('success', $response) || $response['success'] == false) {
            throw new Exception('Failed to start verifying '.$rawResponse);
        }

        curl_close($ch);
    }

    public function initRateLimitTracker()
    {
        $limits = [
            new RateLimit(15, 1, 'minute', '15 per minute limit'),
        ];

        // Uniq per API KEY
        $key = 'rate-tracker-log-bouncify-account-'.md5($this->apiKey);
        $tracker = new InMemoryRateTracker($key, $limits);
        $tracker->cleanup('24 hours');

        return $tracker;
    }

    public function bulkCheck(string $jobId, Closure $doneCallback, Closure $waitCallback = null): bool
    {
        $ch = curl_init();

        curl_setopt_array($ch, array(
            // Replace jobId with your list's jobId and API_KEY with your API Key
            CURLOPT_URL => "https://api.bouncify.io/v1/bulk/{$jobId}?apikey={$this->apiKey}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $rawResponse = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception("Something wrong while checking job progress CURL ERROR (job ID#{$jobId}) from Bouncify: ".curl_error($ch));
        }

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (200 != $httpcode) {
            throw new Exception("Something wrong while checking job progress (job ID#{$jobId}) from Bouncify: ".$rawResponse);
        }

        $response = json_decode($rawResponse, true);
        if (empty($response) || !array_key_exists('status', $response)) {
            throw new Exception("Something wrong while checking job progress (job ID#{$jobId}) from Bouncify. Invalid response ('status' is required): {$rawResponse}");
        }

        if ($response['status'] == "completed") {
            $this->downloadResult($jobId, $doneCallback);
            return true;
        } elseif ($response['status'] == 'ready') {

            // @important: when list is "ready", start verifying (it does not start automatically like other services)

            $this->startVerifyingList($jobId);
            if ($waitCallback) {
                $waitCallback($rawResponse);
            }
            return false;
        } else {
            if ($waitCallback) {
                $total = $response['total'];
                $processed = $response['verified'] ?? 0;
                $waitCallback($rawResponse, $jobId, $total, $processed);
            }
            return false;
        }
    }

    private function downloadResult($jobId, Closure $callback)
    {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            // Replace API_KEY with your API Key and jobId with your list's jobId, you need to download
            CURLOPT_URL => "https://api.bouncify.io/v1/download?jobId={$jobId}&apikey={$this->apiKey}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
        ));

        $rawResponse = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception("Something wrong while fetching result (job ID#{$jobId}) from Bouncify: ".curl_error($ch));
        }

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (200 != $httpcode) {
            throw new Exception("Something wrong while fetching result (job ID#{$jobId}) from Bouncify: ".$rawResponse);
        }

        $basePath = storage_path('app/verification/Bouncify/');
        if (!file_exists($basePath)) {
            mkdir($basePath, 0777, $recursive = true);
        }

        $tmpResultFile = join_paths($basePath, md5($this->apiKey)."-".uniqid());
        file_put_contents($tmpResultFile, $rawResponse);

        list($headers, $count, $results) = read_csv($tmpResultFile, $hasHeaders = true, $ignoreEmptyHeader = true);

        /* Sample result

            [
                "col0" => "00002f34ab8c2b4d04a7@gmail.com",
                "Verification Result" => "undeliverable",
                ...
            ]
        */

        foreach ($results as $raw) {
            if (!array_key_exists($raw['Verification Result'], $this->resultMap)) {
                throw new Exception('Unknown status code returned from Bouncify: '.$raw['Verification Result']);
            }

            $email = $raw['col0'];
            $verificationResult = $this->resultMap[$raw['Verification Result']];
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
        return 'Bouncify.io';
    }

    public function getServiceUrl(): string
    {
        return 'https://bouncify.io/';
    }
}
