<?php
namespace App\Http\Controllers;
use User, Auth, Config, UserMeta, Redirect, Response, Route;
use Models\Distribution as Distro, Models\DistributionTx as DistroTx, Models\Fuel;
use Input, Session, Exception, Log;
use Tokenly\TokenpassClient\TokenpassAPI;
use Tokenly\CurrencyLib\CurrencyUtil;
use Distribute\Initialize as DistroInit;
use Ramsey\Uuid\Uuid;

class APIController extends Controller
{
    
    protected $signed_routes = array('api.distribute.create',
                                     'api.distribute.update',
                                     'api.distribute.delete'
                                     );
    
    public static $api_user = false;
    
    function __construct()
    {
        parent::__construct();
        $action = Route::current()->getAction();
        if(in_array($action['as'], $this->signed_routes)){
            $this->middleware('auth.api.signed');
        }
        else{
            $this->middleware('auth.api');
        }
        $this->middleware('tls');
        $this->middleware('cors');
    }
    
    
    public function getDistributionList()
    {
        $user = self::$api_user;
        $output = array('result' => false);
        $fields = Distro::$api_fields;
        $get = Distro::where('user_id', $user->id)->select($fields)->orderBy('id', 'desc')->get();
        if($get){
            $get = $get->toArray();
            foreach($get as $k => $row){
                $get[$k] = $this->processDistroRow($row, true);
            }
        }
        $output['result'] = $get;
        return Response::json($output);
    }
    
    public function createDistribution()
    {
        $user = self::$api_user;
        $output = array('result' => false);
        $input = Input::all();
        $xchain = xchain();
        $min_addresses = Config::get('settings.min_distribution_addresses');
        $max_fixed_decimals = Config::get('settings.amount_decimals');               
        
        //check valid asset/token name
        if(!isset($input['asset'])){
            $output['error'] = 'Asset name required';
            return Response::json($output, 400);
        }
        try{
            $getAsset = $xchain->getAsset(strtoupper(trim($input['asset'])));
        }
        catch(Exception $e){
            Log::error('API - Distro Asset "'.$input['asset'].'" error '.$e->getMessage());
            $getAsset = false;
        }
        if(!$getAsset){
        $output['error'] = 'Asset not found';
            return Response::json($output, 400);
        }
        $asset = $getAsset['asset'];
         
        //check value type
        $value_type = 'fixed';
        if(isset($input['value_type']) AND $input['value_type'] == 'percent'){
            $value_type = 'percent';
        }
 
        //figure out the list of addresses to send to
        if(!isset($input['address_list'])){
            $output['error'] = 'Address list required';
            return Response::json($output, 400);
        }         
        $address_list = Distro::processAddressList($input['address_list'], $value_type);
		if(count($address_list) < $min_addresses){
			$output['error'] = 'Please enter at least '.$min_addresses.' addresses to distribute to';
            return Response::json($output, 400);
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
				$output['error'] = 'Invalid amount of tokens to send';
                return Response::json($output, 400);
			}
			$address_list = Distro::divideTotalBetweenList($address_list, $use_total);
			if(count($address_list) < $min_addresses){
				$output['error'] = 'Please enter at least '.$min_addresses.' addresses to distribute to (some amounts invalid)';
                return Response::json($output, 400);
			}
			$asset_total = $use_total;
		}
		else{
			$asset_total = 0;
			foreach($address_list as $row){
				$asset_total += $row['amount'];
			}
		}
        
        //check for custom label
        $label = '';
        if(isset($input['label'])){
            $label = trim(htmlentities($input['label']));
        }        
        
        //check for webhook field
        $webhook = null;
        if(isset($input['webhook'])){
            if(strpos('http://', $input['webhook']) === 0 OR strpos('https://', $input['webhook']) === 0){
                $webhook = $input['webhook'];
            }
        }
        
        //check for putting on hold right away
        $hold = 0;
        if(isset($input['hold']) AND intval($input['hold']) == 1){
            $hold = 1;
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
			Log::error('Error getting distro deposit address (API) : '.$e->getMessage());
		}
		if(!$deposit_address){
			$output['error'] = 'Error generating deposit address';
            return Response::json($output, 500);
		}
		
        //check if auto pumping fuel
		$use_fuel = 0;
		if(isset($input['use_fuel']) AND intval($input['use_fuel']) == 1){
			$use_fuel = 1;
		}
		
		//build the distribution
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
        $distro->webhook = $webhook;
        $distro->hold = $hold;
        $distro->uuid = Uuid::uuid4()->toString();

        //estimate fees
        $num_tx = count($address_list);
        $fee_total = Fuel::estimateFuelCost($num_tx, $distro);
        $distro->fee_total = $fee_total;

