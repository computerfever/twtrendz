<?php

namespace Modules\Themes\Http\Middleware;

use Closure;
use Modules\LandingPage\Entities\LandingPage;

class CustomDomain{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next){
		$domain = $request->getHost();
		// domain main
		if ($domain == getAppDomain()) {

			$request->merge([
				'domain' => $domain,
			]);
			return $next($request);
		}
		// check domain customize landing page
		$user = \Acelle\Model\User::where('url',$domain)->first();

        if(!empty($user)){

            if($user->landing_page != null){
                $page= LandingPage::where('id',$user->landing_page)->firstOrFail();
            }else{
                $page= LandingPage::where('admin',1)->publish()->firstOrFail();
            }

        	$landingPageUser = $user; 
       	}else{
			$page = LandingPage::where('custom_domain', $domain)->orWhere('sub_domain', $domain)->publish()->firstOrFail();
			$landingPageUser = null;
		}

		// Append domain and tenant to the Request object
		$request->merge([
			'domain' => $domain,
			'page' => $page,
			'landingPageUser'=>$landingPageUser
		]);

		return $next($request);
	}
}