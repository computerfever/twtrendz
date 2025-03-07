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

            if(!$request->user()){
                // return route('login');
                return redirect('login');
            }

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

            $user = \Acelle\Model\User::where('url',$domain)->first();

            if(!empty($user)){
                
                $page= LandingPage::where('admin',1)->publish()->firstOrFail();
                    
                $customer = $user->customer;

                if(!$customer->user->hasPermission('landing_pages.full_access')){
                    abort(404);
                }

                $subscription = $customer->getCurrentActiveSubscription();

                if(empty($subscription)){
                    abort(404);
                }

                $blockscss          = replaceVarContentStyle(config('app.blockscss'));
                $check_remove_brand = 1;
                $jsonPageRoute      = route("getPageJson", ["code"=>$page->code]);
                $thankYouURL        = getLandingPageCurrentURL($page)."/thank-you";

                return view('landingpage::landingpages.publish_page', compact('page','jsonPageRoute','thankYouURL','blockscss','check_remove_brand'));
                

            }else{

                // check domain customize landing page
                $page= LandingPage::where('custom_domain', $domain)->orWhere('sub_domain', $domain)->publish()->firstOrFail();
                if(empty($page->custom_url)){
                    
                    $customer = $page->customer;

                    if(!$customer->user->hasPermission('landing_pages.full_access')){
                        abort(404);
                    }

                    $subscription = $customer->getCurrentActiveSubscription();

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
}
