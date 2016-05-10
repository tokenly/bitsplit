<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Models\Distribution;

class MarkComplete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:markComplete {address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marks a distribution complete';

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
        $id = intval($address);
        $distro = Distribution::where('deposit_address', $address)->first();
        if(!$distro){
            $distro = Distribution::where('id', $id)->first();
        }
        if(!$distro){
            $this->error('Distribution not found');
            return false;
        }
        $distro->markComplete();
        $this->info('Distro #'.$distro->id.' marked complete '.timestamp());
        return true;
    }
}
