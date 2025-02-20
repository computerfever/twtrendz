<?php

namespace Acelle\Library\Everification;

use Exception;
use Acelle\Library\Contracts\VerifyInterface;
use Acelle\Library\Contracts\BulkVerifyInterface;
use Illuminate\Database\Eloquent\Builder;
use GuzzleHttp\Client as GuzzleClient;
use Closure;

class EmailListVerify implements VerifyInterface, BulkVerifyInterface
{
    protected $apiKey;
    protected $map = [
        'ok' => 'deliverable',
        'ok_for_all' => 'deliverable',
        'email_disabled' => 'undeliverable',
        'dead_server' => 'undeliverable',
        'invalid_mx' => 'undeliverable',
        'invalid_syntax' => 'undeliverable',
        'disposable' => 'risky',
        'spamtrap' => 'risky',
        'smtp_protocol' => 'unknown',
        'antispam_system' => 'unknown',
        'unknown' => 'unknown',
    ];

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getCredits(): ?int
    {
        $email = "test@example.com";
        $key = $this->apiKey;
        $url = "https://apps.emaillistverify.com/api/getCredit?secret=".$key;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public function verify($email)
    {
        $key = $this->apiKey;
        $url = "https://apps.emaillistverify.com/api/verifyEmail?secret=".$key."&email=".$email;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return [$this->map[$response], (string) json_encode($response)];
    }

    public function bulkSubmit(Builder $subscriberQuery): string
    {
        $key = $this->apiKey;
        $filename = "bulkVerify_" . uniqid() . ".txt";
        $emails = $subscriberQuery->pluck('email')->toArray();
        $filePath = '/tmp/' . $filename;

        // header
        array_unshift($emails, 'Email');

        // Open the file in append mode
        $file = fopen($filePath, 'a');

        // Write each email to a new line
        foreach ($emails as $email) {
            fwrite($file, $email . "\n");
        }

        // Close the file
        fclose($file);

        $url = 'https://api.emaillistverify.com/api/verifyApiFile?secret=' . $key.'&filename=my_emails.txt';

        $curl = curl_init();

        // Set the options for the cURL request
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'accept: text/html',
                'Content-Type: multipart/form-data',
                "Authorization: Bearer $key",
            ],
            CURLOPT_POSTFIELDS => [
                'key' => $key,
                'file_contents' => new \CURLFile($filePath, 'text/plain'),
                'quality' => 'high',
            ],
        ]);

        // Execute the request and capture the response
        $response = curl_exec($curl);

        // Check for errors
        if (curl_errno($curl)) {
            echo 'cURL Error: ' . curl_error($curl);
        } else {
            echo 'Response: ' . $response;
        }

        // Close the cURL session
        curl_close($curl);

        return $response;
    }

    public function bulkCheck(string $batchId, Closure $doneCallback, Closure $waitCallback): bool
    {
        // 1438667
        $key = $this->apiKey;
        $url = 'https://apps.emaillistverify.com/api/getApiFileInfo?secret='.$key.'&id='.$batchId;
        $string = file_get_contents($url);
        list($file_id, $filename, $unique, $lines, $lines_processed, $status, $timestamp, $link1, $link2) = explode('|', $string); //parse data

        $data = [$file_id,$filename,$unique,$lines,$lines_processed,$status,$timestamp,$link1,$link2];

        if ($status != 'finished') {
            if ($waitCallback) {
                $waitCallback(json_encode($data));
            }

            return false;
        } else {
            // fetch result
            $url = $link2;

            // Open the file and read the contents into an array
            if (($handle = fopen($url, 'r')) !== false) {
                $csvData = [];
                while (($row = fgetcsv($handle)) !== false) {
                    $csvData[] = $row;
                }
                fclose($handle);

                // Remove the first element
                array_shift($csvData);

                // Output the array
                print_r($csvData);
            } else {
                throw new \Exception('Failed to open the URL: ' . $link2);
            }

            foreach ($csvData as $result) {
                // var_dump($result[0], $result[1]);
                $email = $result[1];
                $status = $this->map[$result[0]];
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
        return 'EmailListVerify';
    }

    public function getServiceUrl(): string
    {
        return 'https://apps.emaillistverify.com';
    }
}
