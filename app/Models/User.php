<?php
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
class User extends Model implements AuthenticatableContract, CanResetPasswordContract
{
	use Authenticatable, CanResetPassword;
	
	protected $fillable = array('name','email','username','tokenly_uuid','oauth_token');
	
	public static $cur_user = false;
	
	public static function currentUser()
	{
		if(!self::$cur_user){
			self::$cur_user = Auth::user();
		}
		return self::$cur_user;
	}

	public static function isAdmin($userId = 0)
	{
		if($userId == 0){
			$user = Auth::user();
		}
		else{
			$user = User::find($userId);
		}
		if(!$user OR $user->admin == 0){
			return false;
		}
		return true;
	}
}
