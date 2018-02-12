<?php
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Models\Distribution, Models\DistributionTx;
use Tokenly\CurrencyLib\CurrencyUtil;
use Tokenly\LaravelApiProvider\Contracts\APIPermissionedUserContract;
use Tokenly\LaravelApiProvider\Model\APIUser;
use Tokenly\LaravelApiProvider\Model\Traits\Permissioned;
class User extends APIUser implements AuthenticatableContract, CanResetPasswordContract, APIPermissionedUserContract
{
	use Authenticatable, CanResetPassword;
    use Permissioned;
	
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
	
	public static function getDashInfo($userId = 0, $no_history = false)
	{
        $time_start = microtime(true);
		if($userId == 0){
			$user = Auth::user();
		}
		else{
			$user = User::find($userId);
		}
		if(!$user){
			return false;
		}
        Log::debug('Starting getDashInfo task');
        $output = array();
		$output['fuel_address'] = User::getFuelAddress($user->id);
		$output['fuel_balance'] = intval(UserMeta::getMeta($user->id, 'fuel_balance'));
		$output['fuel_pending'] = intval(UserMeta::getMeta($user->id, 'fuel_pending'));
		$output['fuel_spent'] = intval(UserMeta::getMeta($user->id, 'fuel_spent'));
        $output['fuel_balanceFloat'] = CurrencyUtil::satoshisToValue($output['fuel_balance']);
        $output['fuel_pendingFloat'] = CurrencyUtil::satoshisToValue($output['fuel_pending']);;
        $output['fuel_spentFloat'] = CurrencyUtil::satoshisToValue($output['fuel_spent']);;

        Log::debug('Total execution time to get the user fuel address: ' . (microtime(true) - $time_start));
        //TODO: remove debug code
        if(!$no_history){
            $distros = Distribution::where('user_id', $user->id)->orderBy('id', 'desc')->get();
            $output['distribution_history'] = $distros;
            $output['distributions_complete'] = 0;
            $output['distribution_txs'] = 0;
            if($distros){
                $output['distribution_count'] = count($distros);
                foreach($distros as $distro){
                    if($distro->complete == 1){
                        $output['distributions_complete']++;
                    }
                    $output['distribution_txs'] += $distro->countComplete();
                }
            }
        }
        Log::debug('Total execution time in seconds: ' . (microtime(true) - $time_start));
		return $output;
	}
	
	public static function getFuelAddress($userId)
	{
		$get_address = UserMeta::getMeta($userId, 'fuel_address');
		if(!$get_address){
			$xchain = xchain();
			try{
				$new_address = $xchain->newPaymentAddress();
				$monitor = false;
				if($new_address AND isset($new_address['address'])){
					$nonce = hash('sha256', $new_address['id'].'_'.$userId);
					$monitor = $xchain->newAddressMonitor($new_address['address'], route('hooks.refuel').'?nonce='.$nonce);
					$send_monitor = $xchain->newAddressMonitor($new_address['address'], route('hooks.unfuel').'?nonce='.$nonce, 'send');
				}
			}
			catch(\Exception $e){
				\Log::error('Error getting user '.$userId.' fuel address: '.$e->getMessage());
				return false;
			}
			
			if($new_address AND isset($new_address['address']) AND $monitor AND $send_monitor){
				UserMeta::setMeta($userId, 'fuel_address', $new_address['address']);
				UserMeta::setMeta($userId, 'fuel_address_uuid', $new_address['id']);
				UserMeta::setMeta($userId, 'fuel_address_monitor_receive', $monitor['id']);
				UserMeta::setMeta($userId, 'fuel_address_monitor_send', $send_monitor['id']);
				UserMeta::setMeta($userId, 'fuel_balance', 0);
				UserMeta::setMeta($userId, 'fuel_pending', 0);
				UserMeta::setMeta($userId, 'fuel_spent', 0);
				return $new_address['address'];
			}
			return false;
		}
		return $get_address;
	}

    public function firstActiveAPIKey() {
        return APIKey::where('user_id', $this['id'])
            ->where('active', 1)
            ->orderBy('id')
            ->first();
    }
}
