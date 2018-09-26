<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Models\Distribution as Distro, User;
use Exception;
use Storage;

class generateUserBalanceReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:generateUserBalanceReport';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a list of user balances and any leftover distribution balances';

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
        
        //load xchain
        $xchain = xchain();
        
        $this->info('Processing...');
        
        //get users
        $users = User::all();
        $this->info('Loaded '.count($users).' users...');
        
        //loop through and collect a list of fuel addresses + balances
        //also loop through user distributions and build balance lists
        $fuel_addresses = array();
        $distro_addresses = array();
        $fuel_errors = array();
        $distro_errors = array();
        $map_users = array();
        foreach($users as $user){
            $map_users[$user->id] = $user;
            $this->info('Processing user '.$user->email.' - '.$user->username.' ['.$user->id.']');
            $address = User::getFuelAddress($user->id);
            $this->info('Fuel address: '.$address);
            if($address){
                try{
                    $fuel_balances = $xchain->getBalances($address, true);
                }
                catch(Exception $e){
                    $fuel_errors[$user->id] = $e->getMessage();
                    $fuel_balances = false;
                    $this->info('Error loading fuel address balances for address '.$address.': '.$e->getMessage());
                }
                if($fuel_balances){
                    $fuel_addresses[$user->id] = array('address' => $address, 'balances' => $fuel_balances);
                    $this->info('Fuel balances: '.print_r($fuel_balances, true));
                }
            }
            
            $distributions = Distro::where('user_id', $user->id)->get();
            if($distributions){
                $this->info('Processing distributions for user '.$user->id .'('.count($distributions).')');
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
                        if(!isset($distro_addresses[$user->id])){
                            $distro_addresses[$user->id] = array();
                        }
                        $distro_address = array('distro' => $distro->id, 'address' => $distro->deposit_address, 'balances' => $distro_balances);
                        $distro_addresses[$user->id][] = $distro_address;
                        $this->info(print_r($distro_address, true));
                    }
                }
            }
            sleep(0.5); //add slight delay for API calls
        }
        
        
        //build results list
        $output = '';
        $fuel_tokens = array();
        $distro_tokens = array();
        foreach($users as $user){
            $line = 'User #'.$user->id.' - '.$user->email.' - '.$user->username."\n";
            if(isset($fuel_addresses[$user->id])){
                    $has_balances = false;
                    if($fuel_addresses[$user->id]['balances']){
                        foreach($fuel_addresses[$user->id]['balances'] as $asset => $quantity){
                            if($quantity > 0){
                                $has_balances = true;
                            }
                            if(!in_array($asset, $fuel_tokens)){
                                $fuel_tokens[] = $asset;
                            }
                        }
                    }
                    $fuel_addresses[$user->id]['has_balances'] = $has_balances;
                    $output .= 'Fuel Address: '.$fuel_addresses[$user->id]['address']."\n";
                    if($has_balances){
                        //only show if there is a balance
                        $line .= 'Fuel Balances:'."\n";
                        $line .= print_r($fuel_addresses[$user->id], true)."\n";
                    }                
            }
            if(isset($distro_addresses[$user->id])){
                $line .= 'Distribution Balances:.'."\n";
                foreach($distro_addresses[$user->id] as $dk => $distro_address){
                    $has_balances = false;
                    if($distro_address['balances']){
                        foreach($distro_address['balances'] as $asset => $quantity){
                            if($quantity > 0){
                                $has_balances = true;
                            }
                            if(!in_array($asset, $distro_tokens)){
                                $distro_tokens[] = $asset;
                            }                            
                        }
                    }
                    $distro_addresses[$user->id][$dk]['has_balances'] = $has_balances;
                    if(!$has_balances){
                        //skip empty addresses
                        continue;
                    }
                    $line .= print_r($distro_address, true)."\n";
                }
            }
            $line .= "------\n";
            $line .= "------\n";
            $output .= $line;
        }
        
        //add errors to output
        $output .= "------\n";
        $output .= 'Fuel Address Errors:'."\n";
        $output .= print_r($fuel_errors, true)."\n";
        $output .= "------\n";
        $output .= 'Distro Address Errors:'."\n";
        $output .= print_r($distro_errors, true)."\n";
        
        
        //$this->info($output);
        //save output
        Storage::put('user-balance-report.txt', $output);
        $this->info('Saved balance report log to user-balance-report.txt');
        
        //create a csv output for distributions
        $this->info('Generating CSV report for distributions');
        $csv_lines = array();
        $csv_headings = array('User', 'Distro ID', 'Distro Address');
        foreach($distro_tokens as $token){
            $csv_headings[] = $token;
        }
        foreach($distro_addresses as $user_id => $distro_address_list){
            foreach($distro_address_list as $distro_address){
                if(isset($distro_address['has_balances']) AND !$distro_address['has_balances']){
                    continue; //skip empty address
                }
                $csv_line = array();
                $csv_line[] = $map_users[$user_id]->email;
                $csv_line[] = $distro_address['distro'];
                $csv_line[] = $distro_address['address'];
                foreach($distro_tokens as $token){
                    $token_balance = 0;
                    if($distro_address['balances'] AND isset($distro_address['balances'][$token])){
                        $token_balance = $distro_address['balances'][$token];
                    }
                    $csv_line[] = $token_balance;
                }
                $csv_lines[] = $csv_line;
            }
        }
        
        $csv_output = '';
        foreach($csv_headings as $k => $heading){
            $csv_output .= '"'.$heading.'"';
            if($k < (count($csv_headings) - 1)){
                $csv_output .= ',';
            }
        }
        $csv_output .= "\n";
        foreach($csv_lines as $csv_line){
            foreach($csv_line as $k => $column){
                $csv_output .= '"'.$column.'"';
                if($k < (count($csv_line) - 1)){
                    $csv_output .= ',';
                }
            }
            $csv_output .= "\n";
        }
        
        //save CSV
        Storage::put('distro-balance-report.csv', $csv_output);
        $this->info('Saved distro-balance-report.csv');
        
        
        //save another CSV for fuel address balances
        $csv_lines = array();
        $csv_headings = array('User', 'Fuel Address');
        foreach($fuel_tokens as $token){
            $csv_headings[] = $token;
        }
        foreach($fuel_addresses as  $user_id => $fuel_address){
            if(isset($fuel_address['has_balances']) AND !$fuel_address['has_balances']){
                continue; //skip empty address
            }            
            $csv_line = array();
            $csv_line[] = $map_users[$user_id]->email;
            $csv_line[] = $fuel_address['address'];
            foreach($fuel_tokens as $k => $token){
                $token_balance = 0;
                if($fuel_address['balances'] AND isset($fuel_address['balances'][$token])){
                    $token_balance = $fuel_address['balances'][$token];
                }
                $csv_line[] = $token_balance;
            }
            $csv_lines[] = $csv_line;
        }
        $csv_output = '';
        foreach($csv_headings as $k => $heading){
            $csv_output .= '"'.$heading.'"';
            if($k < (count($csv_headings) - 1)){
                $csv_output .= ',';
            }
        }
        $csv_output .= "\n";
        foreach($csv_lines as $csv_line){
            foreach($csv_line as $k => $column){
                $csv_output .= '"'.$column.'"';
                if($k < (count($csv_line) - 1)){
                    $csv_output .= ',';
                }
            }
            $csv_output .= "\n";
        }        
        Storage::put('fuel-balance-report.csv', $csv_output);
        $this->info('Saved fuel-balance-report.csv');        
        
        $this->info('..done');
    
    }
}
