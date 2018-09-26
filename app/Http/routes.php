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
		
Route::post('hooks/distribution/send', array('as' => 'hooks.distro.send',
		'uses' => 'WebhookController@DistributorSend'));		
		
Route::post('hooks/refuel', array('as' => 'hooks.refuel',
		'uses' => 'WebhookController@FuelAddressDeposit'));		
		
Route::post('hooks/unfuel', array('as' => 'hooks.unfuel',
'uses' => 'WebhookController@DebitFuelAddress'));		
		
//distributions
Route::post('distribute', array('as' => 'distribute.post', 'uses' => 'DistributeController@submitDistribution'));
Route::get('distribute/_status-info', array('as' => 'distribute.status-info', 'uses' => 'DistributeController@getStatusInfo'));
Route::get('distribute/{address}', array('as' => 'distribute.details', 'uses' => 'DistributeController@getDetails'));
Route::get('distribute/{address}/_info', array('as' => 'distribute.details.info', 'uses' => 'DistributeController@getDetailsInfo'));
Route::post('distribute/{address}', array('as' => 'distribute.details.update', 'uses' => 'DistributeController@updateDetails'));
Route::get('distribute/delete/{id}', array('as' => 'distribute.delete', 'uses' => 'DistributeController@deleteDistribution'));
Route::get('distribute/duplicate/{address}', array('as' => 'distribute.duplicate', 'uses' => 'DistributeController@duplicateDistribution'));	


//tokenly accounts stuff
// The welcome page for the user that requires a logged in user
Route::get('/account/welcome', 'AccountController@welcome');

// routes for logging in and logging out
Route::get('/account/login', array('as' => 'account.auth', 'uses' => 'AccountController@login'));
Route::get('/account/logout', array('as' => 'account.auth.logout', 'uses' => 'AccountController@logout'));

// This is a route to sync the user with their Tokenpass information
//   Redirect the user here to update their local user information with their Tokenpass information
Route::get('/account/sync', 'AccountController@sync');

// oAuth handlers
Route::get('/account/auth', array('as' => 'account.authorize', 'uses' => 'AccountController@redirectToProvider'));
Route::get('/account/auth/callback', array('as' => 'account.authcallback', 'uses' => 'AccountController@handleProviderCallback'));

//API key management
Route::get('api-keys', array('as' => 'account.api-keys', 'uses' => 'APIKeyController@index'));
Route::get('api-keys/create', array('as' => 'account.api-keys.create', 'uses' => 'APIKeyController@create'));
Route::get('api-keys/delete/{key}', array('as' => 'account.api-keys.delete', 'uses' => 'APIKeyController@delete'));

//API methods
Route::post('api/v1/distribute/create', array('as' => 'api.distribute.create', 'uses' => 'APIController@createDistribution'));
Route::get('api/v1/distribute', array('as' => 'api.distribute.list', 'uses' => 'APIController@getDistributionList'));
Route::get('api/v1/distribute/{id}', array('as' => 'api.distribute.get', 'uses' => 'APIController@getDistribution'));
Route::patch('api/v1/distribute/{id}', array('as' => 'api.distribute.update', 'uses' => 'APIController@updateDistribution'));
Route::delete('api/v1/distribute/{id}', array('as' => 'api.distribute.delete', 'uses' => 'APIController@deleteDistribution'));
Route::get('api/v1/self', array('as' => 'api.user-info', 'uses' => 'APIController@getLoggedAPIUserInfo'));

Route::get('/', ['middleware' => 'tls', function () {
    return view('welcome');
}]);
