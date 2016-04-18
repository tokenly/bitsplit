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
			$get = Distribution::where('id', $userid)->orWhere('address', $userId)->first();
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
			$distro = Distribution::find($address);
			if($distro){
				$address = $distro->deposit_address;
			}
			else{
				Log::error('Fuel pump - distro not found '.$address);
				throw new Exception($address.' distribution not found');
			}
		}
		$xchain = xchain();
		if(strtolower($amount) == 'sweep'){
			return $xchain->sweepAllAssets($uuid, $address);
		}
		if($amount_satoshis){
			$amount = round($amount / 100000000, 8); 
		}

		return $xchain->send($uuid, $address, $amount, $asset, $fee);
	}
	
	public static function masterFuelSwap($userId, $token_in, $token_out, $in_amount, $out_amount)
	{
		$output = array();
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
}
