<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Models\Distribution as Distro, Models\DistributionTx as DistroTx, Log;

class UpdateDistroTx extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:updateDistroTx {id} {--destination=} {--quantity=} {--utxo=} {--confirmed=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Force updates details on a specific outgoing distribution transaction';

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
        $id = $this->argument('id');
        $get = DistroTx::find($id);
        if(!$get){
            $this->error('Distribution TX not found');
            return false;
        }
        
        $destination = $this->option('destination');
        $quantity = $this->option('quantity');
        $utxo = $this->option('utxo');
        $confirmed = $this->option('confirmed');
        $changed = false;
        
        if($destination != null){
            $get->destination = $destination;
            $changed = true;
        }
        
        if($quantity != null){
            $old_quantity = intval($get->quantity);
            $new_quantity = intval($quantity);
            $get->quantity = $new_quantity;
            $changed = true;
            //recalculate asset total for parent distribution
            $distro = Distro::find($get->distribution_id);
            $tx_list = DistroTx::where('distribution_id', $get->distribution_id)->get();
            if($tx_list AND $distro){
                $total = 0;
                foreach($tx_list as $row){
                    $total += intval($row->quantity);
                }
                $total -= $old_quantity;
                $total += $new_quantity;
                $distro->asset_total = $total;
                $save = $distro->save();
                if(!$save){
                    $this->error('Error updating distribution #'.$distro->id.' asset total');
                    return false;
                }
            }
        }
        
        if($utxo != null){
            if(trim($get->txid) != ''){
                $this->error('Cannot manually set a utxo on an already generated transaction');
                return false;
            }
            $get->utxo = $utxo;
            $changed = true;
        }
        
        if($confirmed != null){
            if(intval($confirmed) == 1){
                $get->confirmed = 1;
            }
            else{
                $get->confirmed = 0;
            }
            $changed = true;
        }
        
        if(!$changed){
            $this->error('Nothing to change');
            return false;
        }
        
        $save = $get->save();
        if(!$save){
            $this->error('Error saving distribution tx #'.$get->id);
            return false;
        }
        $this->info('Distribution tx #'.$get->id.' details updated!');
        return true;
    }
}
