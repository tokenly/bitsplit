<?php 
namespace App\Http\Controllers;
use User, Auth, Config, APIKey, Session, Redirect;
class APIKeyController extends Controller {
    
	public function __construct()
	{
        parent::__construct();
		$this->middleware('auth');
	}

	public function index()
	{
		$user = Auth::user();
        $keys = APIKey::where('user_id', $user->id)->get();

		return view('api-keys', array('user' => $user, 'keys' => $keys));
	}
    
    public function create()
    {
        $user = Auth::user();
        $generate = APIKey::generate($user);
        if(!$generate){
            Session::flash('message', 'Error generating API key pair.');
            Session::flash('message-class', 'alert-danger'); 
        }
        else{
            Session::flash('message', 'API key pair generated!');
            Session::flash('message-class', 'alert-success');
        }
        return Redirect::route('account.api-keys');
    }
    
    public function delete($key)
    {
        $user = Auth::user();
        $getKey = APIKey::where('client_key', $key)->where('user_id', $user->id)->first();
        if(!$getKey){
            Session::flash('message', 'Invalid API key pair.');
            Session::flash('message-class', 'alert-danger'); 
        }
        else{
            $delete = $getKey->delete();
            if(!$delete){
                Session::flash('message', 'Error deleting API key pair.');
                Session::flash('message-class', 'alert-danger'); 
            }
            else{
                Session::flash('message', 'API key pair deleted!');
                Session::flash('message-class', 'alert-success');
            }
        }
        return Redirect::route('account.api-keys');
    }
}
