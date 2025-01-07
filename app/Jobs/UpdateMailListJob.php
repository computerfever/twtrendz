<?php

namespace Acelle\Jobs;

use Acelle\Model\MailList;
use Acelle\Model\Blacklist;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Cache;

class UpdateMailListJob extends Base implements ShouldBeUnique
{
    public $list;
    protected $customer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(MailList $list)
    {
        $this->list = $list;
        $this->customer = $this->list->customer;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->customer->setUserDbConnection();

        if (config('app.saas') && is_null($this->list->customer->getCurrentActiveGeneralSubscription())) {
            return;
        }

        $this->list->updateCachedInfo();

        // Limitation: cross-db join
        // blacklist new emails (if any)
        // Blacklist::doBlacklist($this->list->customer);
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public $uniqueFor = 1200; // 20 minutes
    public function uniqueId()
    {
        return $this->list->id;
    }
}
