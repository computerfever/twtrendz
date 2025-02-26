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

// Route::get('/', 'ThemesController@getLandingPage');
Route::get('/thank-you', 'ThemesController@thankYouPage');
Route::post('get-page-json/{code}', 'ThemesController@getPageJson')->name('getPageJson');

// Route::get('/saad', 'ThemesController@getLandingPage');
// Route::get('/saad/thank-you', 'ThemesController@thankYouPage');
// Route::post('/saad/get-page-json/{code}', 'ThemesController@getPageJson');

Route::get('/{custom_url}', 'ThemesController@getCustomUrlLandingPage');
Route::get('/{custom_url}/thank-you', 'ThemesController@getCustomUrlThankYouPage');
Route::post('/{custom_url}/get-page-json/{code}', 'ThemesController@getCustomUrlPageJson')->name('getCustomUrlPageJson');