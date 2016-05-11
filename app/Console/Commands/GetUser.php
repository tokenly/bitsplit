<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use User, Log, UserMeta, Models\Distribution as Distro, Models\DistributionTx as DistroTx;

class GetUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:getUser {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gets details on a Bitsplit user';

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
        $user = User::select('id', 'name', 'username', 'email', 'admin', 'tokenly_uuid', 'created_at', 'updated_at')
            ->where('username', $id)->orWhere('id', $id)->orWhere('email', $id)->orWhere('tokenly_uuid', $id)
            ->first();
        if(!$user){
        $this->error('User not found');
        return false;
        }

        $user->distribution_count = 0;
        $user->confirmed_distribution_txs = 0;
        $distros = Distro::where('user_id', $user->id)->get();
        if($distros){
         foreach($distros as $distro){
             $user->distribution_count++;
             $user->confirmed_distribution_txs += DistroTx::where('distribution_id', $distro->id)->where('confirmed', 1)->count();
         }
        }
        $user->fuel_address = UserMeta::getMeta($user->id, 'fuel_address');
        $user->fuel_address_uuid = UserMeta::getMeta($user->id, 'fuel_address_uuid');
        $user->fuel_spent = UserMeta::getMeta($user->id, 'fuel_spent');
        dd($user->toArray());
    }
}
