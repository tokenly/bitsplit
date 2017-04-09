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
        if($distro->fee_rate != null){
            $per_byte = $distro->fee_rate;
        }
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
				
		$txos_needed = $tx_count;
		$txos_left = $tx_count - $checkPrimes['primedCount'];
		$num_primes = intval(ceil($txos_needed  / $max_txos));
        $primes_left = intval(ceil($txos_left  / $max_txos));
		$per_prime = intval(ceil($txos_needed / $num_primes));
        $per_prime_left = intval(ceil($txos_left / $primes_left));
        
		$pre_prime_txo = ($base_txo_cost * $per_prime_left) + $base_cost + ($per_prime_left * $txo_size * $per_byte);
        $pre_prime_txo += Config::get('settings.miner_fee'); //add buffer for fee variations
		$prime_stage = 2;
		if($primes_left > 1){
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
			$prime_fee = $base_cost + (($primes_left+1) * $txo_size * $per_byte);
			$prime_count = $primes_left;
		}
		else{
			//perform second-stage priming (utxos for the actual token sends)
			$per_txo = $base_txo_cost;
			$prime_fee = $base_cost + ($per_prime_left * $txo_size * $per_byte);
            if($num_primes === 1){
                $prime_fee += $txo_size * $per_byte; //extra change output
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
				continue;
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
