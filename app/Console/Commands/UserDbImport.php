<?php

namespace Acelle\Console\Commands;

use Illuminate\Console\Command;

class UserDbImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'userdb:import {connection} {inputfile}';

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
        $connection = $this->argument('connection');
        $inputfile = $this->argument('inputfile');

        if (!file_exists($inputfile)) {
            throw new \Exception("File '$inputfile' does not exist!");
        }

        echo "Importing...\n";

        $connectionInfo = config("database.connections.{$connection}");
        $fullCmd = "mysql -u{$connectionInfo['username']} -h{$connectionInfo['host']} -P{$connectionInfo['port']} {$connectionInfo['database']} -p{$connectionInfo['password']} < $inputfile 2>&1";

        $result = exec($fullCmd, $output, $return);

        if ($return != 0) {
            throw new \Exception("Failed importing: {$result}");
        }

        echo "+ Done\n";

        return 0;
    }

    private function loadRawSql($filename)
    {
        return file_get_contents(database_path("userdb/{$filename}"));
    }
}
