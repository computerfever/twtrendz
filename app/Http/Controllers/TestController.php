<?php

namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;
use Acelle\Model\Subscription;

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
                
            $user->url = $user->customerLandingPageUrl();

            $user->save();

        }

    }   
}
