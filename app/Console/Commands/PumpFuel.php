<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use User, UserMeta;

class PumpFuel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pumpFuel {username} {address} {amount} {--sweep=false}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Moves BTC ("fuel") from users\' fuel address to another address';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function fire()
    {
        $username = $this->argument('username');
        $address = $this->argument('address');
        $amount = $this->argument('amount');
        $sweep = $this->option('sweep');
        $user = User::where('username', $username)->first();
        if(!$user){
			$this->error('User not found');
			return false;
		} 
		$fuel_address = UserMeta::getMeta($user->id, 'fuel_address_uuid');
		if(!$fuel_address){
			$this->error('No fuel address on file');
			return false;
		}
		$xchain = xchain();
		try{
			if($sweep == 'true'){
				$send = $xchain->sweepAllAssets($fuel_address, $address);
			}
			else{
				$send = $xchain->send($fuel_address, $address, $amount, 'BTC');
			}
			if($send){
				$this->info('Success: '.$send['txid']);
				return true;
			}
		}
		catch(\Exception $e){
			$this->error($e->getMessage());
			return false;
		}
	}
    
}
