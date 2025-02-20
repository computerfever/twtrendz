<?php

namespace Acelle\Library\Everification;

use Exception;
use Acelle\Library\Contracts\VerifyInterface;

class EmailListValidation implements VerifyInterface
{
    protected $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function isBulkVerifySupported(): bool
    {
        return false;
    }

    public function verify($email)
    {
        $url = "https://app.emaillistvalidation.com/api/verifEmailv2?secret={$this->apiKey}&email={$email}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $rawResponse = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpcode != 200) {
            throw new Exception('Error working with emailvaliation.com server: '.$rawResponse);
        }

        $response = json_decode($rawResponse, true);

        if (array_key_exists('error', $response) && (!array_key_exists('success', $response) || $response['success'] != true)) {
            throw new Exception('Failed connecting with emailvaliation.com server: '.$rawResponse);
        }

        // See: https://help.emaillistvalidation.com/article/7-result-codes-and-terminology

        $statusMap = [
            'valid' => 'deliverable',
            'invalid' => 'undeliverable',
            'ok' => 'deliverable',
            'ok_for_all' => 'deliverable',
            'ok_for_all | ok_for_all' => 'deliverable',
            'ok_for_all|ok_for_all' => 'deliverable',
            'email_disabled' => 'undeliverable',
            'risky' => 'risky',
            'unknown' => 'unknown',
            'ok' => 'deliverable',
            'unknown_email' => 'undeliverable',
            'email_disabled' => 'undeliverable',
            'domain_error' => 'undeliverable',
            'spam traps' => 'risky',
            'spamtrap' => 'risky',
            'disposable' => 'deliverable',
            'accept all or Ok for all' => 'deliverable',
            'accept all' => 'deliverable',
            'ok for all' => 'deliverable',
            'syntax_error' => 'undeliverable',
            'invalid vendor response' => 'unknown',
            'dead_server' => 'undeliverable',
            'antispam_system' => 'unknown',
            'relay_error' => 'unknown',
            'attempt_rejected' => 'unknown',
            'smtp_protocol' => 'unknown',
            'smtp_error' => 'unknown',
            'error' => 'unknown',
            'ok or valid' => 'deliverable',
        ];

        if (array_key_exists('Result', $response)) {
            $result = strtolower($response['Result']);
        } elseif (array_key_exists('error', $response)) {
            $result = strtolower($response['error']);
        } else {
            throw new Exception('Unknown status code returned from EmailListValidation service: '.$rawResponse);
        }

        if (!array_key_exists($result, $statusMap)) {
            throw new Exception('Unexpected response from EmailListValidation service: '.$rawResponse);
        }

        $verificationStatus = $statusMap[$result];

        return [$verificationStatus, $rawResponse];
    }
}
