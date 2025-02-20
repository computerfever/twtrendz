<?php

namespace Acelle\Console\Commands;

use Illuminate\Console\Command;

class GenerateSupervisorMaster extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supervisor:master';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Supervisor master configuration file';

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
        \Acelle\Helpers\generate_supervisor_config($template = 'supervisor_master_config.tmpl');
        return 0;
    }
}
