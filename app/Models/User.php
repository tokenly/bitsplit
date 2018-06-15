<?php
use App\Libraries\Substation\UserWalletManager;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Models\Distribution, Models\DistributionTx;
use Tokenly\CurrencyLib\CurrencyUtil;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\SubstationClient\SubstationClient;
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
	
	public static function getDashInfo($userId = 0, $no_history = false)
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
		$output['fuel_address'] = User::getFuelAddress($user->id);
		$output['fuel_balance'] = intval(UserMeta::getMeta($user->id, 'fuel_balance'));
		$output['fuel_pending'] = intval(UserMeta::getMeta($user->id, 'fuel_pending'));
		$output['fuel_spent'] = intval(UserMeta::getMeta($user->id, 'fuel_spent'));
        $output['fuel_balanceFloat'] = CurrencyUtil::satoshisToValue($output['fuel_balance']);
        $output['fuel_pendingFloat'] = CurrencyUtil::satoshisToValue($output['fuel_pending']);;
        $output['fuel_spentFloat'] = CurrencyUtil::satoshisToValue($output['fuel_spent']);;
		
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

		return $output;
	}
	
    public static function getFuelAddress($userId)
    {
        $fuel_address = UserMeta::getMeta($userId, 'fuel_address');
        if (!$fuel_address) {
            $fuel_address = self::newFuelAddress($userId);
        }
        return $fuel_address;
    }

    public static function newFuelAddress($userId)
    {
        $substation = app(SubstationClient::class);

        // get or create a substation wallet for this user
        $user = User::find($userId);
        $wallet_uuid = app(UserWalletManager::class)->ensureSubstationWalletForUser($user);

        try {
            $new_address = $substation->allocateAddress($wallet_uuid);
        } catch (\Exception $e) {
            EventLog::logError('fuelAddress.error', $e, [
                'userId' => $userId,
            ]);
            \Log::error('Error getting user ' . $userId . ' fuel address: ' . $e->getMessage());
            return false;
        }

        EventLog::debug('fuelAddress.new', [
            'userId' => $userId,
            'address' => $new_address['address'],
            'uuid' => $new_address['uuid'],
        ]);

        // "uuid": "5a83a57f-a56c-4c0b-afd4-8572516bf2fb",
        // "address": "1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j",
        UserMeta::setMeta($userId, 'fuel_address', $new_address['address']);
        UserMeta::setMeta($userId, 'fuel_address_uuid', $new_address['uuid']);
        UserMeta::setMeta($userId, 'fuel_balance', 0);
        UserMeta::setMeta($userId, 'fuel_pending', 0);
        UserMeta::setMeta($userId, 'fuel_spent', 0);

        return $new_address['address'];
    }


    public function firstActiveAPIKey() {
        return APIKey::where('user_id', $this['id'])
            ->where('active', 1)
            ->orderBy('id')
            ->first();
    }
}
