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
use Closure;
use Carbon\Carbon;
use Acelle\Library\Exception\RateLimitExceeded;

class LoadCampaign implements ShouldQueue
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
    protected $page;
    protected $listOfIds;
    protected $customer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(CampaignInterface $campaign, int $page, array $listOfIds)
    {
        $this->campaign = $campaign;
        $this->page = $page;
        $this->listOfIds = $listOfIds;
        $this->customer = $this->campaign->customer;

        //// DO NOT SET CONNECTION HERE (IT IS EXECUTED IN A LOOP INSIDE BaseCampaign::run() )
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Might cause problem with batches
        // $this->customer->setUserDbConnection();

        if ($this->batch()->cancelled()) {
            return;
        }

        // Last update recording should go here
        // before any other tasks (to prevent IO blocking tasks)
        // In case we need to clean up pending jobs, at least we know that last job start time
        // Reduce the posibility of killing a newly started (and still running) job
        $this->campaign->debug(function ($info) {
            // Record last activity, no matter it is a successful delivery or exception
            // This information is useful when we want to audit delivery processes
            // i.e. when we can to automatically restart dead jobs for example
            $info['last_activity_at'] = Carbon::now()->toString();

            // Must return;
            return $info;
        });

        $count = 0;
        $total = sizeof($this->listOfIds);

        $this->campaign->logger()->info(sprintf('LoadCampaign: loading contacts for page %s (#%s)', $this->page, $total));

        try {
            $this->campaign->loadDeliveryJobsByIds(function (ShouldQueue $deliveryJob) use (&$count, $total) {
                $this->batch()->add($deliveryJob);

                $count += 1;
                $this->campaign->logger()->info(sprintf("LoadCampaign: job loaded %s/%s", $count, $total));

                $delayFlag = $this->campaign->checkDelayFlag();
                if ($delayFlag) {
                    $this->campaign->logger()->info(sprintf("Rate limit hit! Quit loading jobs at %s/%s", $count, $total));

                    throw new RateLimitExceeded('Stop loading jobs');
                }
            }, $this->page, $this->listOfIds);
        } catch (RateLimitExceeded $ex) {
            // just do nothing
        }
    }
}
