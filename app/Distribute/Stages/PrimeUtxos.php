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
		$dust_size = Config::get('settings.default_dust');
		$default_miner = Config::get('settings.miner_fee');
		$base_txo_cost = ($average_size * $per_byte) + ($dust_size * 2);
		$base_cost = ($average_size * $per_byte);
		
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
			return true;
		}		
		
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

		$txos_needed = $tx_count - $checkPrimes['primedCount'];
		$num_primes = intval(ceil($txos_needed  / $max_txos));
		$per_prime = intval(floor($txos_needed / $num_primes)) + 1;
		
		$prime_stage = 2;
		if($num_primes > 1){
			//possible two-stage priming needed
			
			//$prime_stage = 1;
		}
		
		if($prime_stage == 1){
			//perform 1st-stage priming
		}
		else{
			//perform second-stage priming
			$per_txo = $base_txo_cost;
			$prime_fee = $base_cost + ($per_prime * $txo_size * $per_byte);
			try{
				$submit_prime = $xchain->primeUTXOs($distro->address_uuid, round($per_txo/100000000,8), $per_prime, round($prime_fee/100000000,8), true);
			}
			catch(Exception $e){
				Log::error('Priming error distro '.$distro->id.': '.$e->getMessage());
				return false;
			}
			if(!$submit_prime OR trim($submit_prime['txid']) == ''){
				Log::error('Unkown error priming distro '.$distro->id);
				//pump a bit of fuel to give this a kick
				try{
					$pump = Fuel::pump($distro->user_id, $distro->deposit_address, $default_miner, 'BTC', $default_miner);
					$spent = intval(UserMeta::getMeta($distro->user_id, 'fuel_spent'));
					$spent = $spent + ($default_miner*2);
					UserMeta::setMeta($distro->user_id, 'fuel_spent', $spent);
					Log::info('Extra fuel pumped for distro '.$distro->id.' priming '.$pump['txid']);
				}
				catch(Exception $e){
					Log::error('Error pumping extra fuel for distro '.$distro->id.' priming: '.$e->getMessage());
				}
				return false;
			}
			$time = timestamp();
			$tx_data = array('created_at' => $time, 'updated_at' => $time, 'distribution_id' => $distro->id,
							'quantity' => $prime_fee + ($per_txo*$per_prime), 'txid' => $submit_prime['txid'],
							'stage' => 2, 'confirmed' => 0);
			$save = DB::table('distribution_primes')->insert($tx_data);
			if(!$save){
				Log::error('Error saving distribution '.$distro->id.' prime TX');
				return false;
			}
			return true;
		}
	}
}
