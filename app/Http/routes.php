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

//main pages
Route::get('home', array('as' => 'home', 'uses' => 'HomeController@index'));

//webhooks
Route::post('hooks/distribution/deposit', array('as' => 'hooks.distro.deposit',
		'uses' => 'WebhookController@DistributorDeposit'));
		
//distributions
Route::post('distribute', array('as' => 'distribute.post', 'uses' => 'DistributeController@submitDistribution'));
Route::get('distribute/{address}', array('as' => 'distribute.details', 'uses' => 'DistributeController@getDetails'));
		

//tokenly accounts stuff
// The welcome page for the user that requires a logged in user
$router->get('/account/welcome', 'AccountController@welcome');

// routes for logging in and logging out
$router->get('/account/login', array('as' => 'account.auth', 'uses' => 'AccountController@login'));
$router->get('/account/logout', array('as' => 'account.auth.logout', 'uses' => 'AccountController@logout'));

// This is a route to sync the user with their Tokenly Accounts information
//   Redirect the user here to update their local user information with their Tokenly Accounts information
$router->get('/account/sync', 'AccountController@sync');

// oAuth handlers
$router->get('/account/authorize', array('as' => 'account.authorize', 'uses' => 'AccountController@redirectToProvider'));
$router->get('/account/authorize/callback', array('as' => 'account.authcallback', 'uses' => 'AccountController@handleProviderCallback'));



Route::get('/', function () {
    return view('welcome');
});
