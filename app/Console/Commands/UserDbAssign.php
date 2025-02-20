<?php

namespace Acelle\Console\Commands;

use Illuminate\Console\Command;

class UserDbAssign extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'userdb:assign {customer_id} {connection} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize a customer from Master DB to a local DB';

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
        $customerId = $this->argument('customer_id');
        $customer = \Acelle\Model\Customer::find($customerId);

        if (is_null($customer)) {
            throw new \Exception("Cannot find customer with ID #{$customerId}");
        }

        $force = $this->options()['force'];

        if ($customer->hasLocalDb() && !$force) {
            throw new \Exception("Customer already have a local db: '".$customer->db_connection."'. Use --force to force assign a new connection");
        }

        $customer->db_connection = $this->argument('connection');
        $customer->save();

        echo "Assigned customer #{$customer->id} to connection '{$customer->db_connection}'!\n";

        echo "+ Done\n";

        return 0;
    }
}
