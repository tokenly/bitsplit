<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Models\Distribution as Distro, User, Log;

class UpdateDistro extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:updateDistro {address} {--user_id=} {--stage=} {--label=} {--hold=} {--use_fuel=} {--recalculate_fuel=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Force updates details on a distribution';

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
        $get = Distro::where('deposit_address', $address)->first();
        if(!$get){
            $get = Distro::where('id', intval($address))->first();
        }
        if(!$get){
            $this->error('Distribution not found');
            return false;
        }
        $user_id = $this->option('user_id');
        $stage = $this->option('stage');        
        $label = $this->option('label');
        $hold = $this->option('hold');
        $use_fuel = $this->option('use_fuel');
        $recalc_fuel = $this->option('recalculate_fuel');
        $changed = false;
        if($user_id != null){
            $get_user = User::find($user_id);
            if(!$get_user){
                $this->error('User '.$user_id.' not found');
                return false;
            }
            $get->user_id = $get_user->id;
            $changed = true;
        }
        
        if($stage != null){
            $get->stage = intval($stage);
            $changed = true;
        }
        
        if($label != null){
            $get->label = trim($label);
            $changed = true;
        }
        
        if($hold != null){
            if(intval($hold) == 1){
                $get->hold = 1;
            }
            else{
                $get->hold = 0;
            }
            $changed = true;
        }
        
        if($use_fuel != null){
            if(intval($use_fuel) == 1){
                $get->use_fuel = 1;
            }
            else{
                $get->use_fuel = 0;
            }
            $changed = true; 
        }
        
        if($recalc_fuel){
            $num_tx = $get->addressCount();
            $get->fee_total = Fuel::estimateFuelCost($num_tx, $get);
            $changed = true;
        }
        
        if(!$changed){
            $this->error('Nothing to change');
            return false;
        }
        
        $save = $get->save();
        if(!$save){
            $this->error('Error saving distribution #'.$get->id);
            return false;
        }
        
        $this->info('Distribution #'.$get->id.' updated!');
        return true;
    }
}
