<?php

namespace Acelle\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Cache\Repository;

class UpdateSendingServerJob extends Base implements ShouldBeUnique
{
    public $server;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($server)
    {
        $this->server = $server;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->server->updateCache();
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public $uniqueFor = 1200; // 20 minutes
    public function uniqueId()
    {
        return $this->server->id;
    }

    /*
    public function uniqueVia(): Repository
    {
        return Cache::driver('file');
    }
    */
}
