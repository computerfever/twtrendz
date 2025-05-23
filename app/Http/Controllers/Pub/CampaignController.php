<?php

namespace Acelle\Http\Controllers\Pub;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log as LaravelLog;
use Acelle\Http\Controllers\Controller;
use Acelle\Library\StringHelper;
use Acelle\Jobs\ExportCampaignLog;
use Acelle\Model\TrackingLog;
use Acelle\Model\Subscriber;
use Acelle\Model\Campaign;
use Acelle\Model\IpLocation;
use Acelle\Model\ClickLog;
use Acelle\Model\OpenLog;
use Acelle\Model\JobMonitor;
use DB;
use Carbon\Carbon;

class CampaignController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    private function setUserDbConnection($request)
    {
        $customerUid = $request->customer_uid;
        $customer = \Acelle\Model\Customer::findByUid($customerUid);

        if (is_null($customer)) {
            throw new \Exception('Cannot find customer connection');
        } else {
            $customer->setUserDbConnection();
        }
    }

    /**
     * Campaign overview.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function overview(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);

        // Trigger the CampaignUpdate event to update the campaign cache information
        // The second parameter of the constructor function is false, meanining immediate update
        event(new \Acelle\Events\CampaignUpdated($campaign));

        return view('public.campaigns.overview', [
            'campaign' => $campaign,
        ]);
    }

    /**
     * Campaign links.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function links(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);
        $links = $campaign->clickLogs()
                          ->select(
                              'click_logs.url',
                              DB::raw('count(*) AS clickCount'),
                              DB::raw(sprintf('max(%s) AS lastClick', table('click_logs.created_at')))
                          )->groupBy('click_logs.url')->get();

        return view('public.campaigns.links', [
            'campaign' => $campaign,
            'links' => $links,
        ]);
    }

    /**
     * 24-hour chart.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function chart24h(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);
        $currentTimezone = $campaign->customer->getTimezone();

        $result = [
            'columns' => [],
            'opened' => [],
            'clicked' => [],
        ];



        // 24h collection
        if ($request->period == '24h') {
            $hours = [];

            // columns
            for ($i = 23; $i >= 0; --$i) {
                $time = Carbon::now()->timezone($currentTimezone)->subHours($i);
                $result['columns'][] = $time->format('h') . ':00 ' . $time->format('A');
                $hours[] = $time->format('H');
            }

            $openData24h = $campaign->openUniqHours(Carbon::now('UTC')->subHours(24), Carbon::now('UTC'));
            $clickData24h = $campaign->clickHours(Carbon::now('UTC')->subHours(24), Carbon::now('UTC'));

            // data
            foreach ($hours as $hour) {
                $num = isset($openData24h[$hour]) ? count($openData24h[$hour]) : 0;
                $result['opened'][] = $num;

                $num = isset($clickData24h[$hour]) ? count($clickData24h[$hour]) : 0;
                $result['clicked'][] = $num;
            }
        } elseif ($request->period == '3_days') {
            $days = [];

            // columns
            for ($i = 2; $i >= 0; --$i) {
                $time = Carbon::now()->timezone($currentTimezone)->subDays($i);
                $result['columns'][] = $time->format('m-d');
                $days[] = $time->format('Y-m-d');
            }

            $openData = $campaign->openUniqDays(Carbon::now('UTC')->subDays(3), Carbon::now('UTC')->endOfDay());
            $clickData = $campaign->clickDays(Carbon::now('UTC')->subDays(3), Carbon::now('UTC')->endOfDay());

            // data
            foreach ($days as $day) {
                $num = isset($openData[$day]) ? count($openData[$day]) : 0;
                $result['opened'][] = $num;

                $num = isset($clickData[$day]) ? count($clickData[$day]) : 0;
                $result['clicked'][] = $num;
            }
        } elseif ($request->period == '7_days') {
            $days = [];

            // columns
            for ($i = 6; $i >= 0; --$i) {
                $time = Carbon::now()->timezone($currentTimezone)->subDays($i);
                $result['columns'][] = $time->format('m-d');
                $days[] = $time->format('Y-m-d');
            }

            $openData = $campaign->openUniqDays(Carbon::now('UTC')->subDays(7), Carbon::now('UTC')->endOfDay());
            $clickData = $campaign->clickDays(Carbon::now('UTC')->subDays(7), Carbon::now('UTC')->endOfDay());

            // data
            foreach ($days as $day) {
                $num = isset($openData[$day]) ? count($openData[$day]) : 0;
                $result['opened'][] = $num;

                $num = isset($clickData[$day]) ? count($clickData[$day]) : 0;
                $result['clicked'][] = $num;
            }
        } elseif ($request->period == 'last_month') {
            $days = [];

            // columns
            for ($i = Carbon::now('UTC')->subMonths(1)->diff(Carbon::now('UTC'))->days - 1; $i >= 0; --$i) {
                $time = Carbon::now()->timezone($currentTimezone)->subDays($i);
                $result['columns'][] = $time->format('m-d');
                $days[] = $time->format('Y-m-d');
            }

            $openData = $campaign->openUniqDays(Carbon::now('UTC')->subMonths(1), Carbon::now('UTC')->endOfDay());
            $clickData = $campaign->clickDays(Carbon::now('UTC')->subMonths(1), Carbon::now('UTC')->endOfDay());

            // data
            foreach ($days as $day) {
                $num = isset($openData[$day]) ? count($openData[$day]) : 0;
                $result['opened'][] = $num;

                $num = isset($clickData[$day]) ? count($clickData[$day]) : 0;
                $result['clicked'][] = $num;
            }
        } elseif ($request->period == 'last_year') {
            $months = [];

            // columns
            for ($i = Carbon::now('UTC')->subYears(1)->diffInMonths(Carbon::now('UTC')) - 1; $i >= 0; --$i) {
                $time = Carbon::now()->timezone($currentTimezone)->subMonths($i);
                $result['columns'][] = $time->format('Y, M');
                $months[] = $time->format('Y-m');
            }

            $openData = $campaign->openUniqMonths(Carbon::now('UTC')->subYears(1), Carbon::now('UTC')->endOfDay());
            $clickData = $campaign->clickMonths(Carbon::now('UTC')->subYears(1), Carbon::now('UTC')->endOfDay());

            // data
            foreach ($months as $month) {
                $num = isset($openData[$month]) ? count($openData[$month]) : 0;
                $result['opened'][] = $num;

                $num = isset($clickData[$month]) ? count($clickData[$month]) : 0;
                $result['clicked'][] = $num;
            }
        }


        return response()->json($result);
    }

    /**
     * Chart.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function chart(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);

        $result = [
            [
                'name' => trans('messages.recipients'),
                'value' => $campaign->subscribersCount(),
            ],
            [
                'name' => trans('messages.delivered'),
                'value' => $campaign->deliveredCount(),
            ],
            [
                'name' => trans('messages.failed'),
                'value' => $campaign->failedCount(),
            ],
            [
                'name' => trans('messages.Open'),
                'value' => $campaign->openUniqCount(),
            ],
            [
                'name' => trans('messages.Click'),
                'value' => $campaign->uniqueClickCount(),
            ],
            [
                'name' => trans('messages.Bounce'),
                'value' => $campaign->bounceCount(),
            ],
            [
                'name' => trans('messages.report'),
                'value' => $campaign->feedbackCount(),
            ],
            [
                'name' => trans('messages.unsubscribe'),
                'value' => $campaign->unsubscribeCount(),
            ],
        ];

        return response()->json($result);
    }

    /**
     * Chart Country.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function chartCountry(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);

        $result = [
            'data' => [],
        ];

        // create data
        $total = $campaign->uniqueOpenCount();
        $count = 0;
        foreach ($campaign->topOpenCountries()->get() as $location) {
            $country_name = (!empty($location->country_name) ? $location->country_name : trans('messages.unknown'));
            $result['data'][] = ['value' => $location->aggregate, 'name' => $country_name];
            $count += $location->aggregate;
        }

        // Others
        if ($total > $count) {
            $result['data'][] = ['value' => $total - $count, 'name' => trans('messages.others')];
        }

        usort($result['data'], function ($a, $b) {
            return strcmp($a['value'], $b['value']);
        });
        $result['data'] = array_reverse($result['data']);

        return response()->json($result);
    }

    /**
     * Chart Country by clicks.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function chartClickCountry(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);

        $result = [
            'data' => [],
        ];

        // create data
        $datas = [];
        $total = $campaign->clickCount();
        $count = 0;
        foreach ($campaign->topClickCountries()->get() as $location) {
            $result['data'][] = ['value' => $location->aggregate, 'name' => $location->country_name];
            $count += $location->aggregate;
        }

        // others
        if ($total > $count) {
            $result['data'][] = ['value' => $total - $count, 'name' => trans('messages.others')];
        }

        usort($result['data'], function ($a, $b) {
            return strcmp($a['value'], $b['value']);
        });
        $result['data'] = array_reverse($result['data']);

        return response()->json($result);
    }

    /**
     * 24-hour quickView.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function quickView(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);

        return view('public.campaigns._quick_view', [
            'campaign' => $campaign,
        ]);
    }

    /**
     * Tracking logs.
     */
    public function trackingLog(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);

        $items = $campaign->trackingLogs();

        return view('public.campaigns.tracking_log', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    /**
     * Tracking logs ajax listing.
     */
    public function trackingLogListing(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);

        $items = TrackingLog::search($request, $campaign)->paginate($request->per_page);

        return view('public.campaigns.tracking_logs_list', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    /**
     * Download tracking logs.
     */
    public function trackingLogDownload(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);

        $logtype = $request->input('logtype');

        $job = new ExportCampaignLog($campaign, $logtype);
        $monitor = $campaign->dispatchWithMonitor($job);

        return view('public.campaigns.download_tracking_log', [
            'campaign' => $campaign,
            'job' => $monitor,
        ]);
    }

    /**
     * Tracking logs export progress.
     */
    public function trackingLogExportProgress(Request $request)
    {
        $this->setUserDbConnection($request);
        $job = JobMonitor::findByUid($request->uid);

        $progress = $job->getJsonData();
        $progress['status'] = $job->status;
        $progress['error'] = $job->error;
        $progress['download'] = action('Pub\CampaignController@download', ['customer_uid' => $request->customer_uid,'uid' => $job->uid]);

        return response()->json($progress);
    }

    /**
     * Actually download.
     */
    public function download(Request $request)
    {
        $this->setUserDbConnection($request);
        $job = JobMonitor::findByUid($request->uid);
        $path = $job->getJsonData()['path'];
        return response()->download($path)->deleteFileAfterSend(true);
    }

    /**
     * Bounce logs.
     */
    public function bounceLog(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);

        $items = $campaign->bounceLogs();

        return view('public.campaigns.bounce_log', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    /**
     * Bounce logs listing.
     */
    public function bounceLogListing(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);

        $items = \Acelle\Model\BounceLog::search($request, $campaign)->paginate($request->per_page);

        return view('public.campaigns.bounce_logs_list', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    /**
     * FBL logs.
     */
    public function feedbackLog(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);

        $items = $campaign->openLogs();

        return view('public.campaigns.feedback_log', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    /**
     * FBL logs listing.
     */
    public function feedbackLogListing(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);

        $items = \Acelle\Model\FeedbackLog::search($request, $campaign)->paginate($request->per_page);

        return view('public.campaigns.feedback_logs_list', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    /**
     * Open logs.
     */
    public function openLog(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);

        $items = $campaign->openLogs();

        return view('public.campaigns.open_log', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    /**
     * Open logs listing.
     */
    public function openLogListing(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);

        $items = \Acelle\Model\OpenLog::search($request, $campaign)->paginate($request->per_page);

        return view('public.campaigns.open_log_list', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    /**
     * Click logs.
     */
    public function clickLog(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);

        $items = $campaign->clickLogs();

        return view('public.campaigns.click_log', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    /**
     * Click logs listing.
     */
    public function clickLogListing(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);

        $items = \Acelle\Model\ClickLog::search($request, $campaign)->paginate($request->per_page);

        return view('public.campaigns.click_log_list', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    /**
     * Unscubscribe logs.
     */
    public function unsubscribeLog(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);

        $items = $campaign->unsubscribeLogs();

        return view('public.campaigns.unsubscribe_log', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    /**
     * Unscubscribe logs listing.
     */
    public function unsubscribeLogListing(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);

        $items = \Acelle\Model\UnsubscribeLog::search($request, $campaign)->paginate($request->per_page);

        return view('public.campaigns.unsubscribe_logs_list', [
            'items' => $items,
            'campaign' => $campaign,
        ]);
    }

    /**
     * Open map.
     */
    public function openMap(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);

        return view('public.campaigns.open_map', [
            'campaign' => $campaign,
        ]);
    }

    /**
     * Subscribers list.
     */
    public function subscribers(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);

        $subscribers = $campaign->subscribers();

        return view('public.campaigns.subscribers', [
            'subscribers' => $subscribers,
            'campaign' => $campaign,
            'list' => $campaign->defaultMailList,
            'columns' => $request->user()->getSetting('subscribers_columns') ?? ['created_at', 'updated_at'],
        ]);
    }

    /**
     * Subscribers listing.
     */
    public function subscribersListing(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);

        // Subscribers
        $subscribers = $campaign->getDeliveryReport()
                                ->addSelect('subscribers.*')
                                ->addSelect('bounce_logs.raw AS bounced_message')
                                ->addSelect('feedback_logs.feedback_type AS feedback_message')
                                ->addSelect('tracking_logs.error AS failed_message');

        // Check open conditions
        if ($request->open) {
            // Query of email addresses that DID open
            $openByEmails = $campaign->openLogs()->join('subscribers', 'tracking_logs.subscriber_id', '=', 'subscribers.id')->groupBy('subscribers.email')->select('subscribers.email');

            if ($request->open == 'yes') {
                $subscribers = $subscribers->joinSub($openByEmails, 'OpenedByEmails', function ($join) {
                    $join->on('subscribers.email', '=', 'OpenedByEmails.email');
                });
            } elseif ($request->open = 'no') {
                $subscribers = $subscribers->leftJoinSub($openByEmails, 'OpenedByEmails', function ($join) {
                    $join->on('subscribers.email', '=', 'OpenedByEmails.email');
                })->whereNull('OpenedByEmails.email');
            }
        }

        // Check click conditions
        if ($request->click) {
            // Query of email addresses that DID click
            $clickByEmails = $campaign->clickLogs()->join('subscribers', 'tracking_logs.subscriber_id', '=', 'subscribers.id')->groupBy('subscribers.email')->select('subscribers.email');

            if ($request->click == 'clicked') {
                $subscribers = $subscribers->joinSub($clickByEmails, 'ClickedByEmails', function ($join) {
                    $join->on('subscribers.email', '=', 'ClickedByEmails.email');
                });
            } elseif ($request->click = 'not_clicked') {
                $subscribers = $subscribers->leftJoinSub($clickByEmails, 'ClickedByEmails', function ($join) {
                    $join->on('subscribers.email', '=', 'ClickedByEmails.email');
                })->whereNull('ClickedByEmails.email');
            }
        }

        // Paging
        $subscribers = $subscribers->search($request->keyword)->paginate($request->per_page ? $request->per_page : 50);

        // Field information
        $fields = $campaign->defaultMailList->getFields->whereIn('uid', $request->columns);

        return view('public.campaigns._subscribers_list', [
            'subscribers' => $subscribers,
            'list' => $campaign->defaultMailList,
            'campaign' => $campaign,
            'fields' => $fields,
        ]);
    }

    /**
     * Preview template.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function preview(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaign = Campaign::findByUid($request->uid);

        return view('public.campaigns.preview', [
            'campaign' => $campaign,
        ]);
    }

    /**
     * Preview content template.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function previewContent(Request $request)
    {
        $this->setUserDbConnection($request);

        $customer = \Acelle\Model\Customer::findByUid($request->customer_uid);

        $campaign = Campaign::findByUid($request->uid);

        $mailList = \Acelle\Model\MailList::select('mail_lists.*')->leftJoin('subscriptions', 'subscriptions.customer_id', '=', 'mail_lists.customer_id')->where('subscriptions.status','=','active')->where('mail_lists.name', '=', 'Newsletter')->where('mail_lists.customer_id', '=', $customer->id)->first();

        // $subscriber = Subscriber::find($request->subscriber_id);
        if($mailList == null){
            $subscriber = $campaign->subscribers()->first();
        }else{
            $subscriber = $mailList->subscribers()->first();
        }

        // echo $campaign->getHtmlContent($subscriber);
        return view('campaigns.web_view', [
            'campaign' => $campaign,
            'subscriber' => $subscriber,
            'message_id' => null,
        ]);
    }

    /**
     * Email web view.
     */
    public function webView(Request $request)
    {
        $this->setUserDbConnection($request);
        $message_id = StringHelper::base64UrlDecode($request->message_id);
        $tracking_log = TrackingLog::where('message_id', '=', $message_id)->first();

        try {
            if (!$tracking_log) {
                throw new \Exception(trans('messages.web_view_can_not_find_tracking_log_with_message_id'));
            }

            $subscriber = $tracking_log->subscriber;
            $campaign = $tracking_log->campaign;

            if (!$campaign || !$subscriber) {
                throw new \Exception(trans('messages.web_view_can_not_find_campaign_or_subscriber'));
            }

            return view('public.campaigns.web_view', [
                'campaign' => $campaign,
                'subscriber' => $subscriber,
                'message_id' => $message_id,
            ]);
        } catch (\Exception $e) {
            return view('somethingWentWrong', ['message' => trans('messages.the_email_no_longer_exists')]);
        }
    }

    /**
     * Email web view for previewing before sending
     */
    public function webViewPreview(Request $request)
    {
        $this->setUserDbConnection($request);
        $subscriber = Subscriber::find($request->subscriber_id);
        $campaign = Campaign::findByUid($request->campaign_uid);

        if (is_null($subscriber) || is_null($campaign)) {
            throw new \Exception('Invalid subscriber or campaign UID');
        }

        return view('public.campaigns.web_view', [
            'campaign' => $campaign,
            'subscriber' => $subscriber,
            'message_id' => null,
        ]);
    }

    /**
     * Template review.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function templateReview(Request $request)
    {
        $this->setUserDbConnection($request);
        // Get current user
        $campaign = Campaign::findByUid($request->uid);

        return view('public.campaigns.template_review', [
            'campaign' => $campaign,
        ]);
    }

    /**
     * Template review iframe.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function templateReviewIframe(Request $request)
    {
        $this->setUserDbConnection($request);
        // Get current user
        $campaign = Campaign::findByUid($request->uid);

        return view('public.campaigns.template_review_iframe', [
            'campaign' => $campaign,
        ]);
    }

    public function speedtest(Request $request)
    {
        $this->setUserDbConnection($request);
        $campaigns = Campaign::latest()->paginate(10);

        return view('campaigns._list', [
            'campaigns' => $campaigns,
        ]);
    }
}
