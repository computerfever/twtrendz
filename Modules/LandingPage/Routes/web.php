<?php
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::group(['middleware' => ['not_installed', 'auth', 'frontend', 'subscription']], function(){

	Route::post('getFonts', 'LandingPageController@getFonts'); //working

	Route::prefix('landingpages')->group(function() {
		Route::get('/', 'LandingPageController@index')->name('landingpages.index');//working

		// Landing Pages Settings
		Route::get('setting/{item}', 'LandingPageController@setting')->name('landingpages.setting');//working

		Route::get('publish/{item}', 'LandingPageController@publish')->name('landingpages.publish');//working

		Route::post('setting-update/{item}', 'LandingPageController@settingUpdate')->name('landingpages.settings.update'); //working

		// Create New LandingPage
		Route::post('save', 'LandingPageController@save')->name('landingpages.save'); //working

		// Landing Pages Load builder
		Route::get('builder/{code}/{type?}', 'LandingPageController@builder')->name('landingpages.builder');//working
		Route::get('load-builder/{item}/{type?}', 'LandingPageController@loadBuilder')->name('landingpages.loadBuilder');//working
		Route::post('update-builder/{item}/{type?}', 'LandingPageController@updateBuilder')->name('landingpages.updateBuilder');//working

		Route::post('clone/{id}', 'LandingPageController@clone')->name('landingpages.clone');//working

		// Landing Pages Delete
		Route::post('delete/{item}', 'LandingPageController@delete')->name('landingpages.delete');//working
	});

	Route::prefix('intergration')->group(function() {
  	Route::post('lists/{type}', 'IntergrationController@lists');//working
  	Route::post('mergefields/{type}', 'IntergrationController@mergefields');//working
  	
  	Route::get('testConnection', 'IntergrationController@testConnection');
  });


	// Preview Template
	Route::prefix('landingpages')->group(function() {
		Route::get('preview-template/{id}', 'LandingPageController@previewTemplate')->name('landingpages.preview');//working
		Route::get('frame-main-page/{id}', 'LandingPageController@frameMainPage')->name('landingpages.frame-main-page');//working
		Route::get('frame-thank-you-page/{id}', 'LandingPageController@frameThankYouPage')->name('landingpages.frame-thank-you-page');//working
		Route::post('get-template-json/{code}', 'LandingPageController@getTemplateJson');//working
	});

	// LandingPage Builder Routes
	Route::post('uploadimage', 'LandingPageController@uploadImage');//working
  Route::post('deleteimage', 'LandingPageController@deleteImage');//working
  Route::post('searchIcon', 'LandingPageController@searchIcon');//not working
	

});

Route::get('storage/{folderName}/{fileName}/{fileName2?}', function(Request $request, $folderName, $fileName, $fileName2=""){
	if(checkIsAwsS3()){
		if(empty($fileName2)){
			$url = Storage::disk('s3')->url("storage/$folderName/$fileName");
		}elseif(!empty($fileName2)){
			$url = Storage::disk('s3')->url("storage/$folderName/$fileName/$fileName2");
		}
		return redirect($url);
	}
});