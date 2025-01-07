<?php

namespace Acelle\Console\Commands;

use Illuminate\Console\Command;

class GenerateSupervisorWorker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supervisor:worker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Supervisor worker config file';

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
        echo \Acelle\Helpers\generate_supervisor_config($template = 'supervisor_worker_config.tmpl');
        return 0;
    }
}
