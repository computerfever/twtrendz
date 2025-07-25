<?php

namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;
use Acelle\Model\Subscription;

use Illuminate\Support\Facades\Http;

use Modules\LandingPage\Entities\LandingPage;

class TestController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){

        $users = \Acelle\Model\User::get();

        foreach ($users as $user) {

            if(!$user->can("admin_access", $user)){
                
                $email = $user->email;

                $fwdEmail = strtolower(explode("@", $email)[0]."@twtrendz.com");

                $insertForwaderEmail = insertForwaderEmail($email,$fwdEmail);

                echo $email." --- ".$fwdEmail."<br>";

                echo $insertForwaderEmail;

                echo "<br>";

                $user->fwd_email = $insertForwaderEmail;

                $user->save();

            }

        }

        // $logs = \Acelle\Model\BounceLog::where('raw', 'like', '"{%')->get();

        // foreach ($logs as $log) {

        //     // $log->raw = str_replace(["\\"], "", $log->raw);
        //     // $log->raw = str_replace(['"{','}"'], ['{','}'], $log->raw);

        //     $raw = json_decode($log->raw,true);
        //     $log->raw = $raw;
        //     $log->save();

        // }

        // $customersList = \Acelle\Model\MailList::where(['name'=>'Customers Emails','customer_id'=>1])->first();

        // $users = \Acelle\Model\User::get();

        // foreach ($users as $user) {

        //     if(!$user->can("admin_access", $user)){
        //         if(!empty($customersList)){
                    
        //             $checkSubscriber = \Acelle\Model\Subscriber::where(['mail_list_id'=>$customersList->id,"email"=>$user->email])->first();
        //             if(empty($checkSubscriber)){
        //                 $subscriber = new \Acelle\Model\Subscriber();

        //                 $subscriber->mail_list_id = $customersList->id;

        //                 $subscriber->email = $user->email;

        //                 $subscriber->custom_100 = $user->email;
        //                 $subscriber->custom_101 = $user->first_name;
        //                 $subscriber->custom_102 = $user->last_name;

        //                 $subscriber->status = 'subscribed';

        //                 $subscriber->save();
        //             }
        //         }
        //     }

        //     // $user->url = $user->customerLandingPageUrl();
        //     // $user->save();

        // }

    }   
    
}
