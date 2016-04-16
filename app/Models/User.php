<?php
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Models\Distribution, Models\DistributionTx;
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
	
	public static function getDashInfo($userId = 0)
	{
		if($userId == 0){
			$user = Auth::user();
		}
		else{
			$user = User::find($userId);
		}
		if(!$user){
			return false;
		}
		
		$output = array();
		$output['fuel_address'] = '';
		$output['fuel_balance'] = 0;
		$output['fuel_spent'] = 0;
		
		$distros = Distribution::where('user_id', $user->id)->get();
		$output['distribution_history'] = $distros;
		$output['distributions_complete'] = 0;
		$output['distribution_txs'] = 0;
		if($distros){
			foreach($distros as $distro){
				if($distro->complete == 1){
					$output['distributions_complete']++;
				}
				$output['distribution_txs'] += $distro->countComplete();
			}
		}

		return $output;
	}
}
