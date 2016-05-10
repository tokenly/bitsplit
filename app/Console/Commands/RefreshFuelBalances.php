<?php

namespace App\Console\Commands;

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
    public function fire()
    {
        $get = UserMeta::where('metaKey', 'fuel_address')->get();
        if(!$get OR count($get) == 0){
			$this->error('No fuel addresses found');
			return false;
		}
		$xchain = xchain();
        foreach($get as $row){
			try{
				$balances = $xchain->getBalances($row->value, true);
				if(is_array($balances)){
					if(isset($balances['BTC'])){
						UserMeta::setMeta($row->userId, 'fuel_balance', $balances['BTC']);
						UserMeta::setMeta($row->userId, 'fuel_pending', 0);
						DB::table('fuel_deposits')->where('user_id', $row->userId)->update(array('confirmed' => 1));
						DB::table('fuel_debits')->where('user_id', $row->userId)->update(array('confirmed' => 1));
						$this->info($row->userId.' updated - '.$row->value);
						continue;
					}
				}
			}
			catch(Exception $e){
				$this->error($e->getMessage());
				continue;
			}
		}
    }
}
