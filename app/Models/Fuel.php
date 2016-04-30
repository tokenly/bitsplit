<?php
namespace Models;
use DB, Models\Distribution, Models\DistributionTx, User, UserMeta, Exception, Log, Config;
class Fuel
{
	
	public static function pump($userId, $address, $amount, $asset = 'BTC', $fee = null, $amount_satoshis = true)
	{
		if($userId == 'MASTER'){
			$uuid = env('MASTER_FUEL_ADDRESS_UUID');
			if(!$uuid){
				Log::error('Fuel pump - master fuel address not found');
				throw new Exception('Master fuel address not found');
			}
		}
		elseif(strpos($userId, 'distro:') === 0){
			$userId = substr($userId, 7);
			$get = Distribution::where('id', $userId)->orWhere('deposit_address', $userId)->first();
			if(!$get){
				Log::error('Fuel pump - distro not found '.$userId);
				throw new Exception($userId.' distribution not found');
			}
			$uuid = $get->address_uuid;
		}
		else{
			$user = User::where('id', $userId)->orWhere('username', $userId)->first();
			if(!$user){
				Log::error('Fuel pump - user not found '.$userId);
				throw new Exception($userId.' user not found');
			}
			$uuid = UserMeta::getMeta($user->id, 'fuel_address_uuid');
			if(!$uuid){
				Log::error('Fuel pump - fuel address not found '.$userId);
				throw new Exception($userId.' fuel address not found');
			}
		}
		if($address == 'MASTER'){
			$address = env('MASTER_FUEL_ADDRESS');
		}
		elseif($address == 'HOUSE'){
			$address = env('HOUSE_INCOME_ADDRESS');
			if(!$address){
				Log::error('Fuel pump - house income address not found');
				throw new Exception('House income address not found');
			}
		}
		elseif(strpos($address, 'user:') === 0){
			$address = substr($address, 5);
			$user = User::where('id', $address)->orWhere('username', $address)->first();
			if(!$user){
				Log::error('Fuel pump - user not found '.$address);
				throw new Exception($address.' user not found');
			}
			$address = UserMeta::getMeta($user->id, 'fuel_address');
			if(!$address){
				Log::error('Fuel pump - fuel address not found '.$user->id);
				throw new Exception($user->id.' fuel address not found');
			}
		}
		else{
			if(is_int($address)){
				$distro = Distribution::where('id', $address)->first();
				if($distro){
					$address = $distro->deposit_address;
				}
			}
		}
		$xchain = xchain();
		if(strtolower($amount) == 'sweep'){
			Log::info('Sweeping assets from '.$uuid.' to '.$address);
			return $xchain->sweepAllAssets($uuid, $address);
		}
		if($amount_satoshis){
			$amount = round($amount / 100000000, 8); 
			if($fee !== null){
				$fee = round(Config::get('settings.miner_fee')/100000000,8);
			}			
		}
		Log::info('Pumping '.$amount.' '.$asset.' from '.$uuid.' to '.$address.' (fee: '.$fee.')');
		return $xchain->send($uuid, $address, $amount, $asset, $fee);
	}
	
	public static function masterFuelSwap($userId, $token_in, $token_out, $in_amount, $out_amount)
	{
		$output = array();
		Fuel::pump('MASTER', 'user:'.$userId, Config::get('settings.miner_fee'), 'BTC'); //pump a bit of BTC to pay for TX fee
		$output['in_swap'] = Fuel::pump($userId, 'MASTER', $in_amount, $token_in);
		$output['out_swap'] = Fuel::pump('MASTER', 'user:'.$userId, $out_amount, $token_out);
		if($output['in_swap'] AND $output['out_swap']){
			Log::info('Swapped fuel with master - user:'.$userId.' '.$output['in_swap']['txid'].' -> '.$output['out_swap']['txid']);
		}
		return $output;
	}
	
	public static function getFuelQuote($token, $amount, $amount_satoshis = true)
	{
		$valid_assets = Config::get('settings.valid_fuel_tokens');
		if(!isset($valid_assets[$token])){
			return false;
		}
		$rate = $valid_assets[$token];
		$quotebot = json_decode(@file_get_contents(env('QUOTEBOT_URL')), true);
		if(!is_array($quotebot)){
			return false;
		}
		$usd_rate = false;
		foreach($quotebot['quotes'] as $row){
			if($row['source'] == 'bitcoinAverage' AND $row['pair'] == 'USD:BTC'){
				$usd_rate = $row['lastHigh'];
			}
		}
		if(!$usd_rate){
			return false;
		}
		$btc_amount = round($rate / $usd_rate, 8);
		if($amount_satoshis){
			$amount = $amount / 100000000;
		}
		$quote = round($amount * $btc_amount, 8);
		if($quote <= 0.000055){
			//too dusty
			return false;
		}		
		if($amount_satoshis){
			$quote = intval($quote * 100000000);
		}
		return $quote;
	}
	
	public static function estimateFuelCost($tx_count)
	{
		$per_byte = Config::get('settings.miner_satoshi_per_byte');
		$average_size = Config::get('settings.average_tx_bytes');
		$average_txo = Config::get('settings.average_txo_bytes');
		$max_txos = Config::get('settings.max_tx_outputs');
		$dust_size = Config::get('settings.default_dust');
		//base cost for # of transactions they are making
		$base_cost = intval((($per_byte * $average_size) + $dust_size) * $tx_count);
		//cost for priming transactions
		$prime_cost = 0;
		$num_primes = ceil($tx_count / $max_txos);
		$txos_used = 0;
		$txos_per_prime = floor($tx_count / $num_primes) + 1;
		for($i = 1; $i <= $num_primes; $i++){
			$prime_size = $average_size + ($txos_per_prime * $average_txo);
			$prime_cost += $prime_size * $per_byte;
		}
		//cost for priming the priming transactions (if applicable)
		$pre_prime_cost = 0;
		if($num_primes > 1){
			$pre_prime_size = $average_size + (($num_primes+1) * $average_txo);
			$pre_prime_cost = $pre_prime_size * $per_byte;
		}
		$cost = intval($base_cost + $prime_cost + $pre_prime_cost);
		return $cost;
	}
}
