<?php

namespace App\Console\Commands;

use App\Libraries\Substation\Substation;
use App\Libraries\Substation\UserWalletManager;
use Illuminate\Console\Command;
use User, UserMeta, Exception, DB;

class RefreshFuelBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:refreshFuelBalances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes the balance for each fuel address';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $get = UserMeta::where('metaKey', 'fuel_address')->get();
        if(!$get OR count($get) == 0){
			$this->error('No fuel addresses found');
			return false;
		}
        foreach($get as $row){

            $user_id = $row->userId;
            $user = User::find($user_id);

            try {
                $substation = Substation::instance();
                $wallet_uuid = app(UserWalletManager::class)->ensureSubstationWalletForUser($user);

                $fuel_address_uuid = UserMeta::getMeta($user_id, 'fuel_address_uuid');
                $substation_balances = $substation->getCombinedAddressBalanceById($wallet_uuid, $fuel_address_uuid);

                $confirmed_balance = 0;
                $unconfirmed_balance = 0;
                if (isset($substation_balances['BTC']['confirmed'])) {
                    $confirmed_balance = $substation_balances['BTC']['confirmed']->getSatoshisString();
                }
                if (isset($substation_balances['BTC']['unconfirmed'])) {
                    // only use the unconfirmed balance if it is different than the confirmed balance
                    if ($substation_balances['BTC']['unconfirmed']->getSatoshisString() != $confirmed_balance) {
                        $unconfirmed_balance = $substation_balances['BTC']['unconfirmed']->getSatoshisString();
                    }
                }

                UserMeta::setMeta($user_id, 'fuel_balance', $confirmed_balance);
                UserMeta::setMeta($user_id, 'fuel_pending', $unconfirmed_balance);
                $this->comment("Updated address {$row->value} with {$confirmed_balance} sat confirmed and {$unconfirmed_balance} sat unconfirmed");
            } catch (Exception $e) {
                $this->error("Error updating address {$row->value}: ".$e->getMessage());
            }

		}
    }
}
