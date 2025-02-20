<?php

namespace Tests\Unit;

use Tests\TestCase;
use Carbon\Carbon;
use Acelle\Library\DynamicRateTracker;
use Acelle\Library\RateLimit;
use Acelle\Library\Exception\RateLimitExceeded;
use Acelle\Library\Exception\RateLimitReservedByAnotherFileSystem;
use Exception;

use function Acelle\Helpers\execute_with_limits;

class DynamicRateTrackerTest extends TestCase
{
    public function test_just_works()
    {
        $key = 'test-dynamic-rate-tracker';
        $limits = [
            new RateLimit(10, 1, 'minute', '#'),
            new RateLimit(200, 12, 'hour', '#'),
            new RateLimit(4, 1, 'minute', '#'),
            new RateLimit(5, 1, 'day', '#'),
        ];

        $tracker = new DynamicRateTracker($key, $limits);

        // clean up reserved file
        $tracker->clearReservation();

        // So the selected limit for reserving is: "4 per 1 minute"
        $this->assertEquals($tracker->getLimitsDescription(), "#, #, #, #");
        $this->assertEquals($tracker->getShortestLimitPeriod(), '1 minute');
        $this->assertEquals($tracker->getMinCredits(), 4);

        $tracker->count();

        list($until, $credits) = $tracker->parseReservedCredits();
        $this->assertEquals($credits, 3);

        $tracker->count();
        $tracker->count();
        $tracker->count();

        list($until, $credits) = $tracker->parseReservedCredits();
        $this->assertEquals($credits, 0);
    }

    public function test_with_exception_with_and_without_rate_exceeding_callback()
    {
        $key = 'test-dynamic-rate-tracker';
        $limits = [
            new RateLimit(3, 1, 'minute', '3 per minute'),
            new RateLimit(200, 12, 'hour', '5 per 24 hours (per day)'),
        ];

        $tracker = new DynamicRateTracker($key, $limits);

        // clean up reserved file
        $tracker->clearReservation();

        // So the selected limit for reserving is: "4 per 1 minute"
        $this->assertEquals($tracker->getShortestLimitPeriod(), '1 minute');
        $this->assertEquals($tracker->getMinCredits(), 3);

        $exceeded = false;

        $rateExceedingCallback = function ($msg) use (&$exceeded) {
            $exceeded = true;
        };

        $tracker->count(now(), $rateExceedingCallback);
        $tracker->count(now(), $rateExceedingCallback);
        $tracker->count(now(), $rateExceedingCallback);

        $this->assertFalse($exceeded);

        list($until, $credits) = $tracker->parseReservedCredits();
        $this->assertEquals($credits, 0);

        try {
            $tracker->count(now(), $rateExceedingCallback);
            throw new Exception('Not expected here');
        } catch (RateLimitExceeded $ex) {

            // the callback is executed beautifully!
            $this->assertTrue($exceeded);
        }

        $this->expectException(RateLimitExceeded::class);
        $tracker->count(now(), $rateExceedingCallback);
    }

    public function test_with_no_limits_at_all()
    {
        $key = 'test-dynamic-rate-tracker';
        $tracker = new DynamicRateTracker($key, $limits = []);

        // clean up reserved file
        $tracker->clearReservation();

        // So the selected limit for reserving is: "4 per 1 minute"
        $this->assertNull($tracker->getMinCredits());

        $tracker->count();
        $tracker->count();
        $tracker->count();

        $tracker->test();
    }

    public function test_with_multi_trackers()
    {
        $key1 = 'test-dynamic-rate-tracker-1';
        $key2 = 'test-dynamic-rate-tracker-2';

        $tracker1 = new DynamicRateTracker($key1, [ new RateLimit(10, 1, 'minute') ]);
        $tracker2 = new DynamicRateTracker($key2, [ new RateLimit(5, 1, 'minute') ]);

        $tracker1->clearReservation();
        $tracker2->clearReservation();

        // The idea is: tracker2 will hit rate limit
        // However, make sure tracker1 remaining credits are not counted!
        for($i = 1; $i <= 5; $i += 1) {
            execute_with_limits([$tracker1, $tracker2], $credits = [], function () {
                // do nothing
            });

            list($until1, $credits1) = $tracker1->parseReservedCredits();
            list($until2, $credits2) = $tracker2->parseReservedCredits();

            $this->assertEquals($credits1, 10 - $i);
            $this->assertEquals($credits2, 5 - $i);
        }


        for($i = 1; $i <= 2; $i += 1) {

            try {
                execute_with_limits([$tracker1, $tracker2], $credits = [], function () {
                    // do nothing
                });

                throw new Exception('Exception must be thrown');
            } catch (RateLimitExceeded $ex) {
                // expected
            }

            list($until1, $credits1) = $tracker1->parseReservedCredits();
            list($until2, $credits2) = $tracker2->parseReservedCredits();

            $this->assertEquals($credits1, 5);
            $this->assertEquals($credits2, 0);
        }
    }

    public function test_with_multi_process()
    {
        $key = 'test-dynamic-rate-tracker';
        $limits = [
            new RateLimit(2, 5, 'seconds', '2 per 5  secs'),
            new RateLimit(200, 12, 'hour', '200 per 12 hours'),
        ];

        $tracker = new DynamicRateTracker($key, $limits);

        // !!!!!!!!!!!!!!! IMPORTANT - do not clear here
        // $tracker->clearReservation();

        // So the selected limit for reserving is: "4 per 1 minute"
        $this->assertEquals($tracker->getShortestLimitPeriod(), '5 seconds');
        $this->assertEquals($tracker->getMinCredits(), 2);

        // Only test it manually, by running two consoles separately with the following command
        //
        //     ./vendor/bin/pest --filter=test_with_multi_process
        //
        // And monitor the outcome: only 2 lines added per 5 seconds
        //
        //    tail -f /tmp/test-multi-process
        //

        return;

        while (true) {
            try {
                execute_with_limits($rateTrackers = [$tracker], $creditsTrackers = [], function () {
                    // ONLY WRITE 2 lines (in total) every 30 seconds
                    $now = now()->toString();
                    $message = "{$now}: Added by ".getmypid()."\n";
                    $f = fopen("/tmp/test-multi-process", "a") or die("Unable to open file!");
                    fwrite($f, $message);
                    fclose($f);
                    echo " - Done\n";
                });
            } catch (RateLimitExceeded $ex) {
                // no problem, try again
                echo " - Rate exceeded\n";
                sleep(rand(1, 5));
            }
        }
    }

    public function test_reserved_by_others()
    {
        $key = 'test-dynamic-rate-tracker';
        $limits = [
            new RateLimit(2, 5, 'seconds', '2 per 30 secs'),
            new RateLimit(200, 12, 'hour', '5 per 24 hours (per day)'),
        ];

        $tracker = new DynamicRateTracker($key, $limits);
        $tracker->clearReservation();

        $this->assertFalse($tracker->isReservedByOthers(now()));
    }

    public function test_get_reserved_until_value_from_reserved_by_another_exception()
    {
        $until = now()->add('1 hour');

        try {
            throw new RateLimitReservedByAnotherFileSystem('Hello', $code = 0, $previous = null, $until);
        } catch (RateLimitReservedByAnotherFileSystem $ex) {
            $this->assertTrue($until->eq($ex->getReservedUntil()));
        }
    }
}
