<?php namespace App\Http\Controllers;
use User, Auth, Config, UserMeta, Redirect, Response;
use Models\Distribution as Distro, Models\DistributionTx as DistroTx, Models\Fuel;
use Input, Session, Exception, Log;
use Tokenly\TokenpassClient\TokenpassAPI;
class DistributeController extends Controller {
	
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
			$csv_list =	$this->processAddressList($get_csv, $value_type, true, $cut_head);
			if(!$csv_list){
				return $this->return_error('home', 'CSV file empty or contains no valid entries');
			}
			$address_list = $csv_list;
		}
		
		if(!$address_list AND isset($input['address_list'])){
			$get_list = $this->processAddressList($input['address_list'], $value_type);
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
			$address_list = $this->divideTotalBetweenList($address_list, $use_total);
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
		
		//estimate fees
		$fee_total = 0; //come back to this
		
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
		$distro->fee_total = $fee_total;
		$distro->label = $label;
		$distro->use_fuel = $use_fuel;
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
		
		//redirect to details page
		return Redirect::route('distribute.details', $deposit_address);
	}
	
	protected function processAddressList($list, $value_type, $csv = false, $cut_csv_head = false)
	{
		$xchain = xchain();
		$list = explode("\n", str_replace("\r", "", trim($list)));
		if($csv){
			if($cut_csv_head){
				if(isset($list[0])){
					unset($list[0]);
				}
			}
		}
		$tokenpass = new TokenpassAPI;
		$address_list = array();
		foreach($list as $row){
			if($csv){
				$parse_row = str_getcsv($row);
			}
			else{
				$parse_row = explode(',', $row);
			}
			if(isset($parse_row[0]) AND isset($parse_row[1])){
				$address = trim($parse_row[0]);
				try{
					$valid_address = $xchain->validateAddress($address);
					if(!$valid_address OR !$valid_address['result']){
						$address = false;
					}
				}
				catch(Exception $e){
					Log::error('Error validating distribution address "'.$address.'": '.$e->getMessage());
					$address = false;
				}
				if(!$address){
					//see if we can lookup address by username
					try{
						$lookup_user = $tokenpass->lookupAddressByUser(trim($parse_row[0]));
						if($lookup_user AND isset($lookup_user['address'])){
							$address = $lookup_user['address'];
						}
					}
					catch(Exception $e){
						Log::error('Error looking up address by username "'.$address.'" '.$e->getMessage());
					}
					if(!$address){
						continue;
					}
				}
				if($value_type == 'percent'){
					$amount = floatval($parse_row[1]) / 100;
				}
				else{
					$amount = intval(bcmul(trim($parse_row[1]), "100000000", "0"));
				}
				if($amount <= 0){
					continue;
				}
				if(isset($address_list[$address])){
					$address_list[$address]['amount'] += $amount;
				}
				else{
					$item = array('address' => $address, 'amount' => $amount);
					$address_list[$address] = $item;
				}
			}
		}
		$address_list = array_values($address_list);
		if(count($address_list) == 0){
			return false;
		}
		return $address_list;
	}
	
	protected function divideTotalBetweenList($list, $total)
	{
		$total = intval($total);
		$used = 0;
		$max_decimals = Config::get('settings.amount_decimals');
		foreach($list as $k => $row){
			$amount = intval(round($row['amount'] * $total, $max_decimals));
			$used += $amount;
			if($used > $total){
				unset($list[$k]);
				continue;
			}
			$list[$k]['amount'] = $amount;
		}
		return $list;
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
		
		$address_list = DistroTx::where('distribution_id', $distro->id)->orderBy('quantity', 'desc')->get();
		
		$address_count = 0;
		$num_complete = 0;
		if($address_list){
			foreach($address_list as $row){
				$address_count++;
				if($row->confirmed == 1){
					$num_complete++;
				}
			}
		}
		
		return view('distribute.details', array('user' => $user, 'distro' => $distro,
												'address_list' => $address_list,
												'address_count' => $address_count,
												'num_complete' => $num_complete,));
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
		$delete = $distro->delete();
		if(!$delete){
			return $this->return_error('home', 'Error deleting distribution');
		}
		return $this->return_success('home', 'Distribution deleted!');
	}
}
