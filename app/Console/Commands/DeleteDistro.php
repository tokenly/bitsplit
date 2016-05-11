<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Models\Distribution as Distro;
use Distribute\Initialize;

class DeleteDistro extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:deleteDistro {address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes a distribution';

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
        //get distro
        $address = $this->argument('address');
        $get = Distro::where('deposit_address', $address)->first();
        if(!$get){
            $get = Distro::where('id', intval($address))->first();
        }
        if(!$get){
            $this->error('Distribution not found');
            return false;
        }
        //stop transaction monitor
        $initer = new Initialize;
        $initer->stopMonitor($get);
        
        //delete
        $id = $get->id;
        $delete = $get->delete();
        if(!$delete){
            $this->error('Error deleting distribution #'.$id);
            return false;
        }
        else{
            $this->info('Distribution #'.$id.' deleted');
        }
        return true;
    }
}
