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

//distributions
Route::middleware(['requireApproval'])->group(function () {
    Route::get('home', array('as' => 'home', 'uses' => 'HomeController@index'));
    Route::get('distributions/new', array('as' => 'distribute.new', 'middleware' => ['auth'], 'uses' => 'DistributeController@newDistribution'));
    Route::post('distribute', array('as' => 'distribute.post', 'uses' => 'DistributeController@submitDistro'));
    Route::get('distribute/_status-info', array('as' => 'distribute.status-info', 'uses' => 'DistributeController@getStatusInfo'));
    Route::get('distribute/{address}/_info', array('as' => 'distribute.details.info', 'uses' => 'DistributeController@getDetailsInfo'));
    Route::post('distribute/{address}', array('as' => 'distribute.details.update', 'uses' => 'DistributeController@updateDetails'));
    Route::get('distribute/delete/{id}', array('as' => 'distribute.delete', 'uses' => 'DistributeController@deleteDistribution'));
    Route::get('distribute/duplicate/{address}', array('as' => 'distribute.duplicate', 'uses' => 'DistributeController@duplicateDistribution'));
});

// public
Route::get('distributions', array('as' => 'distribute.history', 'uses' => 'DistributeController@getDistributionsHistory'));
Route::get('distribute/{address}', array('as' => 'distribute.details', 'uses' => 'DistributeController@getDetails'));
Route::get('official-distributions', array('as' => 'distribute.official_fldc_history', 'uses' => 'DistributeController@getOfficialFldcDistributionsHistory'));

// recipients
Route::middleware(['tls', 'auth'])->group(function () {
    Route::get('recipient/dashboard', ['as' => 'recipient.dashboard', 'uses' => 'RecipientController@index']);
    Route::get('recipient/withdraw', ['as' => 'recipient.withdraw', 'uses' => 'RecipientController@withdraw']);
    Route::post('recipient/withdraw', ['as' => 'recipient.withdraw.post', 'uses' => 'RecipientController@processWithdraw']);
});


//Terms of use
Route::get('/terms-and-conditions', array('as' => 'terms-and-conditions', 'uses' => 'AccountController@termsAndConditions'));
Route::post('/account/accept_tac', array('as' => 'account.accept_tac', 'uses' => 'AccountController@acceptTac'));



//tokenly accounts stuff
// The welcome page for the user that requires a logged in user
$router->get('/account/welcome', 'AccountController@welcome');

// routes for logging in and logging out
$router->get('/account/login', array('as' => 'account.auth', 'uses' => 'AccountController@login'));
$router->get('/account/logout', array('as' => 'account.auth.logout', 'uses' => 'AccountController@logout'));


Route::get('/account/complete', array('as' => 'account.get_complete', 'middleware' => ['auth'], 'uses' => 'AccountController@getComplete'));
Route::post('/account/complete', array('as' => 'account.complete', 'middleware' => ['auth'], 'uses' => 'AccountController@complete'));


//Admin
Route::get('/account/admin/users', array('as' => 'account.admin.users', 'uses' => 'AccountController@admin_users'));
Route::get('/account/admin/user/{id}', array('as' => 'account.admin.user', 'uses' => 'AccountController@admin_user'));

Route::get('/account/admin/users/approve/{user}', array('as' => 'account.admin.users.approve', 'middleware' => ['auth', 'bindings', \App\Http\Middleware\Moderator::class], 'uses' => 'AccountController@approve'));
Route::get('/account/admin/users/decline/{user}', array('as' => 'account.admin.users.decline', 'middleware' => ['auth', 'bindings', \App\Http\Middleware\Moderator::class], 'uses' => 'AccountController@decline'));
Route::get('/account/admin/users/make_admin/{user}', array('as' => 'account.admin.users.make_admin', 'middleware' => ['auth', 'bindings', \App\Http\Middleware\Admin::class], 'uses' => 'AccountController@make_admin'));
Route::get('/account/admin/users/make_moderator/{user}', array('as' => 'account.admin.users.make_moderator', 'middleware' => ['auth', 'bindings', \App\Http\Middleware\Admin::class], 'uses' => 'AccountController@make_moderator'));
Route::get('/account/admin/users/remove_admin/{user}', array('as' => 'account.admin.users.remove_admin', 'middleware' => ['auth', 'bindings', \App\Http\Middleware\Admin::class], 'uses' => 'AccountController@remove_admin'));
Route::get('/account/admin/users/remove_moderator/{user}', array('as' => 'account.admin.users.remove_moderator', 'middleware' => ['auth', 'bindings', \App\Http\Middleware\Admin::class], 'uses' => 'AccountController@remove_moderator'));

Route::get('/account/admin/fields', array('as' => 'account.admin.fields',  'middleware' => ['auth', 'bindings', \App\Http\Middleware\Admin::class],'uses' => 'SignupFieldsController@index'));
Route::post('/account/admin/fields', array('as' => 'account.admin.fields',  'middleware' => ['auth', 'bindings', \App\Http\Middleware\Admin::class],'uses' => 'SignupFieldsController@create'));
Route::post('/account/admin/fields/positions', array('as' => 'account.admin.fields.positions','middleware' => ['auth', 'bindings', \App\Http\Middleware\Admin::class], 'uses' => 'SignupFieldsController@updateOrder'));
Route::delete('/account/admin/fields/{field}', array('as' => 'account.admin.fields.positions', 'middleware' => ['auth', 'bindings', \App\Http\Middleware\Admin::class],'uses' => 'SignupFieldsController@delete'));

//END Admin


// This is a route to sync the user with their Tokenpass information
//   Redirect the user here to update their local user information with their Tokenpass information
$router->get('/account/sync', 'AccountController@sync');

// oAuth handlers
Route::get('/account/authorize', array('as' => 'account.authorize', 'uses' => 'AccountController@redirectToProvider'));
Route::get('/account/authorize/callback', array('as' => 'account.authcallback', 'uses' => 'AccountController@handleProviderCallback'));

//API key management
Route::get('api-keys', array('as' => 'account.api-keys', 'uses' => 'APIKeyController@index'));
Route::get('api-keys/create', array('as' => 'account.api-keys.create', 'uses' => 'APIKeyController@create'));
Route::get('api-keys/delete/{key}', array('as' => 'account.api-keys.delete', 'uses' => 'APIKeyController@delete'));

//API methods
Route::middleware(['tls','cors'])->group(function () {
    Route::middleware(['auth.api'])->group(function () {
        Route::get('api/v1/distribute', array('as' => 'api.distribute.list', 'uses' => 'APIController@getDistributionList'));
        Route::get('api/v1/distribute/{id}', array('as' => 'api.distribute.get', 'uses' => 'APIController@getDistribution'));
        Route::get('api/v1/self', array('as' => 'api.user-info', 'uses' => 'APIController@getLoggedAPIUserInfo'));
    });

    Route::middleware(['auth.api.signed'])->group(function () {
        Route::post('api/v1/distribute/create', array('as' => 'api.distribute.create', 'uses' => 'APIController@createDistribution'));
        Route::patch('api/v1/distribute/{id}', array('as' => 'api.distribute.update', 'uses' => 'APIController@updateDistribution'));
        Route::delete('api/v1/distribute/{id}', array('as' => 'api.distribute.delete', 'uses' => 'APIController@deleteDistribution'));
    });
});

Route::get('/', ['middleware' => 'tls', function () {
    return view('welcome');
}]);
