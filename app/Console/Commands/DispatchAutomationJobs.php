<?php

namespace Acelle\Console\Commands;

use Illuminate\Console\Command;
use Acelle\Jobs\DispatchAutomationJobs as TheJob;

// @important: for unknown reasons, jobs cannot be dispatched inside a Console/Kernel.php
//             as a result, job dispatch should be placed in a command instead, then have the command executed in Kernel.php

class DispatchAutomationJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:dispatch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $job = (new TheJob())->onQueue(ACM_QUEUE_TYPE_AUTOMATION_DISPATCH);
        safe_dispatch($job);

        return 0;
    }
}
