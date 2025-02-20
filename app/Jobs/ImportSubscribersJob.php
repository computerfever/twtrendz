<?php

namespace Acelle\Jobs;

use Acelle\Library\Traits\Trackable;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Exception;

class ImportSubscribersJob extends Base
{
    use Trackable;

    /* Already specified by Base job
     *
     *     public $failOnTimeout = true;
     *     public $tries = 1;
     *
     */

    public $timeout = 259200; // 3 days

    // @todo this should better be a constant
    protected $list;
    protected $file; // Example: /home/acelle/storage/app/tmp/import-000000.csv
    protected $map;  // { "First Name" => 5, "LAST_NAME" => 6, "email" => 4, "city" => 16 }
    protected $customer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($list, $file, $map)
    {
        $this->list = $list;
        $this->file = $file;
        $this->map = $map;
        $this->customer = $this->list->customer;

        // Set the initial value for progress check
        $this->afterDispatched(function ($thisJob, $monitor) {
            $monitor->setJsonData([
                'percentage' => 0,
                'total' => 0,
                'processed' => 0,
                'failed' => 0,
                'message' => trans('messages.list.import.queued.msg'),
                'logfile' => null,
            ]);
        });
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->customer->setUserDbConnection();

        \App::setLocale($this->list->customer->language->code);
        \Carbon\Carbon::setLocale($this->list->customer->language->code);

        // Use a logger to log failed
        $formatter = new LineFormatter("[%datetime%] %channel%.%level_name%: %message%\n");
        $logfile = $this->file.".log";
        $stream = new StreamHandler($logfile, Logger::DEBUG);
        $stream->setFormatter($formatter);

        $pid = getmypid();
        $logger = new Logger($pid);
        $logger->pushHandler($stream);

        $this->monitor->updateJsonData([
            'logfile' => $logfile,
        ]);

        // Write log, to make sure the file is created
        $logger->info('Initiated');

        // File path:
        $this->list->import(
            $this->file,
            $this->map,
            $update = true,
            function ($processed, $total, $failed, $message) use ($logger) {
                $percentage = ($total && $processed) ? (int)($processed * 100 / $total) : 0;

                $this->monitor->updateJsonData([
                    'percentage' => $percentage,
                    'total' => $total,
                    'processed' => $processed,
                    'failed' => $failed,
                    'message' => $message,
                ]);

                // Write log, to make sure the file is created
                $logger->info($message);
                $logger->info(sprintf('Processed: %s/%s, Skipped: %s', $processed, $total, $failed));
            },
            function ($invalidRecord, $error) use ($logger) {
                $logger->warning('Invalid record: [' . implode(",", array_values($invalidRecord)) . "] | Validation error: " . implode(";", $error));
            }
        );

        $logger->info('Finished');
    }
}