        // save the distribution
		$save = $distro->save();
		if(!$save){
			Log::error('Error saving distribution (API)  '.$deposit_address.' for user '.$user->id);
			$output['error'] = 'Error saving distribution';
            return Response::json($output, 500);
		}


        
        //get the new ID
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
        
        //return details
        return $this->getDistribution($distro->uuid);
    }
    
    public function getDistribution($id)
    {
        $output = array('result' => false);
        $code = 200;
        $get = $this->getDistroFromId($id);
        if($get){
            $get = $get->toArray();
            $get = $this->processDistroRow($get);
        }
        else{
            $output['error'] = 'Distribution not found';
            $code = 404;
        }
        $output['result'] = $get;
        return Response::json($output, $code);
    }
    
    public function updateDistribution($id)
    {
        $output = array('result' => false);
        $input = Input::all();
        $get = $this->getDistroFromId($id);
        if($get){
            $distro = $get;
            //check for label update
            if(isset($input['label'])){
                $distro->label = htmlentities(trim($input['label']));
            }
            
            //check for hold toggle
            $hold = 0;
            if(isset($input['hold']) AND intval($input['hold']) == 1){
                if($distro->complete == 0){
                    $hold = 1;
                }
                else{
                    $output['error'] = 'Cannot put complete distribution on hold';
                    return Response::json($output, 400);
                }
            }
            $distro->hold = $hold;
            
            //check for webhook
            if(isset($input['webhook'])){
                if(strpos('http://', $input['webhook']) === 0 OR strpos('https://', $input['webhook']) === 0){
                    $distro->webhook = $input['webhook'];
                }
            }
            
            $save = $distro->save();
            if(!$save){
                $output['error'] = 'Error saving distribution';
                return Response::json($output, 500);
            }
            
            return $this->getDistribution($distro->uuid);
        }
        else{
            $output['error'] = 'Distribution not found';
            return Response::json($output, 404);
        }
    }
    
    public function deleteDistribution($id)
    {
        $output = array('result' => false);
        $code = 200;
        $get = $this->getDistroFromId($id);
        if($get){
            if($get->complete == 0){
                if($get->asset_received > 0 OR $get->fee_received > 0){
                    $output['error'] = 'Cannot delete an in-progress distribution with received funds';
                    return Response::json($output, 400);
                }
            }
            $delete = $get->delete();
            if($delete){
                $output['result'] = true;
            }
            else{
                $output['error'] = 'Error deleting distribution';
                $code = 500;
            }
        }
        else{
            $output['error'] = 'Distribution not found';
            $code = 404;
        }
        return Response::json($output, $code);
    }
    
    public function getLoggedAPIUserInfo()
    {
        $user = self::$api_user;
        $info = User::getDashInfo($user->id, true);
        $output['result'] = $info;
        return Response::json($output);
    }
    
    protected function getDistroFromId($id)
    {
        $user = self::$api_user;
        $fields = Distro::$api_fields;
        $fields[] = 'user_id';        
        $get = Distro::where('uuid', $id)->orWhere('deposit_address', $id)->select($fields)->first();
        if($get AND ($get->user_id == $user->id OR intval($user->admin) == 1)){
            return $get;
        }
        return false;
    }
    
    protected function processDistroRow($row, $no_list = false)
    {
        if(isset($row['user_id'])){
            unset($row['user_id']);
        }
        $row['id'] = $row['uuid'];
        unset($row['uuid']);
        $row['asset_received'] = intval($row['asset_received']);
        $row['fee_received'] = intval($row['fee_received']);
        $row['fee_total'] = intval($row['fee_total']);
        $row['asset_total'] = intval($row['asset_total']);
        $row['asset_totalFloat'] = CurrencyUtil::satoshisToValue($row['asset_total']);
        $row['fee_totalFloat'] = CurrencyUtil::satoshisToValue($row['fee_total']);
        $row['asset_receivedFloat'] = CurrencyUtil::satoshisToValue($row['asset_received']);
        $row['fee_receivedFloat'] = CurrencyUtil::satoshisToValue($row['fee_received']);
        $row['stage_name'] = Distro::getStageName($row['stage']);
        $row['hold'] = intval($row['hold']);
        $row['complete'] = intval($row['complete']);
        $row['stage'] = intval($row['stage']);
        $row['use_fuel'] = intval($row['use_fuel']);
        if(!$no_list){
            $row['address_list'] = $this->getDistroTxList($row['id']);
        }
        return $row;
    }
    
    protected function getDistroTxList($id)
    {
        $get = DistroTx::where('distribution_id', $id)->select(DistroTx::$api_fields)->get();
        if(!$get){
            return false;
        }
        $list = $get->toArray();
        foreach($list as $k => $row){
            $list[$k]['quantity'] = intval($row['quantity']);
            $list[$k]['confirmed'] = intval($row['confirmed']);
            $list[$k]['quantity_float'] = CurrencyUtil::satoshisToValue($row['quantity']);
        }
        return $list;
    }
}
