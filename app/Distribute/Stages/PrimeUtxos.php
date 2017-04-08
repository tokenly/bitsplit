<?php
namespace Distribute\Stages;
use Config, DB, Exception, Log, Models\Fuel, UserMeta;

class PrimeUtxos extends Stage
{
	public function init()
	{
		$distro = $this->distro;
		$xchain = xchain();
		$tx_count = $distro->addressCount();
		$max_txos = Config::get('settings.max_tx_outputs');
		$per_byte = Config::get('settings.miner_satoshi_per_byte');
		$average_size = Config::get('settings.average_tx_bytes');
		$txo_size = Config::get('settings.average_txo_bytes');
		$xcp_tx_bytes = Config::get('settings.xcp_tx_bytes');
        $extra_bytes = Config::get('settings.tx_extra_bytes');
        $input_bytes = Config::get('settings.tx_input_bytes');        
		$dust_size = $distro->getBTCDustSatoshis();
        
		$base_txo_cost = ($xcp_tx_bytes * $per_byte) + $dust_size;
		$base_cost = ($input_bytes + $extra_bytes) * $per_byte;
		
		//check if any primes waiting on confirmations
		$unconf_primes = DB::table('distribution_primes')->where('distribution_id', $distro->id)->where('confirmed', 0)->get();
		if($unconf_primes AND count($unconf_primes) > 0){
			Log::info('Waiting for distro #'.$distro->id.' primes to confirm');
			return true;
		}
		$pending_totals = $distro->pendingDepositTotals();
		if($pending_totals['fuel'] > 0){
			Log::info('Waiting for more fuel to confirm for distro #'.$distro->id.' [priming]');
			return true;
		}
		
		$checkPrimes = false;
		try{
			$checkPrimes = $xchain->checkPrimedUTXOs($distro->address_uuid, round($base_txo_cost/100000000,8));
		}
		catch(Exception $e){
			Log::error('Error checking primes for distro #'.$distro->id.': '.$e->getMessage());
			return false;
		}

		if(!$checkPrimes){
			Log::error('Unknown error checking primes for distro #'.$distro->id);
			return false;
		}
		
		if($checkPrimes['primedCount'] >= $tx_count){
			Log:info('Inputs finished priming for distro #'.$distro->id);
			$distro->incrementStage();
            $distro->sendWebhookUpdateNotification();
			return true;
		}		
				
		$txos_needed = $tx_count - $checkPrimes['primedCount'];
		$num_primes = intval(ceil($txos_needed  / $max_txos));
		$per_prime = intval(floor($txos_needed / $num_primes));
        
		$pre_prime_txo = ($base_txo_cost * $per_prime) + $base_cost + ($per_prime * $txo_size * $per_byte);
        $pre_prime_txo += Config::get('settings.miner_fee'); //add buffer for fee variations
		$prime_stage = 2;
		if($num_primes > 1){
			//possible two-stage priming needed
			$checkPrePrimes = false;
			try{
				$checkPrePrimes = $xchain->checkPrimedUTXOs($distro->address_uuid, round($pre_prime_txo/100000000,8));
			}
			catch(Exception $e){
				Log::error('Error checking stage1 primes for distro #'.$distro->id.': '.$e->getMessage());
				return false;
			}
			if(!$checkPrePrimes){
				Log::error('Unknown error checking stage1 primes for distro #'.$distro->id);
				return false;
			}		
			if($checkPrePrimes['primedCount'] < $num_primes){
				Log::info('Starting stage1 priming for distro #'.$distro->id);
				$prime_stage = 1;
			}					
		}
		$submit_prime = false;
		$prime_repeat = 1;
		if($prime_stage === 1){
			//perform 1st-stage priming (priming the primes)
			$per_txo = $pre_prime_txo;
			$prime_fee = $base_cost + (($num_primes+1) * $txo_size * $per_byte);
			$prime_count = $num_primes;
		}
		else{
			//perform second-stage priming (utxos for the actual token sends)
			$per_txo = $base_txo_cost;
			$prime_fee = $base_cost + ($per_prime * $txo_size * $per_byte);
            if($num_primes === 1){
                $prime_fee += $per_prime * $txo_size * $per_byte; //extra change output
            }
			$prime_count = $per_prime;
			$prime_repeat = $num_primes;
		}
		$time = timestamp();
        $prime_cap = 0;
		for($i = 0; $i < $prime_repeat; $i++){
			try{
                $prime_cap += $prime_count; //increment the requested # of primes until the desired total is reached
				//$submit_prime = $xchain->primeUTXOs($distro->address_uuid, round($per_txo/100000000,8), $prime_cap, round($prime_fee/100000000,8));
				$submit_prime = $xchain->primeUTXOsWithFeeRate($distro->address_uuid, round($per_txo/100000000,8), $prime_cap, $per_byte);
			}
			catch(Exception $e){
				Log::error('Priming error distro '.$distro->id.': '.$e->getMessage());
				return false;
			}
			if(!$submit_prime OR trim($submit_prime['txid']) == ''){
				Log::error('Unkown error priming distro '.$distro->id);
				if($distro->use_fuel == 1 AND Config::get('settings.auto_pump_stuck_distros')){
					//pump a bit of fuel to give this a kick
					try{
                        $miner_fee = (($input_bytes + $extra_bytes + ($txo_size*2)) * $per_byte);
						$pump = Fuel::pump($distro->user_id, $distro->deposit_address, $base_txo_cost, 'BTC', $miner_fee);
						$spent = intval(UserMeta::getMeta($distro->user_id, 'fuel_spent'));
						$spent = $spent + $base_txo_cost + $miner_fee;
                                                
						UserMeta::setMeta($distro->user_id, 'fuel_spent', $spent);
						Log::info('Extra fuel pumped for distro '.$distro->id.' priming '.$pump['txid']);
					}
					catch(Exception $e){
						Log::error('Error pumping extra fuel for distro '.$distro->id.' priming: '.$e->getMessage());
					}
				}
				return false;
			}			
			$tx_data = array('created_at' => $time, 'updated_at' => $time, 'distribution_id' => $distro->id,
							'quantity' => $prime_fee + ($per_txo*$per_prime), 'txid' => $submit_prime['txid'],
							'stage' => 2, 'confirmed' => 0);
			$save = DB::table('distribution_primes')->insert($tx_data);
			if(!$save){
				Log::error('Error saving distribution '.$distro->id.' prime TX '.$submit_prime['txid']);
				return false;
			}
			Log::info('Prime stage'.$prime_stage.' sent for Distro '.$distro->id.' '.$submit_prime['txid']);			
		}
		return true;
	}
}
