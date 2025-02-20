<?php

namespace Acelle\Library\Exception;

use Exception;
use Throwable;
use Illuminate\Support\Carbon;
use Acelle\Library\Exception\RateLimitExceeded;

class RateLimitReservedByAnotherFileSystem extends RateLimitExceeded
{
    protected $reservedUntil;

    // Overwrite the constructor of Exception class
    // Reference: https://www.php.net/manual/en/language.exceptions.extending.php
    // @IMPORTANT: $reservedUntil cannot be applied to RateLimitExceeded, as there are many limits to check, there is NO "reserved until" value
    public function __construct($message, $code = 0, Throwable $previous = null, Carbon $reservedUntil = null)
    {
        // Custom attribute
        $this->reservedUntil = $reservedUntil;

        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }

    // get custom attribute
    public function getReservedUntil()
    {
        return $this->reservedUntil;
    }
}
