<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['namespace' => 'Api', 'prefix' => 'v1', 'middleware' => ['auth:api', 'setdb']], function () {
    //
    Route::get('', function () {
        return \Response::json(\Auth::guard('api')->user());
    });

    // Simple authentication
    Route::get('me', function () {
        return \Response::json(\Auth::guard('api')->user());
    });

    // List
    Route::delete('lists/{uid}', 'MailListController@delete');
    Route::post('lists/{uid}/add-field', 'MailListController@addField');
    Route::resource('lists', 'MailListController');

    // Campaign
    Route::delete('campaigns/{uid}', 'CampaignController@delete');
    Route::post('campaigns/{uid}/pause', 'CampaignController@pause');
    Route::post('campaigns/{uid}/run', 'CampaignController@run');
    Route::post('campaigns/{uid}/resume', 'CampaignController@resume');
    Route::resource('campaigns', 'CampaignController');

    // Subscriber
    Route::patch('lists/{list_uid}/subscribers/email/{email}/unsubscribe', 'SubscriberController@unsubscribeEmail');
    Route::post('subscribers/{id}/add-tag', 'SubscriberController@addTag');
    Route::post('subscribers/{id}/remove-tag', 'SubscriberController@removeTag');
    Route::get('subscribers/email/{email}', 'SubscriberController@showByEmail');
    Route::patch('lists/{list_uid}/subscribers/{id}/subscribe', 'SubscriberController@subscribe');
    Route::patch('lists/{list_uid}/subscribers/{id}/unsubscribe', 'SubscriberController@unsubscribe');
    Route::delete('subscribers/{id}', 'SubscriberController@delete');

    Route::resource('subscribers', 'SubscriberController');

    // Automation
    Route::post('automations/{uid}/api/call', 'AutomationController@apiCall');

    // Sending server
    Route::resource('sending_servers', 'SendingServerController');

    // Plan
    Route::resource('plans', 'PlanController');

    // Customer
    Route::get('customers/by-email/{email}', 'CustomerController@findByEmail');
    Route::post('customers/{uid}/subscription/update', 'CustomerController@subscriptionUpdate');
    Route::post('customers/{uid}/change-plan/{plan_uid}', 'CustomerController@changePlan');
    Route::match(['get','post'], 'login-token', 'CustomerController@loginToken');
    Route::post('customers/{uid}/assign-plan/{plan_uid}', 'CustomerController@assignPlan');
    Route::patch('customers/{uid}/disable', 'CustomerController@disable');
    Route::patch('customers/{uid}/enable', 'CustomerController@enable');
    Route::resource('customers', 'CustomerController');

    // Subscription
    Route::resource('subscriptions', 'SubscriptionController');

    // File
    Route::post('file/upload', 'FileController@upload');

    // File
    Route::post('automations/{uid}/execute', 'AutomationController@execute')->name('automation_execute');

    Route::post('notification/bounce', 'NotificationController@bounce');
    Route::post('notification/feedback', 'NotificationController@feedback');
});

Route::group(['namespace' => 'Api\Public', 'prefix' => 'v1'], function () {
    Route::post('public/subscribers', 'SubscriberController@store');

    // Payment
    Route::get('payment/list', 'PaymentController@list');

    // Email Verification
    Route::get('email-verification/get-checkout-url', 'EmailVerificationController@getCheckoutUrl');
    Route::get('email-verification/get-subscription', 'EmailVerificationController@getSubscription');
    Route::post('email-verification/customer/find-create', 'EmailVerificationController@findOrCreateCustomer');
    Route::get('email-verification/feature-plan', 'EmailVerificationController@getFeaturePlan');
    Route::post('email-verification/subscribe', 'EmailVerificationController@subscribe');

    // Plans
    Route::get('public/plans/available', 'PlanController@availablePlans');
});
