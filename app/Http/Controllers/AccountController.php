<?php 
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\SignupField;
use App\Models\UserAccountData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Socialite;
use Spatie\Permission\Models\Role;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\TokenpassClient\Facade\Tokenpass;
use User, Input, Session, Hash, Redirect, Exception, Config, URL, Response;

class AccountController extends Controller {

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
        $this->middleware('tls');
	}


    /**
     * Show the welcome page or redirect
     */
    public function welcome() {
        // ensure the user is signed in.  If not, then redirect to the login page
        $user = Auth::user();
        if (!$user) { return Redirect::route('account.auth'); }

        return Redirect::route('home');
    }

    public function termsAndConditions()
    {
        $user = Auth::user();

        $accept_cta = false;

        if($user) {
            $accept_cta = !($user->checkTACAccept());
        }

        return view('terms_and_conditions.terms_and_conditions', ['user' => $user, 'accept_cta' => $accept_cta]);
    }
    
    public function acceptTac() {
        $user = Auth::user();
        if($user) {
            
            $new_accept = $user->acceptTAC();

            if($new_accept) {
                $return_route = Session::get('return_route_after_tac');
                if($return_route && ($return_route != route('terms-and-conditions'))) {
                    Session::put('return_route_after_tac', null);
                    return redirect($return_route);
                } else {
                    return redirect()->route('home');
                }
                // return redirect()->route('home');
            } else {
                Session::flash('message', 'Error accepting Terms and Conditions. Please refresh and try again.');
                Session::flash('message-class', 'alert-danger');
                return Redirect::back(); 
            }
        } else {
            return Redirect::back();
        }
    }

    public function getComplete() {
        $user = Auth::user();
        if (!$user) { return redirect('/account/welcome'); }
        \Session::put('embed_body', false);
        $fields = SignupField::with('options')->with('condition')->orderBy('position', 'ASC')->get();
        return view('user_meta.get_user_meta_data', ['user' => $user, 'fields' => $fields]);
    }

    public function complete(Request $request) {
        if($request->user()->getCurrentUserAccountData()) {
            return redirect()->route('home');
        }

        $user = $request->user();
        /* VALIDATION */
        $fields = $request->input('fields');
        foreach ($fields as $key => $field) {
            $field_object = SignupField::with('condition')->find($field['id']);
            if(empty($field['value']) && $field_object->required && $field_object->type != 'toggle') {
                if(!$field_object->condition()->exists()) {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'Field ' . $field['name'] . ' is required',
                    ], 400);
                }
                $condition_met = true;
                foreach ($fields as $compare_field) {
                    if($field_object->condition->field_to_compare_id == $compare_field['id'] && (empty($compare_field['value']) || $field_object->condition->value != $compare_field['value'])) {
                        $condition_met = false;
                    }
                }
                if($condition_met) {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'Field ' . $field['name'] . ' is required',
                    ], 400);
                }
            }
        }
        // Save
        foreach ($fields as $key => $field) {
            $field_object = SignupField::with('condition')->find($field['id']);
            if(!empty($field['value']) || $field_object->type == 'toggle') {
                $user->saveData($field_object, $field['value'] ?? false);
            }
        }

        try {
            if($user->checkTACAccept()) {
                User::sendApproveAccountEmailToAdmins($user->id);
            }
            return response()->json([
                'status' => 'fail',
                'message' => 'Account data saved successfully',
            ], 201);
        }
        catch (Exception $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'There was an error saving the account data',
            ], 500);
        }
        
    }

    public function admin_users() {
        $user = Auth::user();
        if (!$user) { return redirect('/account/welcome'); }

        if(!$user->isModerator) { return redirect('/home'); }

        $all_users = User::where('declined', 0)->get()->reverse();

        return view('admin.admin-users-dashboard', ['user' => $user, 'all_users' => $all_users]);
    }

    public function admin_user($userId) {
        $user = Auth::user();
        if (!$user) { return redirect('/account/welcome'); }

        if(!$user->isModerator) { return redirect('/home'); }

        $this_user = User::find($userId);

        return view('admin.admin-user-show', ['user' => $user, 'this_user' => $this_user]);
    }


    public function approve(Request $request, User $user) {
        $approved = $user->approve();
        return redirect(route('account.admin.users'));
    }

    public function decline(Request $request, User $user) {
        $declined = $user->decline();
        return redirect(route('account.admin.users'));
    }

    public function make_moderator(Request $request, User $user) {
        if($user->hasRole('moderator')) {
            Session::flash('message', 'User is already a moderator.');
            Session::flash('message-class', 'alert-danger');
            return redirect(route('account.admin.users'));
        }
        $user->makeModerator();
        return redirect(route('account.admin.users'));
    }

    public function make_admin(Request $request, User $user) {
        if($user->admin) {
            Session::flash('message', 'User is already an admin.');
            Session::flash('message-class', 'alert-danger');
            return redirect(route('account.admin.users'));
        }
        $user->makeAdmin();
        return redirect(route('account.admin.users'));
    }

    public function remove_moderator(Request $request, User $user) {
        $user->removeRole('moderator');
        return redirect(route('account.admin.users'));
    }

    public function remove_admin(Request $request, User $user) {
        $user->removeRole('admin');
        $user->admin = 0;
        $user->save();
        return redirect(route('account.admin.users'));
    }

    /**
     * Login or redirect
     */
    public function login() {

        // if the user is already signed in, go straight to the welcome page
        $user = Auth::user();
        if ($user) { return redirect('/account/welcome'); }
		\Session::put('embed_body', false);
        return view('account.login', ['user' => $user]);
    }


    /**
     * Logout
     */
    public function logout() {
        Auth::logout();
        return Redirect::route('account.auth');
    }


    /**
     * Redirect the user to Tokenpass to get authorization
     */
    public function redirectToProvider()
    {
        // set scopes
        Socialite::scopes(explode(',', config('tokenpass.scopes')));

        // and redirect
        return Socialite::redirect();
    }

    /**
     * Obtain the user information from Accounts.
     *
     * This is the route called after Tokenpass has granted (or denied) permission to this application
     * This application is now responsible for loading the user information from Tokenpass and storing
     * it in the local user database.
     *
     * @return Response
     */
    public function handleProviderCallback(Request $request)
    {

        try {
            // retrieve the user from Tokenpass
            $oauth_user = Socialite::user();


            // get all the properties from the oAuth user object
            $tokenly_uuid       = $oauth_user->id;
            $oauth_token        = $oauth_user->token;
            $username           = $oauth_user->user['username'];
            $name               = $oauth_user->user['name'];
            $email              = $oauth_user->user['email'];
            $email_is_confirmed = $oauth_user->user['email_is_confirmed'];
            
            // find an existing user based on the credentials provided
            $existing_user = User::where('tokenly_uuid', $tokenly_uuid)->first();

            // if an existing user wasn't found, we might need to find a user to merge into
            $mergable_user = ($existing_user ? null : User::where('username', $username)->orWhere('email', $email)->where('tokenly_uuid', null)->first());
            $used_user = false;
            
            if ($existing_user) {
                // update the user
                $existing_user->update(['oauth_token' => $oauth_token, 'name' => $name, 'email' => $email, 
										'tokenly_uuid' => $tokenly_uuid, 'username' => $username ]);

                $used_user = $existing_user;
                
            } else if ($mergable_user) {
                // an existing user was found with a matching username
                //  migrate it to the tokenly accounts control

                if ($mergable_user['tokenly_uuid']) {
                    throw new Exception("Can't merge a user already associated with a different tokenly account", 1);
                }

                // update if needed
                $mergable_user->update(['name' => $name, 'email' => $email, 'oauth_token' => $oauth_token,
										'username' => $username, 'tokenly_uuid' => $tokenly_uuid]);

                $used_user = $mergable_user;

            } else {
                // no user was found - create a new user based on the information we received
                $create_data = ['tokenly_uuid' => $tokenly_uuid, 'oauth_token' => $oauth_token, 'name' => $name, 'username' => $username, 'email' => $email ];
                $new_user = User::create($create_data);
                
                $used_user = $new_user;
            }
            
            Auth::login($used_user);
            return redirect('/account/login');

        } catch (Exception $e) {
            // some unexpected error happened
            EventLog::logError('account.authFailed', $e);
            return view('account.authorization-failed', ['error_msg' => 'Failed to authenticate this user.']);
        }
    }


    /**
     * Obtain the user information from Tokenpass.
     *
     * And sync it with our local database
     *
     * @return Response
     */
    public function sync(Request $request)
    {

        try {
            $logged_in_user = Auth::user();

            $oauth_user = null;
            if ($logged_in_user['oauth_token']) {
                $oauth_user = Socialite::getUserByExistingToken($logged_in_user['oauth_token']);
            }

            if ($oauth_user) {
                $tokenly_uuid       = $oauth_user->id;
                $oauth_token        = $oauth_user->token;
                $username           = $oauth_user->user['username'];
                $name               = $oauth_user->user['name'];
                $email              = $oauth_user->user['email'];
                $email_is_confirmed = $oauth_user->user['email_is_confirmed'];

                // find an existing user based on the credentials provided
                $existing_user = User::where('tokenly_uuid', $tokenly_uuid);
                if ($existing_user) {
                    // update
                    $existing_user->update(['name' => $name, 'email' => $email, 'username' => $username/* etc */ ]);
                }

                $synced = true;
            } else {
                // not able to sync this user
                $synced = false;
            }

            return view('account.sync', ['synced' => $synced, 'user' => $logged_in_user, ]);

        } catch (Exception $e) {
            return view('account.sync-failed', ['error_msg' => 'Failed to sync this user.']);
        }
    }    

}
