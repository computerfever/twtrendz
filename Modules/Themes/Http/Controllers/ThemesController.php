<?php

namespace Modules\Themes\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\LandingPage\Entities\LandingPage;
use Illuminate\Support\Facades\App;
use Module;

class ThemesController extends Controller{
	
	public function getLandingPage(Request $request){
		if($request->domain == getAppDomain()){
			$skin            = config('app.SITE_LANDING');
			$currency_symbol = config('app.CURRENCY_SYMBOL');
			$currency_code   = config('app.CURRENCY_CODE');
			$user            = $request->user();
			return view('themes::' . $skin . '.home', compact('user','currency_symbol','currency_code'));
		}else{
			$page               = $request->page;
			$blockscss          = replaceVarContentStyle(config('app.blockscss'));
			$check_remove_brand = 1;
			$jsonPageRoute      = route("getPageJson", ["code"=>$page->code]);
			$thankYouURL      	= getLandingPageCurrentURL($page)."/thank-you";

			return view('landingpage::landingpages.publish_page', compact('page','jsonPageRoute','thankYouURL','blockscss','check_remove_brand'));
		}
	}
	public function getPageJson(Request $request){
		$page = $request->page;

		$blockscss = replaceVarContentStyle(config('app.blockscss'));
		$fontCurrently = "Open Sans";
		
		if(isset($page->settings->fontCurrently)){
			$fontCurrently = $page->settings->fontCurrently;
		}

		$customer = $page->customer;

		$html = $page->html;

		$tags = [];
	    $tags['CONSULTANT_ID'] = @$customer->contact->consultant_id;
        $tags['CONSULTANT_MSG'] = @$customer->contact->message;
        $tags['first_name'] = @$customer->contact->first_name;
        $tags['last_name'] = @$customer->contact->last_name;
        $tags['COMPANY'] = @$customer->contact->company;
        $tags['PHONE'] = @$customer->contact->phone;
        $tags['email'] = @$customer->contact->email;
        $tags['URL'] = @$customer->contact->url;
        $tags['image'] = @$customer->contact->image;
        $tags['profile_photo'] = @$customer->user->getProfileImageUrl();

		foreach ($tags as $tag => $value) {
            $html = str_replace('{'.$tag.'}', $value ?? '#', $html);
        }

		return response()->json([
			'blockscss'           =>$blockscss, 
			'css'                 => $page->css,
			'html'                => "$html",
			'fontCurrently'       =>  $fontCurrently,
			'custom_header'       => $page->custom_header,
			'custom_footer'       => $page->custom_footer,
			'thank_custom_header' => $page->thank_custom_header,
			'thank_custom_footer' => $page->thank_custom_footer,
			'thank_you_page_css'  => $page->thank_you_page_css,
			'thank_you_page_html' => $page->thank_you_page_html,
			'main_page_script'    => $page->main_page_script,
		]);
	}
	public function thankYouPage(Request $request){
		if($request->domain == getAppDomain()){
			abort(404);
		}else{
			$page               = $request->page;
			$blockscss          = replaceVarContentStyle(config('app.blockscss'));
			$check_remove_brand = 1;
			$jsonPageRoute      = route("getPageJson", ["code"=>$page->code]);

			return view('landingpage::landingpages.publish_thank_page', compact(
				'page','jsonPageRoute','blockscss','check_remove_brand'
			));
		}
	}

	public function getCustomUrlLandingPage(Request $request, $custom_url){
		$domain             = $request->getHost();
		// $page            = $request->page;
		$page               = LandingPage::where(['custom_domain'=> $domain,'custom_url'=> $custom_url])->publish()->firstOrFail();
		$blockscss          = replaceVarContentStyle(config('app.blockscss'));
		$check_remove_brand = 1;
		$jsonPageRoute      = route("getCustomUrlPageJson", ["custom_url"=>$custom_url, "code"=>$page->code]);
		$thankYouURL      	= getLandingPageCurrentURL($page)."/$custom_url/thank-you";

		return view('landingpage::landingpages.publish_page', compact('page','jsonPageRoute','thankYouURL','blockscss','check_remove_brand'));
	}
	public function getCustomUrlPageJson(Request $request, $custom_url, $code){
		// $page = $request->page;
		$domain  = $request->getHost();
		$page    = LandingPage::where(['custom_domain'=> $domain,'custom_url'=> $custom_url])->publish()->firstOrFail();

		$blockscss = replaceVarContentStyle(config('app.blockscss'));
		$fontCurrently = "Open Sans";
		if(isset($page->settings->fontCurrently)){
			$fontCurrently = $page->settings->fontCurrently;
		}
		return response()->json([
			'blockscss'           => $blockscss, 
			'css'                 => $page->css,
			'html'                => $page->html,
			'fontCurrently'       => $fontCurrently,
			'custom_header'       => $page->custom_header,
			'custom_footer'       => $page->custom_footer,
			'thank_custom_header' => $page->thank_custom_header,
			'thank_custom_footer' => $page->thank_custom_footer,
			'thank_you_page_css'  => $page->thank_you_page_css,
			'thank_you_page_html' => $page->thank_you_page_html,
			'main_page_script'    => $page->main_page_script,
		]);
	}
	public function getCustomUrlThankYouPage(Request $request, $custom_url){
		$domain             = $request->getHost();
		// $page            = $request->page;
		$page               = LandingPage::where(['custom_domain'=> $domain,'custom_url'=> $custom_url])->publish()->firstOrFail();
		$blockscss          = replaceVarContentStyle(config('app.blockscss'));
		$check_remove_brand = 1;
		$jsonPageRoute      = route("getCustomUrlPageJson", ["custom_url"=>$custom_url, "code"=>$page->code]);

		return view('landingpage::landingpages.publish_thank_page', compact(
			'page','jsonPageRoute','blockscss','check_remove_brand'
		));
	}

}