<?php
use App\Libraries\Substation\Substation;
use App\Libraries\Substation\UserWalletManager;
use App\Models\UserAccountData;
use App\Models\UserMeta;
use App\Repositories\EscrowAddressLedgerEntryRepository;
use App\Repositories\EscrowAddressRepository;
use App\Repositories\UserRepository;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Models\Distribution, Models\DistributionTx;
use Tokenly\CurrencyLib\CurrencyUtil;
use Tokenly\LaravelApiProvider\Contracts\APIPermissionedUserContract;
use Tokenly\LaravelApiProvider\Model\APIUser;
use Tokenly\LaravelApiProvider\Model\Traits\Permissioned;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\SubstationClient\SubstationClient;
use \Spatie\Permission\Models\Role;

class User extends APIUser implements AuthenticatableContract, CanResetPasswordContract
{
	use Authenticatable, CanResetPassword;
    use Permissioned;
    use \Spatie\Permission\Traits\HasRoles;

	protected $fillable = array('name','email','username','tokenly_uuid','oauth_token');

	public static $cur_user = false;
	
	public static function currentUser()
	{
		if(!self::$cur_user){
			self::$cur_user = Auth::user();
		}
		return self::$cur_user;
	}
	
    public function checkTACAccept()
    {
        $user = Auth::user();

        if(isset($user->tac_accept) AND $user->tac_accept != NULL)
        {
            return true;
        } else {
            return false;
        }
    }

    public function acceptTAC()
    {
        $user = Auth::user();
        $user->tac_accept = new DateTime();
        try {
            $accept = $user->save();
            if($accept) {
                return true;
            } else {
                return false;
            }

            $user_account_data = User::getUserAccountData($user->id);
            if($user_account_data) {
                User::sendApproveAccountEmailToAdmins($user->id);
            }
        }
        catch (Exception $e) {
            return false;
        }
    }

    public function saveUserAccountData($data)
    {
        $user = Auth::user();

        if(!$user) {
            return false;
        }

        $user_account_data = UserAccountData::where('user_id', $user->id);

        if($user_account_data) {
            $user_account_data = $user_account_data->first();
        }

        if(!$user_account_data) {
            $user_account_data = UserAccountData::create();
            $user_account_data->user_id = $user->id;
        }

        if(isset($data['first_name'])) {
            $user_account_data->first_name = $data['first_name'];
        }

        if(isset($data['last_name'])) {
            $user_account_data->last_name = $data['last_name'];
        }

        if(isset($data['company_name'])) {
            $user_account_data->company_name = $data['company_name'];
        }

        if(isset($data['website'])) {
            $user_account_data->website = $data['website'];
        }

        if(isset($data['email'])) {
            $user_account_data->email = $data['email'];
        }

        if(isset($data['company_address'])) {
            $user_account_data->company_address = $data['company_address'];
        }

        if(isset($data['token_name'])) {
            $user_account_data->token_name = $data['token_name'];
        }

        if(isset($data['token_description'])) {
            $user_account_data->token_description = $data['token_description'];
        }

        if(isset($data['token_exchanges_listed'])) {
            $user_account_data->token_exchanges_listed = $data['token_exchanges_listed'];
        }

        if(isset($data['phone_number'])) {
            $user_account_data->phone_number = $data['phone_number'];
        }

        try {
            $accept = $user_account_data->save();

            if($user->checkTACAccept()) {
                User::sendApproveAccountEmailToAdmins($user->id);
            }

            if($accept) {
                return true;
            } else {
                return false;
            }
        }
        catch (Exception $e) {
            return false;
        }
    }

    public function saveData(\App\Models\SignupField $field, $value)
    {
        if(is_array($value)) {
            DB::table('user_data')->insert(['field_id' => $field->id, 'user_id' => $this->id, 'value' => implode(", ", $value)]);
        } else {
            DB::table('user_data')->insert(['field_id' => $field->id, 'user_id' => $this->id, 'value' => $value]);
        }
    }

    public function getCurrentUserAccountData()
    {
        //
        $user_data =  DB::table('user_data')->where('user_id', $this->id)->get();
        if(!$user_data->isEmpty()) {
            return $user_data;
        }
        //
        $user_account_data = UserAccountData::where('user_id', $this->id);
        if($user_account_data) {
            $user_account_data = $user_account_data->first();
            return $user_account_data;
        }
    }

    public static function needsApprovalCount()
    {
        $users_that_need_approval = User::whereNull('approval_admin_id')->where('declined', 0)->get();
        $users_that_need_approval = $users_that_need_approval->whereNotIn('tac_accept', [null]);

        return $users_that_need_approval->count();
    }

