<?php

namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class AudienceController extends Controller
{
    public function overview(Request $request)
    {
        // authorize
        if (\Gate::denies('list', \Acelle\Model\MailList::class)) {
            return $this->notAuthorized();
        }

        // stats
        $subscriberCount = $request->user()->customer->local()->subscribers()->count();
        $subscribedCount = $request->user()->customer->local()->subscribers()->subscribed()->count();
        $activeContactPercent = $subscriberCount > 0 ? ($subscribedCount / $subscriberCount) : 0;

        return view('audience.overview', [
            'subscriberCount' => $subscriberCount,
            'subscribedCount' => $subscribedCount,
            'activeContactPercent' => $activeContactPercent,
            'formCount' => $request->user()->customer->local()->forms()->count(),

            // Cross db join
            'blacklistedCount' => config('sharding.enabled') ? 0 : $request->user()->customer->local()->subscribers()
                ->join('blacklists', 'subscribers.email', '=', 'blacklists.email')->count(),
        ]);
    }

    public function growthChart(Request $request)
    {
        // authorize
        if (\Gate::denies('list', \Acelle\Model\MailList::class)) {
            return $this->notAuthorized();
        }

        $currentTimezone = $request->user()->customer->getTimezone();

        $result = [
            'columns' => [],
            'total' => [],
            'unsubscribed' => [],
        ];

        $times = [];

        // columns
        for ($i = 15; $i >= 0; --$i) {
            $time = Carbon::now($currentTimezone)->subMonths($i);
            $result['columns'][] = $time->format('y M');
            $times[] = $time;
        }

        // data
        foreach ($times as $time) {
            $result['total'][] = \Acelle\Model\LocalCustomer::subscribersCountByTime(
                \Carbon\Carbon::now($currentTimezone)->subYears(1000),
                $time->endOfDay(),
                $request->user()->customer->id
            );

            $result['unsubscribed'][] = \Acelle\Model\LocalCustomer::subscribersCountByTime(
                \Carbon\Carbon::now($currentTimezone)->subYears(1000),
                $time->endOfDay(),
                $request->user()->customer->id,
                null,
                \Acelle\Model\Subscriber::STATUS_UNSUBSCRIBED
            );
        }

        return response()->json($result);
    }
}
