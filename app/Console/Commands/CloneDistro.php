<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Models\Distribution as Distro, User, Log, Models\Fuel, Models\DistributionTx as DistroTx;
use Ramsey\Uuid\Uuid;
use Distribute\Initialize as DistroInit;

class CloneDistro extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:cloneDistro {address} {--username=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clone a distribution and optionall assign it to a different user';

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
        $distro = Distro::where('deposit_address', $address)->first();
        if(!$distro){
            $distro = Distro::where('id', intval($address))->first();
        }
        if(!$distro){
            $this->error('Distribution not found');
            return false;
        }        
        
		$user = User::find($distro->user_id);
        $username = $this->option('username');
        if(trim($username) != ''){
            $user = User::where('username', $username)->first();
            if(!$user){
                $this->error('User '.$username.' not found');
                return false;
            }
        }

		$distro_list = DistroTx::where('distribution_id', $distro->id)->get();
		$xchain = xchain();
		
		//generate deposit address
		$deposit_address = false;
		$address_uuid = false;
		try{
			$get_address = $xchain->newPaymentAddress();
			if($get_address AND isset($get_address['address'])){
				$deposit_address = $get_address['address'];
				$address_uuid = $get_address['id'];
			}
		}
		catch(Exception $e){
			Log::error('Error getting distro deposit address: '.$e->getMessage());
		}
		if(!$deposit_address){
            $this->error('Error generating deposit address');
            return false;
		}		
		
		$new = new Distro;
		$new->user_id = $user->id;
		$new->deposit_address = $deposit_address;
		$new->address_uuid = $address_uuid;
		$new->network = $distro->network;
		$new->asset = $distro->asset;
		$new->asset_total = $distro->asset_total;
		$new->fee_total = Fuel::estimateFuelCost(count($distro_list), $distro);
		$new->label = $distro->label;
		if(trim($new->label) != ''){
			$new->label .= ' (copy)';
		}
		$new->use_fuel = $distro->use_fuel;
		$new->webhook = $distro->webhook;
        $new->uuid = Uuid::uuid4()->toString();
		$save = $new->save();
		
		if(!$save){
			$this->error('Error saving distribution');
            return false;			
		}
		$id = $new->id;
		if($distro_list AND count($distro_list) > 0){
			foreach($distro_list as $row){
				$tx = new DistroTx;
				$tx->distribution_id = $id;
				$tx->destination = $row->destination;
				$tx->quantity = $row->quantity;
				$tx->save();
			}
		}
		
		$initializer = new DistroInit;
		$initializer->init($new);		
		
		$this->info('Distribution cloned! '.$deposit_address);
        return true;
    }
}
