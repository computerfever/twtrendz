<?php

namespace Acelle\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class Test implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $timeout = 5;
    protected $object = null;
    protected $objectId = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($object, $objectId)
    {
        $this->object = $object;
        $this->objectId = $objectId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        echo "Vardump object passed: ";
        var_dump($this->object);

        return;
    }


    public $uniqueFor = 10;
    public function uniqueId(): string
    {
        echo "Checking uniqe id\n";
        return 1;
    }
}
