<?php

namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;
use Acelle\Model\Subscription;

use Modules\LandingPage\Entities\LandingPage;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){

        $domain = $request->getHost();

        if($domain == getAppDomain()){

            event(new \Acelle\Events\UserUpdated($request->user()->customer));
            $currentTimezone = $request->user()->customer->getTimezone();

            // Last month
            $customer = $request->user()->customer;

            $maxLists = get_tmp_quota($customer, 'list_max');
            $maxCampaigns = get_tmp_quota($customer, 'campaign_max');
            $maxSubscribers = get_tmp_quota($customer, 'subscriber_max');

            $listsCount = $customer->local()->listsCount();
            $listsUsed = ($maxLists == -1) ? 0 : $listsCount / $maxLists;

            $campaignsCount = $customer->local()->campaignsCount();
            $campaignsUsed = ($maxCampaigns == -1) ? 0 : $campaignsCount / $maxCampaigns;

            $subscribersCount = $customer->local()->readCache('SubscriberCount', 0);
            $subscribersUsed = ($maxSubscribers == -1) ? 0 : $subscribersCount / $maxSubscribers;

            if (config('app.cartpaye')) {
                return view('dashboard.cartpaye');
            } else {
                return view('dashboard', [
                    'currentTimezone' => $currentTimezone,
                    'maxLists' => $maxLists,
                    'listsCount' => $listsCount,
                    'listsUsed' => $listsUsed,
                    'maxCampaigns' => $maxCampaigns,
                    'campaignsCount' => $campaignsCount,
                    'campaignsUsed' => $campaignsUsed,
                    'maxSubscribers' => $maxSubscribers,
                    'subscribersCount' => $subscribersCount,
                    'subscribersUsed' => $subscribersUsed,
                ]);
            }

        }else{

            // check domain customize landing page
            $page= LandingPage::where('custom_domain', $domain)->orWhere('sub_domain', $domain)->publish()->firstOrFail();
            if(empty($page->custom_url)){

                $user = $page->user;

                if(!$user->hasPermission('landing_pages.full_access')){
                    abort(404);
                }

                $subscription = $user->customer->getCurrentActiveSubscription();

                if(empty($subscription)){
                    abort(404);
                }

                $blockscss          = replaceVarContentStyle(config('app.blockscss'));
                $check_remove_brand = 1;
                $jsonPageRoute      = route("getPageJson", ["code"=>$page->code]);
                $thankYouURL        = getLandingPageCurrentURL($page)."/thank-you";

                return view('landingpage::landingpages.publish_page', compact('page','jsonPageRoute','thankYouURL','blockscss','check_remove_brand'));
            }

        }

    }
}
