<?php 
namespace App\Http\Controllers;
use Illuminate\Http\Request, Input, Log;
use User, UserMeta, Config, Models\Distribution, Models\DistributionTx, Response, DB;
use Models\Fuel;
class WebhookController extends Controller {

	public function __construct()
	{
        $this->middleware('tls');
	}

	public function DistributorDeposit(Request $request)
	{
		$hook = app('Tokenly\XChainClient\WebHookReceiver');
		$xchain = xchain();
		$parseHook = $hook->validateAndParseWebhookNotificationFromRequest($request);
		$input = $parseHook['payload'];
		if(is_array($input) AND isset($input['notifiedAddress']) AND Input::get('nonce')){
			$getDistro = Distribution::where('deposit_address', $input['notifiedAddress'])->first();
			if($getDistro AND $getDistro->complete == 0){
				$userId = $getDistro->user_id;
				$calc_nonce = hash('sha256', $userId.':'.$getDistro->address_uuid);
				$nonce = Input::get('nonce');
				$time = timestamp();
				$min_conf = Config::get('settings.min_distribution_confirms');
				if($nonce == $calc_nonce){
					if($input['asset'] == 'BTC' OR $input['asset'] == $getDistro->asset){
						$getTx = DB::table('distribution_deposits')->where('txid', $input['txid'])->first();
						if(!$getTx){
							$tx_data = array('distribution_id' => $getDistro->id, 'asset' => $input['asset'], 
											'created_at' => $time, 'updated_at' => $time,
											'quantity' => $input['quantitySat'], 
											'txid' => $input['txid'], 'confirmed' => 0);
							if($input['confirmations'] >= $min_conf){
								$tx_data['confirmed'] = 1;
							}
							$save = DB::table('distribution_deposits')->insert($tx_data);
							if(!$save){
								Log::error('Error saving distro deposit '.$input['txid']);
								die();
							}
							$getDistro->setMessage('receiving');
                            $getDistro->sendWebhookUpdateNotification();
						}
						else{
							if($getTx->confirmed == 1){
								Log::error('Distro deposit already confirmed '.$input['txid']);
							}
							else{
								if($input['confirmations'] >= $min_conf){
									$save = DB::table('distribution_deposits')->where('id', $getTx->id)->update(array('confirmed' => 1, 'updated_at' => $time));
									if(!$save){
										Log::error('Error saving distro deposit '.$input['txid']);
										die();
									}
								}
							}
						}
						$all_deposits = DB::table('distribution_deposits')->where('distribution_id', $getDistro->id)->get();
						if($all_deposits AND count($all_deposits) > 0){
							$token_total = 0;
							$fuel_total = 0;
							foreach($all_deposits as $row){
								if($row->confirmed == 1){
									if($row->asset == 'BTC'){
										$fuel_total += $row->quantity;
									}
									elseif($row->asset == $getDistro->asset){
										$token_total += $row->quantity;
									}
								}
							}
							$getDistro->asset_received = $token_total;
							$getDistro->fee_received = $fuel_total;
							$save = $getDistro->save();
							if(!$save){
								Log::error('Error saving distro received totals #'.$getDistro->id.' - '.$input['txid']);
								die();
							}
							Log::info('Distro #'.$getDistro->id.' received: '.round($getDistro->asset_received / 100000000, 8).' '.$getDistro->asset.' '.round($getDistro->fee_received / 100000000, 8).' BTC');
						}
					}
				}
			}
		}		
	}
	
	
	public function DistributorSend(Request $request)
	{
		$hook = app('Tokenly\XChainClient\WebHookReceiver');
		$xchain = xchain();
		$parseHook = $hook->validateAndParseWebhookNotificationFromRequest($request);
		$input = $parseHook['payload'];
		if(is_array($input) AND isset($input['notifiedAddress']) AND Input::get('nonce')){
			$getDistro = Distribution::where('deposit_address', $input['notifiedAddress'])->first();
			if($getDistro AND $getDistro->complete == 0){
				$userId = $getDistro->user_id;
				$calc_nonce = hash('sha256', $userId.':'.$getDistro->address_uuid);
				$nonce = Input::get('nonce');
				$time = timestamp();
				$min_conf = Config::get('settings.min_distribution_confirms');
				if($nonce == $calc_nonce){
					if($input['asset'] == 'BTC'){
						//see if this is a priming transaction
						$getTx = DB::table('distribution_primes')->where('txid', $input['txid'])->first();
						if($getTx AND $input['confirmations'] >= $min_conf){
							if($getTx->confirmed == 1){
								Log::info('Prime tx '.$input['txid'].' for distro '.$getDistro->id.' already confirmed');
							}
							else{
								$save = DB::table('distribution_primes')->where('id', $getTx->id)->update(array('confirmed' => 1, 'updated_at' => $time));
								if(!$save){
									Log::error('Error saving distribution '.$getDistro->id.' prime tx');
								}
								else{
									Log::info('Prime tx '.$input['txid'].' for distro '.$getDistro->id.' confirmed');
								}
							}
						}
						else{
							Log::error('Distro send tx not found #'.$getDistro->id.' '.$input['txid']);
						}
						
					}
					elseif($input['asset'] == $getDistro->asset){
						//see if this matches up to an outgoing token distribution tx
						if(isset($input['destinations'][0])){
							$getTx = DistributionTx::where('distribution_id', $getDistro->id)->where('destination', $input['destinations'][0])->first();
							if($getTx){
								if($getTx->confirmed == 1){
									Log::info('Prime tx '.$input['txid'].' for distro '.$getDistro->id.' already confirmed');
								}
								else{
									//also make sure quantity matches up
									if($input['quantitySat'] == $getTx->quantity){
										//now check for confirms
										if($input['confirmations'] >= $min_conf){
											$getTx->txid = $input['txid'];
											$getTx->confirmed = 1;
											$save = $getTx->save();
											if(!$save){
												Log::error('Error saving distribution '.$getDistro->id.' tx to '.$getTx->destination);
											}
											else{
												Log::info('Distribution'.$getDistro->id.' tx to '.$getTx->destination.' confirmed: '.$getTx->txid);
											}
										}
									}
								}
							}
							else{
								Log::error('Distro '.$getDistro->id.' tx not found '.$input['txid']);
							}
						}
						else{
							Log::error('Distro '.$getDistro->id.' tx incorrect destinations '.$input['txid']);
						}
					}
					else{
						Log::error('Distro '.$getDistro->id.' tx incorrect asset '.$input['txid']);
					}
				}
			}
		}
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
				$time = timestamp();
				if($nonce == $calc_nonce){
					//everything is matching up so far
					$getTx = DB::table('fuel_deposits')->where('txid', $input['txid'])->first();
					$valid_assets = Config::get('settings.valid_fuel_tokens');
					$min_conf = Config::get('settings.min_fuel_confirms');
					
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
						try{
							$balances = $xchain->getAccountBalances($uuid, 'default');
							if($balances){
								UserMeta::setMeta($userId, 'fuel_balance', round($balances['confirmed']['BTC']*100000000));
								UserMeta::setMeta($userId, 'fuel_pending', round($balances['unconfirmed']['BTC']*100000000));
								Log::info('User '.$userId.' fuel balances - '.json_encode($balances));
							}
						}
						catch(Exception $e){
							Log::error('Fuel deposit could not get balances '.$userId.': '.$e->getMessage());
							die();
						}
						if(!$balances){
							Log::error('Fuel deposit no balances '.$userId.': '.$e->getMessage());
							die();
						}				
						if($input['confirmations'] >= $min_conf){
							if($getTx){
								$save = DB::table('fuel_deposits')->where('txid', $input['txid'])->update(array('confirmed' => 1, 'updated_at' => $time));
								if(!$save){
									Log::error('Error saving fuel deposit '.$input['txid']);
									die();
								}
							}
							Log::info('Fuel deposited user '.$userId.': '.$input['quantity'].' '.$input['asset']);
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
		$valid_assets = Config::get('settings.valid_fuel_tokens');
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
						if($input['asset'] != 'BTC' AND !isset($valid_assets[$input['asset']])){
							Log::error('Invalid fuel debit asset '.$input['asset'].' '.$input['txid']);
							die();
						}
						$tx_data = array('user_id' => $userId, 'asset' => $input['asset'], 
										'created_at' => $time, 'updated_at' => $time,
										'quantity' => $input['quantitySat'] + $input['bitcoinTx']['feesSat'], 
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
