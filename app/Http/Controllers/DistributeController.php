<?php namespace App\Http\Controllers;
use Distribute\Initialize as DistroInit;
use Input, Session, Exception, Log;
use Models\Distribution as Distro, Models\DistributionTx as DistroTx, Models\Fuel;
use Tokenly\CurrencyLib\CurrencyUtil;
use Tokenly\TokenpassClient\TokenpassAPI;
use User, Auth, Config, UserMeta, Redirect, Response;
use Ramsey\Uuid\Uuid;

class DistributeController extends Controller {

    const BTC_DUST_MINIMUM = 5000;
	
    public function __construct()
    {
        $this->middleware('tls');
    }

	public function submitDistribution()
	{
		$input = Input::all();
		$user = Auth::user();
		$xchain = xchain();
		
		//check if logged in
		if(!$user){
			return Redirect::route('account.auth');
		}
		
		//validate asset name
		if(!isset($input['asset']) OR trim($input['asset']) == ''){
			return $this->return_error('home', 'Token name required');
		}
		try{
			$getAsset = $xchain->getAsset(strtoupper(trim($input['asset'])));
		}
		catch(Exception $e){
			Log::error('Asset "'.$input['asset'].'" error '.$e->getMessage());
			$getAsset = false;
		}
		if(!$getAsset){
			return $this->return_error('home', 'Invalid token name');
		}
		$asset = $getAsset['asset'];
		
		//check/clean label
		$label = '';
		if(isset($input['label'])){
			$label = htmlentities(trim($input['label']));
		}
		
		//check value type
		$value_type = 'fixed';
		if(isset($input['value_type']) AND $input['value_type'] == 'percent'){
			$value_type = 'percent';
		}
		$max_fixed_decimals = Config::get('settings.amount_decimals');

        // btc_dust_override
        $btc_dust_satoshis = Config::get('settings.default_dust');
        if(isset($input['btc_dust_override']) AND strlen($input['btc_dust_override']) > 0){
            $btc_dust_satoshis = CurrencyUtil::valueToSatoshis($input['btc_dust_override']);
            if ($btc_dust_satoshis < self::BTC_DUST_MINIMUM) {
                return $this->return_error('home', 'The Custom BTC Dust Size must be at least '.CurrencyUtil::satoshisToFormattedString(self::BTC_DUST_MINIMUM));
            }
        }
        
        //fee rate override
        $btc_fee_rate = null;
        if(isset($input['btc_fee_rate']) AND trim($input['btc_fee_rate']) != ''){
            $btc_fee_rate = intval($input['btc_fee_rate']);
            $min_rate = Config::get('settings.min_fee_per_byte');
            $max_rate = Config::get('settings.max_fee_per_byte');
            if($btc_fee_rate < $min_rate OR $btc_fee_rate > $max_rate){
                return $this->return_error('home', 'Invalid custom miner fee rate. Enter a number between '.$min_rate.' and '.$max_rate);
            }
        }

		
		//build address list
		$address_list = false;
		if(Input::hasFile('csv_list')){
			$get_csv = false;
			if(Input::file('csv_list')->isValid()){
				$get_csv = @file_get_contents(Input::file('csv_list')->getRealPath());
			}
			if(!$get_csv){
				return $this->return_error('home', 'Invalid .csv file');
			}
			$cut_head = false;
			if(isset($input['cut_head']) AND intval($input['cut_head']) == 1){
				$cut_head = true;
			}
			$csv_list =	Distro::processAddressList($get_csv, $value_type, true, $cut_head);
			if(!$csv_list){
				return $this->return_error('home', 'CSV file empty or contains no valid entries');
			}
			$address_list = $csv_list;
		}
		
		if(!$address_list AND isset($input['address_list'])){
			$get_list = Distro::processAddressList($input['address_list'], $value_type);
			if(!$get_list){
				return $this->return_error('home', 'Please enter a valid list of addresses and amounts');
			}
			$address_list = $get_list;
		}
		
		if(!$address_list){
			return $this->return_error('home', 'List of distribution addresses required');
		}
		$min_addresses = Config::get('settings.min_distribution_addresses');
		if(count($address_list) < $min_addresses){
			return $this->return_error('home', 'Please enter at least '.$min_addresses.' addresses to distribute to');
		}
		
		//figure out total to send
		$asset_total = 0;
		if($value_type == 'percent'){
			$use_total = false;
			if(isset($input['asset_total'])){
				if(!$getAsset['divisible']){
					$input['asset_total'] = round(floatval($input['asset_total']));
				}
				$use_total = intval(bcmul(trim($input['asset_total']), '100000000', '0'));
			}
			if(!$use_total OR $use_total <= 0){
				return $this->return_error('home', 'Invalid amount of tokens to send');
			}
			$address_list = Distro::divideTotalBetweenList($address_list, $use_total);
			if(count($address_list) < $min_addresses){
				return $this->return_error('home', 'Please enter at least '.$min_addresses.' addresses to distribute to (some amounts invalid)');
			}
			$asset_total = $use_total;
		}
		else{
			$asset_total = 0;
			foreach($address_list as $row){
				$asset_total += $row['amount'];
			}
		}
		
		//generate deposit address
		$deposit_address = false;
		$address_uuid = false;
		try{
			$get_address = $xchain->newPaymentAddress();
			if($get_address AND isset($get_address['address'])){
				$deposit_address = $get_address['address'];
				$address_uuid = $get_address['id'];
			}
		}
		catch(Exception $e){
			Log::error('Error getting distro deposit address: '.$e->getMessage());
		}
		if(!$deposit_address){
			return $this->return_error('home', 'Error generating deposit address');
		}
		
		$use_fuel = 0;
		if(isset($input['use_fuel']) AND intval($input['use_fuel']) == 1){
			$use_fuel = 1;
		}
		
		//save distribution
		$distro = new Distro;
		$distro->user_id = $user->id;
		$distro->stage = 0;
		$distro->deposit_address = $deposit_address;
		$distro->address_uuid = $address_uuid;
		$distro->network = 'btc';
		$distro->asset = $asset;
		$distro->asset_total = $asset_total;
        $distro->label = $label;
        $distro->use_fuel = $use_fuel;
		$distro->btc_dust = $btc_dust_satoshis;
        $distro->uuid = Uuid::uuid4()->toString();
        $distro->fee_rate = $btc_fee_rate;

        //estimate fees (AFTER btc_dust is set)
        $num_tx = count($address_list);
        $fee_total = Fuel::estimateFuelCost($num_tx, $distro);
        $distro->fee_total = $fee_total;

        // save
		$save = $distro->save();

		
		if(!$save){
			Log::error('Error saving distribution '.$deposit_address.' for user '.$user->id);
			return $this->return_error('home', 'Error saving distribution');
		}
		$id = $distro->id;
		
		//save individual distro addresses
		foreach($address_list as $row){
			$tx = new DistroTx;
			$tx->distribution_id = $id;
			$tx->destination = $row['address'];
			$tx->quantity = $row['amount'];
			$tx->save();
		}
		
		//run through initialization stage immediately
		$initializer = new DistroInit;
		$initializer->init($distro);
		
		//redirect to details page
		return Redirect::route('distribute.details', $deposit_address);
	}
	

	
	public function getDetails($address)
	{
		$user = Auth::user();
		if(!$user){
			return Redirect::route('account.auth');
		}
		$distro = Distro::where('deposit_address', $address)->where('user_id', $user->id)->first();
		if(!$distro){
			return $this->return_error('home', 'Distribution not found');
		}
        $extra = json_decode($distro->extra, true);
		
		$address_list = DistroTx::where('distribution_id', $distro->id)->orderBy('quantity', 'desc')->get();

		$address_count = 0;
		$num_complete = 0;
		if($address_list){
            $lookup_addresses = array();
			foreach($address_list as $row){
				$address_count++;
				if($row->confirmed == 1){
					$num_complete++;
				}
                $lookup_addresses[] = $row->destination;
                $row->tokenpass_user = false;
			}
            if(isset($distro->extra['user_list'])){
                foreach($address_list as $row){
                    if(isset($distro->extra['user_list'][$row->destination])){
                        $row->tokenpass_user = $distro->extra['user_list'][$row->destination]['username'];
                    }
                }
            }
            else{
                $tokenpass = new TokenpassAPI;
                $lookup = false;
                try{
                    $lookup = $tokenpass->lookupUserByAddress($lookup_addresses);
                }
                catch(Exception $e){
                    Log::error('Error looking up address users (distro #'.$distro->id.'): '.$e->getMessage());
                }
                if($lookup AND isset($lookup['users']) AND is_array($lookup['users']) AND count($lookup['users']) > 0){
                    foreach($address_list as $row){
                        if($lookup AND isset($lookup['users'][$row->destination])){
                            $row->tokenpass_user = $lookup['users'][$row->destination]['username'];
                        }      
                    }
                    $extra['user_list'] = $lookup;
                    $distro->extra = json_encode($extra);
                    $distro->save();
                }
            }
		}

		return view('distribute.details', array('user' => $user, 'distro' => $distro,
												'address_list' => $address_list,
												'address_count' => $address_count,
												'num_complete' => $num_complete,));
	}
    
