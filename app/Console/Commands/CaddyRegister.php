<?php

namespace Acelle\Console\Commands;

use Illuminate\Console\Command;
use Acelle\Model\TrackingDomain;

class CaddyRegister extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caddy:register';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get all tracking domains and write config files';

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
        $this->withAutoSslTrackingDomains(function ($customer, $domains) {
            foreach ($domains as $domain) {
                $this->createCaddyConfigFile($domain->name);
            }
        });

        return 0;
    }

    private function createCaddyConfigFile($name)
    {
        $path = join_paths(config('caddy.config_dir'), "$name");
        $proxy = config('caddy.reverse_proxy');
        $adminEmail = config('caddy.admin_email_address');
        $config = <<<END
{$name} {
       tls {$adminEmail}
       reverse_proxy {$proxy}
}
END;
        if (!file_exists($path)) {
            file_put_contents($path, $config);
        }
    }

    private function withAutoSslTrackingDomains($callback)
    {
        $customers = \Acelle\Model\Customer::all();
        foreach ($customers as $customer) {
            $customer->setUserDbConnection();
            $domains = $customer->local()->trackingDomains()->autoSsl()->get();
            $callback($customer, $domains);
        }
    }
}
