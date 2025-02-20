<?php

namespace Acelle\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Acelle\Model\Automation2;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Cache;

class UpdateAutomation extends Base implements ShouldBeUnique
{
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

        if ($this->automation->mailList()->exists()) {
            $this->automation->updateCache();
        }
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public $uniqueFor = 1200; // 20 minutes
    public function uniqueId()
    {
        return $this->automation->id;
    }

    public function uniqueVia()
    {
        return Cache::driver('file');
    }
}