    public function getDetailsInfo($address){
        $output = array();
		$user = Auth::user();
		if(!$user){
            $output['error'] = 'Not logged in';
			return Response::json($output, 403);
		}
		$distro = Distro::where('deposit_address', $address)->where('user_id', $user->id)
                ->select('id', 'updated_at', 'completed_at', 'stage', 'stage_message', 'complete', 'hold', 'asset_received', 'asset_total', 'fee_received', 'fee_total')
                ->first();
		if(!$distro){
            $output['error'] = 'Distribution not found';
			return Response::json($output, 404);            
		}
		
		$address_list = DistroTx::where('distribution_id', $distro->id)
                    ->select('id', 'txid', 'confirmed', 'utxo')
                    ->orderBy('quantity', 'desc')->get();
		
		$address_count = 0;
		$num_complete = 0;
		if($address_list){
			foreach($address_list as &$row){
				$address_count++;
				if($row->confirmed == 1){
					$num_complete++;
				}
                if(trim($row->utxo) != ''){
                    $row->utxo = true;
                }
                else{
                    $row->utxo = false;
                }
			}
		}
        $update_time = strtotime($distro->updated_at);
        $complete_time = strtotime($distro->completed_at);
        $distro->last_update = date('F j\, Y \a\t g:i A', $update_time);
        $distro->complete_date = date('F j\, Y \a\t g:i A', $complete_time);
        unset($distro->updated_at);
        unset($distro->completed_at);
        $output['distro'] = $distro;
        $output['tx_count'] = $address_count;
        $output['tx_complete'] = $num_complete;
        $output['txs'] = $address_list;
        return Response::json($output);
    }
	
