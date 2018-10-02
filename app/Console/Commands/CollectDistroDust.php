<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use User, UserMeta, Models\Distribution as Distro;
use Models\Fuel;

class CollectDistroDust extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:collect-dust {user_email} {destination} {--asset=BTC} {--feerate=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Collects all coin from distribution addresses owned by a user';

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
        //set vars
        $user_email = $this->argument('user_email');
        $destination = $this->argument('destination');
        $asset = $this->option('asset');
        $is_btc = false;
        if($asset == 'BTC'){
            $is_btc = true;
        }
        $feerate = $this->option('feerate');
        if(trim($feerate) == ''){
            $feerate = 'low';
        }
        $xchain = xchain();
        
        //load user
        $user = User::where('email', $user_email)->first();
        if(!$user){
            $this->error('User not found');
            return false;
        }
        
        //load balances from all distros created by user
        $distributions = Distro::where('user_id', $user->id)->get();
        if(!$distributions){
            $this->error('No distributions found');
            return false;
        }
        
        $distro_addresses = array();
        
        $this->info('Collecting distributions for user '.$user->email .'('.count($distributions).')');
        foreach($distributions as $distro){
            try{
                $distro_balances = $xchain->getBalances($distro->deposit_address, true);
            }
            catch(Exception $e){
                $distro_errors[$distro->id] = $e->getMessage();
                $distro_balances = false;
                $this->info('Error loading distro address balances for address '.$distro->deposit_adddress.': '.$e->getMessage());
            }
            if($distro_balances){
                $has_balances = false;
                foreach($distro_balances as $asset => $quantity){
                    if($quantity > 0){
                        $has_balances = true;
                    }
                }
                if(!$has_balances){
                    continue;
                }
                $distro_address = array('distro' => $distro->id, 'address' => $distro->deposit_address, 'balances' => $distro_balances, 'address_uuid' => $distro->address_uuid);
                $distro_addresses[] = $distro_address;
                //$this->info(print_r($distro_address, true));
            }
        }
        
        $address_uuids = array();
        foreach($distro_addresses as $distro_address){
            $address_uuids[] = $distro_address['address_uuid'];
        }
        
        $uuid_list = join(',', $address_uuids);
        
        $cmd_output = 'xchain:multi-input-sweep '.$destination.' "'.$uuid_list.'" --fee-rate='.$feerate.' --broadcast';
        
        //output generated command
        $this->info($cmd_output);
            
    }
}
