<?php

namespace Acelle\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteCampaignCallback implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $webhook;
    protected $log; # OpenLog | ClickLog | UnsubscribeLog
    protected $customer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($webhook, $log)
    {
        $this->webhook = $webhook;
        $this->log = $log;

        $this->customer = $this->webhook->customer;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->customer->setUserDbConnection();

        $this->webhook->execute($this->log);
    }
}