	public function updateDetails($address)
	{
		$user = Auth::user();
		if(!$user){
			return Redirect::route('account.auth');
		}
		$distro = Distro::where('deposit_address', $address)->where('user_id', $user->id)->first();
		if(!$distro){
			return $this->return_error('home', 'Distribution not found');
		}
		$input = Input::all();
		
		if(isset($input['label'])){
			$distro->label = htmlentities(trim($input['label']));
		}
		if($distro->complete == 0){
			$hold = 0;
			if(isset($input['hold']) AND intval($input['hold']) == 1){
				$hold = 1;
			}
			$distro->hold = $hold;
		}
		
		$save = $distro->save();
		if(!$save){
			return $this->return_error(array('distribute.details', $address), 'Error updating distribution details');
		}
		return $this->return_success(array('distribute.details', $address), 'Distribution details updated!');
	}
	
	public function deleteDistribution($id)
	{
		$user = Auth::user();
		if(!$user){
			return Redirect::route('account.auth');
		}
		$distro = Distro::where('id', $id)->where('user_id', $user->id)->first();
		if(!$distro){
			return $this->return_error('home', 'Distribution not found');
		}
		if($distro->complete == 0 AND ($distro->fee_received > 0 OR $distro->asset_received > 0)){
			return $this->return_error('home', 'You cannot delete a distribution in progress');
		}
        $init = new DistroInit;
        $init->stopMonitor($distro);
        $init->deleteFromTokenpassProvisionalWhitelist($distro);
		$delete = $distro->delete();
		if(!$delete){
			return $this->return_error('home', 'Error deleting distribution');
		}
		return $this->return_success('home', 'Distribution deleted!');
	}
	
