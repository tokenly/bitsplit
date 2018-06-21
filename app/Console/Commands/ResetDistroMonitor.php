<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Models\Distribution as Distro;
use Distribute\Initialize;
use Log;

class ResetDistroMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:resetDistroMonitor {address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Closes down existing address monitor for a distribution and sets fresh ones up';

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
        $initer = new Initialize;
        $initer->stopMonitor($get);
        $started = $initer->startMonitor($get, false, true);
        if(!$started){
            $this->error('Error starting up fresh distribution monitors');
            return false;
        }
        $this->info('Distribution monitors reset for distro #'.$get->id);
    }
}
