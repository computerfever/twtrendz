<?php

namespace Acelle\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ForceTriggerAutomation implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $automation;
    protected $customer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($automation)
    {
        $this->automation = $automation;
        $this->customer = $this->automation->customer;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->customer->setUserDbConnection();
        $this->automation->forceTrigger();
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public $uniqueFor = 60 * 60 * 24 * 30; // 30 days
    public function uniqueId()
    {
        // a fixed id, making sure only one job in queue at any given time
        return $this->automation->id;
    }
}
