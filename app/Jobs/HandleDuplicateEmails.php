<?php

namespace Acelle\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Acelle\Library\Contracts\CampaignInterface;
use Acelle\Library\Traits\Trackable;
use Acelle\Model\TrackingLog;

class HandleDuplicateEmails implements ShouldQueue
{
    use Trackable;
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    // @important: set the "retry_after" setting in config/queue.php to a value that is greater than $timeout;
    public $timeout = 86400; // need time to dispatch hundreds of jobs
    public $failOnTimeout = true;
    public $tries = 1;
    public $maxExceptions = 1;

    protected CampaignInterface $campaign;
    protected $customer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(CampaignInterface $campaign)
    {
        $this->campaign = $campaign;
        $this->customer = $this->campaign->customer;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->customer->setUserDbConnection();

        /*
        if ($this->batch()->cancelled()) {
            return;
        }
        */

        $duplicateTable = $this->campaign->getDuplicateTable();

        // get a random sending server
        // throw an exception if campaign does not have one
        $server = $this->campaign->pickSendingServer();

        $excluded = $this->campaign->subscribersToSend()->leftJoin($duplicateTable, function ($join) use ($duplicateTable) {
            $join->on("{$duplicateTable}.email", '=', 'subscribers.email');
        })->whereNotNull("{$duplicateTable}.email")->where("{$duplicateTable}.selected_id", '!=', \DB::raw('subscribers.id'))->get();

        foreach ($excluded as $subscriber) {
            $this->campaign->trackMessage(
                [
                    'runtime_message_id' => null,
                    'status' => TrackingLog::STATUS_DUPLICATE,
                ],
                $subscriber,
                $server,
                $msgId = null,
                $trigger = null,
            );

            $this->campaign->logger()->info("Subscriber {$subscriber->email} duplicate, recorded in delivery log as 'duplicate'");
        }
    }
}