    public static function getUserAccountData($userId =0)
    {
        if($userId == 0){
            $user = Auth::user();
        }
        else{
            $user = User::find($userId);
        }

        $user_account_data = UserAccountData::where('user_id', $user->id);

        if($user_account_data) {
            $user_account_data = $user_account_data->first();
            return $user_account_data;
        } else {
            return null;
        }
    }

    public function getAccountData() {
	    return \App\Models\UserData::where('user_id', $this->id)->get();
    }

    public static function getUserData()
    {

        $user_account_data = UserAccountData::where('user_id', $this->id);
        if($user_account_data) {
            $user_account_data = $user_account_data->first();
            return $user_account_data;
        } else {
            return null;
        }
    }


    public function approve() {
        try {
            $current_user = Auth::user();
            $this->approval_admin_id = $current_user->id;
            $accept = $this->save();
            if($accept) {
                return true;
            } else {
                return false;
            }
        }
        catch (Exception $e) {
            return false;
        }
    }

    public function decline()
    {
        $this->declined = 1;
        try {
            return (bool) $this->save();
        } catch (Exception $e) {
            return false;
        }
    }

    public function makeModerator() {
        $this->createAndAssignRole('moderator');
    }

    public function makeAdmin() {
        $this->createAndAssignRole('admin');
    }

    public function createAndAssignRole($role_name)
    {
        if(!Role::where('name', $role_name)->exists()) {
            Role::create(['name' => $role_name]);
        }
        $this->assignRole($role_name);
    }


    public static function sendApproveAccountEmailToAdmins($userId = 0)
    {   
        
        if($userId == 0){
            return false;
        }
        else{
            $user_to_approve = User::find($userId);
        }

        if(!$user_to_approve) {
            return false;
        }

        $admins = User::where('admin', 1)->get();

        foreach($admins as $admin) {
            Mail::send('emails.admin.approve-account', ['user_to_approve' => $user_to_approve, 'admin' => $admin],
                function($m) use ($admin) {
                    $m->from(env('MAIL_FROM_ADDRESS'));
                    $m->to($admin->email, $admin->username)->subject('New User Requires Your Approval');
                }
            );
        }
        
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
        $output = array();
		$output['fuel_address'] = User::getFuelAddress($user->id);
		$output['fuel_balance'] = intval(UserMeta::getMeta($user->id, 'fuel_balance'));
		$output['fuel_pending'] = intval(UserMeta::getMeta($user->id, 'fuel_pending'));
		$output['fuel_spent'] = intval(UserMeta::getMeta($user->id, 'fuel_spent'));
        $output['fuel_balanceFloat'] = CurrencyUtil::satoshisToValue($output['fuel_balance']);
        $output['fuel_pendingFloat'] = CurrencyUtil::satoshisToValue($output['fuel_pending']);;
        $output['fuel_spentFloat'] = CurrencyUtil::satoshisToValue($output['fuel_spent']);;

        // escrow info
        $escrow_address = $user->getEscrowAddress();
        if ($escrow_address) {
            // address
            $ledger = app(EscrowAddressLedgerEntryRepository::class);
            $output['escrow_address'] = $escrow_address['address'];
            $output['escrow_balances'] = $ledger->addressBalancesByAsset($escrow_address, $_confirmed_only = true);
            $output['escrow_pending_balances'] = $ledger->addressBalancesByAsset($escrow_address, $_confirmed_only = false);
        } else {
            // no address
            $output['escrow_address'] = '[None]';
            $output['escrow_balances'] = [];
            $output['escrow_pending_balances'] = [];
        }


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

    public function getStatusAttribute($value)
    {
        $user_account_data = User::getUserAccountData($this->id);
        switch (true) {
            case $this->admin:
                return 'Admin';
            case $this->hasRole('moderator'):
                return 'Moderator';
                case !$this->tac_accept:
                return 'Needs Terms and Conditions Approval';
            case !$user_account_data:
                return 'Needs Account Details';
            case !$this->approval_admin_id:
                return 'Needs approval';
            case $this->approval_admin_id:
                return 'Approved';
        }
        return 'No status';
    }

    public function getIsAdminAttribute($admin)
    {
        return $this->getAdminAttribute($admin);
    }

    public function getAdminAttribute($admin)
    {
        return $admin || $this->hasRole('admin');
    }

    public function getIsModeratorAttribute()
    {
        return $this->admin || $this->hasRole('admin') || $this->hasRole('moderator');
    }

    public function getEscrowAddress()
    {
        if (!$this->hasRole('admin')) {
            return null;
        }

        $user_repository = app(UserRepository::class);
        $escrow_address_repository = app(EscrowAddressRepository::class);

        $owner = $user_repository->findEscrowWalletOwner();
        $escrow_addresses = $escrow_address_repository->findAllByUser($owner, Substation::chain());
        if (count($escrow_addresses)) {
            return $escrow_addresses[0];
        }
        return null;
    }
}
