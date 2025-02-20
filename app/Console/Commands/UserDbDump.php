<?php

namespace Acelle\Console\Commands;

use Illuminate\Console\Command;

class UserDbDump extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'userdb:dump {customer-id} {dumpfile}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize user database. That is, run migration against the specified connection to set up the database schema';

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
        $customerId = $this->argument('customer-id');
        $dumpfile = $this->argument('dumpfile');

        $dirname = dirname($dumpfile);

        if (!file_exists($dirname)) {
            throw new \Exception("Directory '$dirname' does not exist!");
        }

        echo "Exporting...\n";

        // Get the list of UserDB's table, except for 'subscribers' which will be treated specially
        $tables = array_values(array_diff(config('userdb_tables'), ['subscribers']));
        $tables = implode(
            ' ',
            array_map(function ($table) {
                return \DB::getTablePrefix().$table;
            }, $tables)
        );

        $masterDb = 'mysql';
        $connectionInfo = config("database.connections.{$masterDb}");

        $fullCmd = "mysqldump --no-create-info --complete-insert {$connectionInfo['database']} {$tables} --where='customer_id={$customerId}' \
            -u{$connectionInfo['username']} \
            -h{$connectionInfo['host']} \
            -P{$connectionInfo['port']} \
            -p{$connectionInfo['password']} 2>&1 > {$dumpfile}";
        $result = exec($fullCmd, $output, $return);

        if ($return != 0) {
            throw new \Exception("Dump faield: {$result}");
        }

        $subscribersTable = \DB::getTablePrefix()."subscribers";
        $mailListsTable = \DB::getTablePrefix()."mail_lists";

        $fullCmd = "mysqldump --single-transaction --no-create-info --complete-insert {$connectionInfo['database']} $subscribersTable --where='mail_list_id IN (SELECT id FROM {$mailListsTable} WHERE customer_id = {$customerId})' \
            -u{$connectionInfo['username']} \
            -h{$connectionInfo['host']} \
            -P{$connectionInfo['port']} \
            -p{$connectionInfo['password']} 2>&1 >> {$dumpfile}"; // Notice the ">>" sign
        $result = exec($fullCmd, $output, $return);

        if ($return != 0) {
            throw new \Exception("Dump subscribers faield: {$result}");
        }

        echo "+ Done. File written to {$dumpfile}\n";

        return 0;
    }
}
