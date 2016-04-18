<?php 
namespace App\Http\Controllers;
use Illuminate\Http\Request, Input, Log;
use User, UserMeta, Config, Models\Distribution, Models\DistributionTx, Response, DB;
use Models\Fuel;
class WebhookController extends Controller {

	public function DistributorDeposit(Request $request)
	{
		
		
	}

	public function FuelAddressDeposit(Request $request)
	{
		$hook = app('Tokenly\XChainClient\WebHookReceiver');
		$xchain = xchain();
		$parseHook = $hook->validateAndParseWebhookNotificationFromRequest($request);
		$input = $parseHook['payload'];
		if(is_array($input) AND isset($input['notifiedAddress']) AND Input::get('nonce')){
			$getAddress = UserMeta::where('metaKey', 'fuel_address')->where('value', $input['notifiedAddress'])->first();
			if($getAddress){
				$userId = $getAddress->userId;
				$uuid = UserMeta::getMeta($userId, 'fuel_address_uuid');
				$calc_nonce = hash('sha256', $uuid.'_'.$userId);
				$nonce = Input::get('nonce');
				if($nonce == $calc_nonce){
					//everything is matching up so far
					$getTx = DB::table('fuel_deposits')->where('txid', $input['txid'])->first();
					$valid_assets = Config::get('settings.valid_fuel_tokens');
					$min_conf = Config::get('settings.min_fuel_confirms');
					$time = timestamp();
					if(!$getTx AND isset($valid_assets[$input['asset']])){
						$tx_data = array('user_id' => $userId, 'asset' => $input['asset'], 
										'created_at' => $time, 'updated_at' => $time,
										'quantity' => $input['quantitySat'], 
										'fuel_quantity' =>  $input['quantitySat'],
										'txid' => $input['txid'], 'confirmed' => 0);
						if($input['confirmations'] >= $min_conf){
							$tx_data['confirmed'] = 1;
						}
						$save = DB::table('fuel_deposits')->insert($tx_data);
						if(!$save){
							Log::error('Error saving fuel deposit '.$input['txid']);
							die();
						}
					}
					if($getTx AND $getTx->confirmed == 1){
						//already confirmed
						Log::error('Fuel deposit already confirmed '.$input['txid']);
						die();
					}
					
					if($input['asset'] == 'BTC'){
						//credit direct BTC fuel
						$amount = intval($input['quantitySat']);
						$current_fuel = intval(UserMeta::getMeta($userId, 'fuel_balance'));
						$current_pending = intval(UserMeta::getMeta($userId, 'fuel_pending'));						
						if($input['confirmations'] >= $min_conf){
							$new_amount = $amount + $current_fuel;
							$new_pending = $current_pending - $amount;
							if($new_pending < 0){
								$new_pending = 0;
							}
							if($getTx){
								$save = DB::table('fuel_deposits')->where('txid', $input['txid'])->update(array('confirmed' => 1));
								if(!$save){
									Log::error('Error saving fuel deposit '.$input['txid']);
									die();
								}
								UserMeta::setMeta($userId, 'fuel_pending', $new_pending);
							}
							UserMeta::setMeta($userId, 'fuel_balance', $new_amount);
						}
						else{
							if(!$getTx){
								$new_pending = $current_pending + $amount;
								UserMeta::setMeta($userId, 'fuel_pending', $new_pending);
							}
						}
					}
					elseif(isset($valid_assets[$input['asset']])){
						if($input['confirmations'] >= $min_conf AND 
							(!$getTx OR ($getTx AND $getTx->confirmed == 0))){
							//swap asset for fuel
							$quote = Fuel::getFuelQuote($input['asset'], $input['quantitySat']);		
							if($quote){						
								if($getTx){
									$save = DB::table('fuel_deposits')->where('txid', $input['txid'])->update(array('confirmed' => 1, 'fuel_quantity' => $quote));
									if(!$save){
										Log::error('Error saving fuel deposit '.$input['txid']);
										die();
									}								
								}
								Log::info('Fuel swap quote: '.$input['asset'].' - '.$quote);
								Fuel::masterFuelSwap($userId, $input['asset'], 'BTC', $input['quantitySat'], $quote);
							}
						}
					}
				}
			}
		}
	}
		
	public function DebitFuelAddress(Request $request)
	{
		$hook = app('Tokenly\XChainClient\WebHookReceiver');
		$xchain = xchain();
		$parseHook = $hook->validateAndParseWebhookNotificationFromRequest($request);
		$input = $parseHook['payload'];
		if(is_array($input) AND isset($input['notifiedAddress']) AND Input::get('nonce')){		
			$getAddress = UserMeta::where('metaKey', 'fuel_address')->where('value', $input['notifiedAddress'])->first();
			if($getAddress){
				$userId = $getAddress->userId;
				$uuid = UserMeta::getMeta($userId, 'fuel_address_uuid');
				$calc_nonce = hash('sha256', $uuid.'_'.$userId);
				$nonce = Input::get('nonce');
				if($nonce == $calc_nonce){
					$getTx = DB::table('fuel_debits')->where('txid', $input['txid'])->first();
					$min_conf = 1;
					$time = timestamp();
					if(!$getTx){
						if($input['asset'] != 'BTC'){
							Log::error('Invalid fuel debit asset '.$input['asset'].' '.$input['txid']);
							die();
						}
						$tx_data = array('user_id' => $userId, 'asset' => $input['asset'], 
										'created_at' => $time, 'updated_at' => $time,
										'quantity' => $input['quantitySat'], 
										'txid' => $input['txid'], 'confirmed' => 0);
						if($input['confirmations'] >= $min_conf){
							$tx_data['confirmed'] = 1;
						}
						$save = DB::table('fuel_debits')->insert($tx_data);
						if(!$save){
							Log::error('Error saving fuel debit '.$input['txid']);
							die();
						}
					}
					if($getTx AND $getTx->confirmed == 1){
						//already confirmed
						Log::error('Fuel debit TX already confirmed '.$input['txid']);
						die();
					}					
					if($input['asset'] == 'BTC'){
						if(!$getTx OR $getTx->confirmed == 0){
							if(!$getTx){
								$amount = intval($input['quantitySat']);
								$current_fuel = intval(UserMeta::getMeta($userId, 'fuel_balance'));
								$new_amount = $current_fuel - $amount;
								if($new_amount <= 0){
									$new_amount = 0;
								}
								UserMeta::setMeta($userId, 'fuel_balance', $new_amount);
							}
							if($getTx AND $input['confirmations'] >= $min_conf){
								//grab a fresh balance to keep things accurate
								try{
									$balances = $xchain->getBalances($getAddress->value, true);
									if($balances AND isset($balances['BTC'])){
										UserMeta::setMeta($userId, 'fuel_balance', $balances['BTC']);
									}
								}
								catch(Exception $e){
									Log::error('Fuel debit Could not get balances for '.$getAddress->value.': '.$e->getMessage());
								}
								$save = DB::table('fuel_debits')->where('txid', $input['txid'])->update(array('confirmed' => 1));
								if(!$save){
									Log::error('Error confirming fuel debit tx '.$input['txid']);
									die();
								}
							}
						}
					}
				}
			}
		}
	}

}
