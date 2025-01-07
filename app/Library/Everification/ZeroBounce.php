<?php

namespace Acelle\Library\Everification;

use Exception;
use ZeroBounce\SDK\ZeroBounce as ZeroBounceApi;
use Closure;
use Acelle\Library\Contracts\VerifyInterface;
use Acelle\Library\Contracts\BulkVerifyInterface;
use Illuminate\Database\Eloquent\Builder;
use Acelle\Library\InMemoryRateTracker;
use Acelle\Library\RateLimit;

use function Acelle\Helpers\read_csv;
use function Acelle\Helpers\execute_with_limits;

class ZeroBounce implements VerifyInterface, BulkVerifyInterface
{
    protected $apiKey;

    protected $resultMap = [
        'valid' => 'deliverable',
        'invalid' => 'undeliverable',
        'catch-all' => 'deliverable',
        'unknown'    => 'unknown',
        'spamtrap' => 'risky',
        'abuse' => 'risky', // really?
        'do_not_mail' => 'risky', // really?
    ];

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getCredits(): ?int
    {
        ZeroBounceApi::Instance()->initialize($this->apiKey);
        $response = ZeroBounceApi::Instance()->getCredits();
        $credits = (int)$response->credits;

        if ($credits < 0) { // sometimes emailable.com returns a negative number
            return 0;
        } else {
            return $credits;
        }
    }

    public function initRateLimitTracker()
    {
        $limits = [
            new RateLimit(15, 1, 'minute', '15 per minute limit. See more ZeroBounce limits at: https://www.zerobounce.net/docs/api-dashboard/api-rate-limits/'),
        ];

        // Uniq per API KEY
        $key = 'rate-tracker-log-zerobounce-account-'.md5($this->apiKey);
        $tracker = new InMemoryRateTracker($key, $limits);
        $tracker->cleanup('24 hours');

        return $tracker;
    }


    public function verify($email)
    {
        // See list of test email addresses at:
        // https://www.zerobounce.net/docs/email-validation-api-quickstart/#sandbox_mode__v2__
        ZeroBounceApi::Instance()->initialize($this->apiKey);
        $response = ZeroBounceApi::Instance()->validate($email, $ip = null);

        // It is stupid that ZB returns null if API is invalid
        // It should throw an exception instead
        if (empty($response->status)) {
            throw new Exception('Invalid ZeroBounce API KEY');
        }

        if (!array_key_exists(strtolower($response->status), $this->resultMap)) {
            throw new Exception('Unknown status code returned from ZeroBounce: '.$response->status);
        }

        $verificationStatus = $this->resultMap[strtolower($response->status)];
        $rawResponse = $response->__toString();

        return [$verificationStatus, $rawResponse];
    }

    public function isBulkVerifySupported(): bool
    {
        return true;
    }

    // INTERFACE
    public function submit(Builder $subscriberQuery)
    {
        $basePath = storage_path('app/verification/ZeroBounce/upload/');
        if (!file_exists($basePath)) {
            mkdir($basePath, 0777, $recursive = true);
        }

        $filepath = join_paths($basePath, md5($this->apiKey)."-".uniqid().'.txt');

        // Write
        file_put_contents($filepath, implode(PHP_EOL, $subscriberQuery->pluck('email')->toArray()));

        ZeroBounceApi::Instance()->initialize($this->apiKey);

        try {
            $response = ZeroBounceApi::Instance()->sendFile(
                $filepath,                  // The csv or txt file
                $emailAddressColumn = 1,    // The column index of the email address in the file. Index starts at 1
                // The URL will be used as a callback after the file is sent (not needed)
                $hasHeadersRow = false   // If the first row from the submitted file is a header row. True or False
            );

            if (!$response->success) {
                throw new Exception("Cannot send file {$filepath}. Error: ".$response->__toString());
            }

            $fileId = $response->fileId;    // e.g. "aaaaaaaa-zzzz-xxxx-yyyy-5003727fffff"

        } finally {
            \Illuminate\Support\Facades\File::delete($filepath);
        }

        return $fileId;
    }

    public function bulkSubmit(Builder $subscriberQuery): string
    {
        // Likely to throw RateLimitExceeded
        $fileId = execute_with_limits([$this->initRateLimitTracker()], $pool = null, $creditTrackers = [], function () use ($subscriberQuery) {
            return $this->submit($subscriberQuery);
        });

        return $fileId;
    }

    public function bulkCheck(string $token, Closure $doneCallback, Closure $waitCallback = null): bool
    {
        ZeroBounceApi::Instance()->initialize($this->apiKey);

        $fileId = $token;   // The file ID received from "sendFile" response
        $response = ZeroBounceApi::Instance()->fileStatus($fileId);
        $status = $response->fileStatus;    // e.g. "Complete"

        if ('Complete' == $status) {
            $this->downloadFile($fileId, $doneCallback);
            return true;
        } elseif ('Deleted' == $status) {
            throw new Exception("File #{$fileId} was already deleted. ".$response->__toString());
        } else {
            if ($waitCallback) {
                $waitCallback($response->__toString());
            }
            return false;
        }
    }

    private function downloadFile($fileId, Closure $callback)
    {
        // File name looks like: c98645cbae77b49513efde4aa04fb942-669394a1d9b4e
        $basePath = storage_path('app/verification/MyEmailVerifier/');
        if (!file_exists($basePath)) {
            mkdir($basePath, 0777, $recursive = true);
        }
        $downloadPath = join_paths($basePath, md5($this->apiKey)."-".uniqid());

        /** @var $response ZeroBounse\SDK\ZBGetFileResponse */
        ZeroBounceApi::Instance()->initialize($this->apiKey);
        $response = ZeroBounceApi::Instance()->getFile($fileId, $downloadPath);

        $localPath = $response->localFilePath;

        list($headers, $count, $results) = read_csv($localPath, $hasHeaders = true, $ignoreEmptyHeader = true);

        /* Sample result

            [
                "Email Address" => "nghi@b-teka.com",
                "ZB Status" => "invalid",
            ]
        */

        foreach ($results as $raw) {
            $email = $raw['Email Address'];

            if (!array_key_exists($raw['ZB Status'], $this->resultMap)) {
                throw new Exception('Unknown status code returned from ZeroBounce: '.$raw['ZB Status']);
            }

            $verificationResult = $this->resultMap[$raw['ZB Status']];
            $callback($email, $verificationResult, json_encode($raw));
        }

        /*
        // Clean up
        $response = ZeroBounceApi::Instance()->deleteFile($fileId);
        if (!$response->success) {
            throw new Exception('Cannot delete ZeroBounce file: '.$fileId);
        }
        */
    }

    public function getServiceName(): string
    {
        return 'ZeroBounce';
    }

    public function getServiceUrl(): string
    {
        return 'https://zerobounce.net';
    }
}
