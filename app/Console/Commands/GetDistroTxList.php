<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Models\Distribution as Distro, Models\DistributionTx as DistroTx;


class GetDistroTxList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:getDistroTxList {address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gets a list of outgoing addresses/transactions for a distribution';

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
            $get = Distro::where('id', intval($address))->orWhere('uuid', $address)->first();
        }
        if(!$get){
            $this->error('Distribution not found');
            return false;
        }
        $tx_list = DistroTx::where('distribution_id', $get->id)->orderBy('id', 'asc')->get()->toArray();
        dd($tx_list);
    }
}
