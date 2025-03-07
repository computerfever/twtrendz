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
        	$page= LandingPage::where('admin',1)->publish()->firstOrFail();
       	}else{
			$page = LandingPage::where('custom_domain', $domain)->orWhere('sub_domain', $domain)->publish()->firstOrFail();
		}

		// Append domain and tenant to the Request object
		$request->merge([
			'domain' => $domain,
			'page' => $page
		]);

		return $next($request);
	}
}