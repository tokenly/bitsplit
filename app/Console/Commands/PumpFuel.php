<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use User, UserMeta, Models\Distribution as Distro;
use Models\Fuel;

class PumpFuel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:pumpFuel {username} {address} {amount} {--sweep=false} {--asset=BTC} {--feerate=} ';

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
        $asset = $this->option('asset');
        $feerate = $this->option('feerate');
        
		try{
			if($sweep == 'true'){
				$send = Fuel::pump($username, $address, 'sweep', $asset, $feerate, false);
			}
			else{
				$send = Fuel::pump($username, $address, $amount, $asset, $feerate, false);
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
