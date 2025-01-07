<?php

namespace Acelle\Library\Everification;

use Exception;
use Acelle\Library\Contracts\VerifyInterface;
use Acelle\Library\Contracts\BulkVerifyInterface;
use Illuminate\Database\Eloquent\Builder;
use GuzzleHttp\Client as GuzzleClient;
use Closure;

class NerverBounce implements VerifyInterface, BulkVerifyInterface
{
    protected $apiKey;
    protected $map = [
        'valid' => 'deliverable',
        'invalid' => 'undeliverable',
        'disposable' => 'risky',
        'accept all' => 'risky',
        'unverifiable' => 'risky',
        'unknown' => 'unknown',
    ];

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;

        \NeverBounce\Auth::setApiKey($this->apiKey);
    }

    public function getCredits(): ?int
    {
        // Make request for API info
        $info = \NeverBounce\Account::info();

        // Dump account info
        return ($info->credits_info['paid_credits_remaining'] + $info->credits_info["free_credits_remaining"]);
    }

    public function verify($email)
    {
        // Verify a single email
        $verification = \NeverBounce\Single::check($email, true, true);
        $result = [];

        // Get verified email
        $result['email'] = ('Email Verified: ' . $verification->email);

        // Get numeric verification result
        $result['result_integer'] = ('Numeric Code: ' . $verification->result_integer);

        // Get text based verification result
        $result['result'] = ('Text Code: ' . $verification->result);

        // Check for dns flag
        $result['has_dns'] = ('Has DNS: ' . (string) $verification->hasFlag('has_dns'));

        // Check for free_email_host flag
        $result['free_email_host'] = ('Is free mail: ' . (string) $verification->hasFlag('free_email_host'));

        // Get numeric verification result
        $result['suggested_correction'] = ('Suggested Correction: ' . $verification->suggested_correction);

        // Check if email is valid
        $result['Is unknown'] = ('Is unknown: ' . (string) $verification->is('unknown'));

        // Get numeric verification result
        $result['Isn\'t valid or catchall'] = ('Isn\'t valid or catchall: ' . (string) $verification->not(['valid', 'catchall']));

        // Get credits used
        $credits = ($verification->credits_info->paid_credits_used
            + $verification->credits_info->free_credits_used);
        var_dump('Credits used: ' . $credits);

        return [$this->map[$verification->result], (string) json_encode($result)];
    }

    public function bulkSubmit(Builder $subscriberQuery): string
    {
        $json = $subscriberQuery->select('id', 'email')->get()->map(function ($sub) {
            return [
                'id' => $sub->id,
                'email' => $sub->email,
            ];
        })->toArray();

        // Get status from specific job
        $job = \NeverBounce\Jobs::create(
            $json,
            \NeverBounce\Jobs::SUPPLIED_INPUT,
            'Created from Array.csv',
            false,
            true,
            true
        );

        return ($job->job_id);
    }

    public function bulkCheck(string $batchId, Closure $doneCallback, Closure $waitCallback): bool
    {
        // Get status from specific job
        $job = \NeverBounce\Jobs::status($batchId);
        $result = [
            'status' => $job->status,
            'id' => $job->id,
            'created_at' => $job->created_at,
            'started_at' => $job->started_at,
            'total' => $job->total,
            'percent_complete' => $job->percent_complete,
        ];

        if ($job->status != 'success') {
            if ($waitCallback) {
                $waitCallback(json_encode($result));
            }

            return false;
        } else {
            // fetch result
            $job = \NeverBounce\Jobs::results($batchId);

            foreach ($job->results as $result) {
                // var_dump($result[0], $result[1]);
                $email = $result["data"]['email'];
                $status = $this->map[$result['verification']->result];
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
        return 'NerverBounce.com';
    }

    public function getServiceUrl(): string
    {
        return 'https://www.neverbounce.com';
    }
}
