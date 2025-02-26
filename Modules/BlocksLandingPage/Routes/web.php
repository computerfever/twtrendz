<?php
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('settings')->name('settings.')->group(function(){
	Route::resource('blocks', 						'BlocksLandingPageController')->except('show'); //working
	
	Route::get('blocks/blockscss', 				'BlocksLandingPageController@blockscss')->name('blocks.blockscss'); //not working
	Route::post('blocks/updateblockscss', 'BlocksLandingPageController@updateblockscss')->name('blocks.updateblockscss'); //not working

	Route::post('blocks/copyedit/{id}', 	'BlocksLandingPageController@copyedit')->name('blocks.copyedit'); //working

	Route::resource('block-categories', 'BlocksCategoriesController')->except('show');//working
});