<?php

namespace Acelle\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Acelle\Model\Customer;

class QueueReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Report queue statistics';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $print = function ($queue) {
            $waiting = Redis::llen("queues:{$queue}");
            $delayed = Redis::zcount("queues:{$queue}:delayed", '-inf', '+inf');
            $reserved = Redis::zcount("queues:{$queue}:reserved", '-inf', '+inf');
            $queueinfo = sprintf('%s%s%s%s', str_pad($queue, 25, ' '), str_pad($waiting, 10, ' ', STR_PAD_LEFT), str_pad($delayed, 10, ' ', STR_PAD_LEFT), str_pad($reserved, 10, ' ', STR_PAD_LEFT));
            echo $queueinfo."\n";
        };

        $headers = sprintf('%s%s%s%s', str_pad('', 25), str_pad('Waiting', 10, ' ', STR_PAD_LEFT), str_pad('Delayed', 10, ' ', STR_PAD_LEFT), str_pad('Reserved', 10, ' ', STR_PAD_LEFT));
        echo $headers."\n";
        echo "-------------------------------------------------------\n";

        $defaultQueues = ['default', 'high', 'import', 'automation-dispatch', 'automation', 'batch', 'single'];

        foreach ($defaultQueues as $queue) {
            $print($queue);
        }
        echo "-------------------------------------------------------\n";

        $customQueues = Customer::whereNotNull('custom_queue_name')->select('custom_queue_name')->distinct()->pluck('custom_queue_name')->toArray();

        foreach ($customQueues as $queue) {
            $print($queue);
        }
        echo "-------------------------------------------------------\n";

        $failedJobs = \Acelle\Model\FailedJob::count();
        if ($failedJobs > 0) {
            echo sprintf("Failed job(s): %s\n", $failedJobs);
        }

        return 0;
    }
}