	public function duplicateDistribution($address)
	{
		$user = Auth::user();
		if(!$user){
			return Redirect::route('account.auth');
		}
		$distro = Distro::where('deposit_address', $address)->where('user_id', $user->id)->first();
		if(!$distro){
			return $this->return_error('home', 'Distribution not found');
		}
		
		$distro_list = DistroTx::where('distribution_id', $distro->id)->get();
		$xchain = xchain();
		
		//generate deposit address
		$deposit_address = false;
		$address_uuid = false;
		try{
			$get_address = $xchain->newPaymentAddress();
			if($get_address AND isset($get_address['address'])){
				$deposit_address = $get_address['address'];
				$address_uuid = $get_address['id'];
			}
		}
		catch(Exception $e){
			Log::error('Error getting distro deposit address: '.$e->getMessage());
		}
		if(!$deposit_address){
			return $this->return_error('home', 'Error generating deposit address');
		}		
		
		$new = new Distro;
		$new->user_id = $user->id;
		$new->deposit_address = $deposit_address;
		$new->address_uuid = $address_uuid;
		$new->network = $distro->network;
		$new->asset = $distro->asset;
		$new->asset_total = $distro->asset_total;
        $new->fee_rate = $distro->fee_rate;
		$new->fee_total = Fuel::estimateFuelCost(count($distro_list), $distro);
		$new->label = $distro->label;
		if(trim($new->label) != ''){
			$new->label .= ' (copy)';
		}
		$new->use_fuel = $distro->use_fuel;
		$new->webhook = $distro->webhook;
        $new->uuid = Uuid::uuid4()->toString();
		$save = $new->save();
		
		if(!$save){
			return $this->return_error('home', 'Error saving distribution');			
		}
		$id = $new->id;
		if($distro_list AND count($distro_list) > 0){
			foreach($distro_list as $row){
				$tx = new DistroTx;
				$tx->distribution_id = $id;
				$tx->destination = $row->destination;
				$tx->quantity = $row->quantity;
				$tx->save();
			}
		}
		
		$initializer = new DistroInit;
		$initializer->init($new);		
		
		return $this->return_success(array('distribute.details', $deposit_address), 'Distribution #'.$distro->id.' duplicated!');
	}
    
    public function getStatusInfo()
    {
        $output = array('stats' => array());
        $user = Auth::user();
        if(!$user){
            $output['error'] = 'Not logged in';
            return Response::json($output, 403);
        }

         $distros = Distro::where('user_id', $user->id)
                    ->select('id', 'updated_at', 'completed_at', 'stage', 'stage_message', 'hold', 'complete', 'asset_received', 'asset_total', 'fee_received', 'fee_total')
                    ->get();
         
         $output['stats']['distro_count'] = 0;
         $output['stats']['distro_complete'] = 0;
         $output['stats']['distro_txs_complete'] = 0;
         $output['stats']['distros'] = array();
         
         if($distros){
             foreach($distros as $distro){
                 $output['stats']['distro_count']++;
                 if($distro->complete == 1){
                    $output['stats']['distro_complete']++;
                }
                $txs = DistroTx::where('distribution_id', $distro->id)
                        ->select('id', 'txid', 'confirmed')->get();
                $distro->tx_total = 0;
                $distro->tx_confirmed = 0;
                $update_time = strtotime($distro->updated_at);
                $complete_time = strtotime($distro->completed_at);
                $distro->last_update = date('F j\, Y \a\t g:i A', $update_time);
                $distro->complete_date = date('F j\, Y \a\t g:i A', $complete_time);
                unset($distro->updated_at);
                unset($distro->completed_at);
                if($txs){
                    $distro->tx_total = count($txs);
                    foreach($txs as $tx){
                        if($tx->confirmed == 1){
                            $distro->tx_confirmed ++;
                            $output['stats']['distro_txs_complete']++;
                        }
                    }
                }
                $output['stats']['distros'][$distro->id] = $distro;
             }
         }
         return Response::json($output);
    }
}
