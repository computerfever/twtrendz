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

// FrontEnd
Route::group(['middleware' => ['not_installed', 'auth', 'frontend', 'subscription']], function () {

	// FrontEnd - Create LandingPage Page
	Route::get('alltemplates/{id?}', 'TemplateLandingPageController@getAllTemplate')->name('alltemplates');

});

// ADMIN AREA Admin Panel - Landing Page Settings
Route::group(['middleware' => ['not_installed', 'auth', 'backend']], function () {

	Route::prefix('settings')->name('settings.')->group(function(){
		Route::resource('templates', 'TemplateLandingPageController')->except('show'); //working

		Route::post('templates/uploadimage', 'TemplateLandingPageController@uploadImage')->name('templates.uploadimage'); //working
	  Route::post('templates/deleteimage', 'TemplateLandingPageController@deleteImage')->name('templates.deleteimage'); //working

		Route::post('templates/clone/{id}', 'TemplateLandingPageController@clone')->name('templates.clone'); //working
		// Builder template
		Route::get('templates/builder/{id}/{type?}', 'TemplateLandingPageController@builder')->name('templates.builder'); //working
		// Load builder
		Route::get('templates/load-builder/{id}/{type?}', 'TemplateLandingPageController@loadBuilder')->name('templates.loadBuilder'); //working
		Route::post('templates/update-builder/{id}/{type?}', 'TemplateLandingPageController@updateBuilder')->name('templates.updateBuilder'); //working

		// Template Categories
		Route::resource('categories', 'CategoriesTemplateController')->except('show'); //working

		// Template Groupcategories
		Route::resource('groupcategories', 'GroupCategoriesController')->except('show'); //working
	});

});

