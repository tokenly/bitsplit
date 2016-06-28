<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Models\Distribution as Distro, User;

class ShowBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:showBalances {address} {--type=distro}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Shows balance for an address in the system';

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
        $address = $this->argument('address');
        $type = $this->option('type');
        
        $get = false;
        switch($type){
            case 'distro':
            default:
                $get = Distro::where('deposit_address', '=', $address)->orWhere('id', '=', $address)->first();
                break;
        }
        
        if(!$get){
            $this->error('Not found');
            return false;
        }
        
        $xchain = xchain();
        
		$balances = false;
		try{
			$balances = $xchain->getAccountBalances($get->address_uuid, 'default');
		}
		catch(Exception $e){
			Log::error('Error checking balances: '.$e->getMessage());
			return false;
		}		
        
        dd($balances);
		        
    }
}
