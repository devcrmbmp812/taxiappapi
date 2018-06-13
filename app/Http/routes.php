<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/location_update_trip','HomeController@location_update_trip');

Route::group(['prefix' => 'userApi'], function(){

	Route::post('/register','UserApiController@register');

	Route::post('/login','UserApiController@login');

	Route::get('/userDetails','UserApiController@user_details');

	Route::post('/updateProfile', 'UserApiController@update_profile');

	Route::post('/forgotpassword', 'UserApiController@forgot_password');

	Route::post('/changePassword', 'UserApiController@change_password');

	Route::get('/tokenRenew', 'UserApiController@token_renew');

    // Add Card
    Route::post('/addcard', 'UserApiController@userAddCard');

    //Get Card
    Route::get('/getcards' , 'UserApiController@getCards');

    //Select Card
	Route::post('/selectcard', 'UserApiController@selectCard');

        //Delete Card
	Route::post('/deletecard', 'UserApiController@deleteCard');

    //Get Braintree Token
    Route::post('/getbraintreetoken' , 'UserApiController@getBraintreeToken');

    //Testing Payment
    Route::post('/testpayment', 'UserApiController@testPayment');

	// Service Types Handle

	Route::post('/serviceList', 'UserApiController@service_list');

	Route::post('/singleService', 'UserApiController@single_service');

	// Payment modes

	Route::get('/getPaymentModes' , 'UserApiController@get_payment_modes');

	// Request Handle

	Route::post('/guestProviderList', 'UserApiController@guest_provider_list');

  //Fare Calculator

  Route::post('/fare_calculator', 'UserApiController@fare_calculator');

	// Automated request
	Route::post('/sendRequest', 'UserApiController@send_request');

	// Manual request
	Route::post('/manual_create_request', 'UserApiController@manual_create_request');


	Route::post('/cancelRequest', 'UserApiController@cancel_request');

	Route::post('/waitingRequestCancel' ,'UserApiController@waiting_request_cancel');

	Route::post('/requestStatusCheck', 'UserApiController@request_status_check');

	Route::post('/payment' , 'UserApiController@paynow');

	Route::post('/paybypaypal', 'UserApiController@paybypaypal');

	Route::post('/rateProvider', 'UserApiController@rate_provider');
	
	Route::post('/userWallet', 'UserApiController@user_wallet');
	
	Route::post('/history' , 'UserApiController@history');

	Route::post('/singleRequest' , 'UserApiController@single_request');

	// Favourite Providers

	Route::post('/addFavProvider' , 'UserApiController@add_fav_provider');

	Route::get('/favProviders' , 'UserApiController@fav_providers');

	Route::post('/deleteFavProvider' , 'UserApiController@delete_fav_provider');

	// Cards

	Route::post('/getUserPaymentModes', 'UserApiController@get_user_payment_modes');

	Route::post('/PaymentModeUpdate', 'UserApiController@payment_mode_update');

    Route::post('/message/get', 'UserApiController@message_get');

});

Route::get('/serviceList' , 'HomeController@service_list');

Route::group(['prefix' => 'providerApi'], function(){

	Route::post('/register','ProviderApiController@register');

	Route::post('/login','ProviderApiController@login');

	Route::get('/userdetails','ProviderApiController@profile');

	Route::post('/updateProfile', 'ProviderApiController@update_profile');

	Route::post('/forgotpassword', 'ProviderApiController@forgot_password');

	Route::post('/changePassword', 'ProviderApiController@changePassword');

	Route::get('/tokenRenew', 'ProviderApiController@tokenRenew');

	Route::post('locationUpdate' , 'ProviderApiController@location_update');

	Route::get('checkAvailableStatus' , 'ProviderApiController@check_available');

	Route::post('availableUpdate' , 'ProviderApiController@available_update');


	Route::post('/serviceAccept', 'ProviderApiController@service_accept');

	Route::post('/serviceReject', 'ProviderApiController@service_reject');

	Route::post('/providerStarted', 'ProviderApiController@providerstarted');

	Route::post('/arrived', 'ProviderApiController@arrived');

	Route::post('/serviceStarted', 'ProviderApiController@servicestarted');

    Route::post('/serviceCompleted', 'ProviderApiController@servicecompleted');

	Route::post('/codPaidConfirmation', 'ProviderApiController@cod_paid_confirmation');

	Route::post('/rateUser', 'ProviderApiController@rate_user');

	Route::post('/cancelrequest', 'ProviderApiController@cancelrequest');

	Route::post('/history', 'ProviderApiController@history');
	
	Route::post('/balancedriver', 'ProviderApiController@get_balance');

	Route::post('/singleRequest' , 'ProviderApiController@single_request');

	Route::post('/incomingRequest', 'ProviderApiController@get_incoming_request');

	Route::post('/requestStatusCheck', 'ProviderApiController@request_status_check');

  Route::get('/documents', 'ProviderApiController@documents');

  Route::post('/upload_documents', 'ProviderApiController@upload_documents');

  Route::get('/delete_document', 'ProviderApiController@delete_document');

});

Route::get('/assign_next_provider_cron' , 'ApplicationController@assign_next_provider_cron');

// Admin Routes
Route::group(['prefix' => 'admin'], function(){

    Route::get('login', 'Auth\AdminAuthController@showLoginForm')->name('admin.login');

    Route::post('login', 'Auth\AdminAuthController@login')->name('admin.login.post');

    Route::get('logout', 'Auth\AdminAuthController@logout')->name('admin.logout');

    // Registration Routes...

    Route::get('register', 'Auth\AdminAuthController@showRegistrationForm');

    Route::post('register', 'Auth\AdminAuthController@register');

    // Password Reset Routes...
    Route::get('password/reset/{token?}', 'Auth\AdminPasswordController@showResetForm');

    Route::post('password/email', 'Auth\AdminPasswordController@sendResetLinkEmail');

    Route::post('password/reset', 'Auth\AdminPasswordController@reset');

    Route::get('/', 'AdminController@dashboard')->name('admin.dashboard');

    Route::get('/profile', 'AdminController@profile')->name('admin.profile');

  	Route::post('/profile/save', 'AdminController@profile_process')->name('admin.save.profile');

  	Route::post('/change/password', 'AdminController@change_password')->name('admin.change.password');

    // Map View
    Route::get('/mapview', 'AdminController@mapview')->name('admin.mapview');

    Route::get('/user/mapview', 'AdminController@usermapview')->name('admin.usermapview');

    Route::get('/provider/details/{id}', 'AdminController@provider_details')->name('admin.provider_details');

    Route::get('/user/details/{id}', 'AdminController@user_details')->name('admin.user_details');

    // users

    Route::get('/users', 'AdminController@users')->name('admin.users');

    Route::get('/add/user', 'AdminController@add_user')->name('admin.add.user');

    Route::get('/edit/user', 'AdminController@edit_user')->name('admin.edit.user');

    Route::post('/add/user', 'AdminController@add_user_process')->name('admin.save.user');

    Route::get('/delete/user', 'AdminController@delete_user')->name('admin.delete.user');

    Route::get('/view/{option}/{id}', 'AdminController@user_details')->name('admin.view.user');

    Route::get('user_history/{option}/{id}', 'AdminController@user_history')->name('admin.user.history');

    Route::get('/request/view/{id}', 'AdminController@view_request')->name('admin.view.request');

    Route::get('test', 'AdminController@hello')->name('admin.test');


    // Provider

    Route::get('/providers', 'AdminController@providers')->name('admin.providers');

    Route::get('/add/provider', 'AdminController@add_provider')->name('admin.add.provider');

    Route::get('/edit/provider/{id}', 'AdminController@edit_provider')->name('admin.edit.provider');

    Route::post('/add/provider', 'AdminController@add_provider_process')->name('admin.save.provider');

    Route::get('/delete/provider/{id}', 'AdminController@delete_provider')->name('admin.delete.provider');

    Route::get('/provider/approve/{id}/{status}', 'AdminController@provider_approve')->name('admin.provider.approve');

		Route::get('/provider/history/{id}', 'AdminController@provider_history')->name('admin.provider.history');

    Route::get('/view/provider/{id}', 'AdminController@provider_view_details')->name('admin.provider.view');

		Route::get('/provider/documents', 'AdminController@provider_documents')->name('admin.provider.document');

    // Request details

    Route::get('/requests', 'AdminController@requests')->name('admin.requests');


    // User Payment details
    Route::get('user/payments' , 'AdminController@user_payments')->name('admin.user.payments');

    // Service type
    Route::get('/service_types', 'AdminController@service_types')->name('admin.service.types');

    Route::get('/add_service_type', 'AdminController@add_service_type')->name('admin.add.service.type');

    Route::post('/add_service_type', 'AdminController@add_service_process')->name('admin.add.service.process');

    Route::get('/edit_service/{id}', 'AdminController@edit_service')->name('admin.edit.service');

    Route::get('/delete_service/{id}', 'AdminController@delete_service')->name('admin.delete.service');

    //Documnets

    Route::get('/documents', 'AdminController@documents')->name('admin.documents');

    Route::get('/add_document', 'AdminController@add_document')->name('admin.add_document');

    Route::post('/add_document_process', 'AdminController@add_document_process')->name('admin.add_document_process');

    Route::get('/document_edit/{id}', 'AdminController@document_edit')->name('admin.document_edit');

    Route::get('/delete_document/{id}', 'AdminController@delete_document')->name('admin.document_delete');


    //Reviews & Ratings

    Route::get('/user_reviews', 'AdminController@user_reviews')->name('admin.user_reviews');

    Route::get('/provider_reviews', 'AdminController@provider_reviews')->name('admin.provider_reviews');

    Route::get('/provider_review_delete/{id}', 'AdminController@delete_provider_reviews')->name('admin.provider_review_delete');

    Route::get('/user_review_delete/{id}', 'AdminController@delete_user_reviews')->name('admin.user_review_delete');

    //payments
    Route::get('/payment', 'AdminController@payment')->name('admin.payments');

    // Settings

    Route::get('/settings', 'AdminController@settings')->name('admin.settings');

    Route::get('payment/settings' , 'AdminController@payment_settings')->name('admin.payment.settings');

    Route::post('settings' , 'AdminController@settings_process')->name('admin.save.settings');



});
