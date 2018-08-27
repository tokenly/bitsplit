<?php namespace App\Http\Controllers;
use App\Http\Requests\Request;
use App\Http\Requests\SubmitDistribution;
use App\Models\DailyFolder;
use App\Libraries\Substation\Substation;
use App\Libraries\Substation\UserAddressManager;
use App\Services\DistributionService;
use Distribute\Initialize as DistroInit;
use Input, Session, Exception, Log;
use Models\Distribution as Distro, Models\DistributionTx as DistroTx, Models\Fuel;
use Ramsey\Uuid\Uuid;
use Tokenly\AssetNameUtils\Validator as AssetValidator;
use Tokenly\CurrencyLib\CurrencyUtil;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\TokenpassClient\TokenpassAPI;
use User, Auth, Config, UserMeta, Redirect, Response;
use Tokenly\TokenmapClient\TokenmapClient;

class DistributeController extends Controller {

    public function __construct()
    {
        $this->middleware('tls');
    }

    public function newDistribution()
    {
    	$user = Auth::user();	
    	
    	//check if logged in
		if(!$user){
			return Redirect::route('account.auth');
		}

		//check if user has been approved to initiate distribution
		if(!$user->approval_admin_id AND !$user->admin) {
			Session::flash('message', 'You may not initiate a token distribution until your account is approved. Please be patient.');
            Session::flash('message-class', 'alert-danger'); 
    		return Redirect::route('home');
    	}

    	return view('distribute.new', array('user' => $user));
    }

    public function submitDistro(SubmitDistribution $request) {
        try {
            $create_distribution_service = new DistributionService($request);
            $deposit_address = $create_distribution_service->create();
            return Redirect::route('distribute.details', $deposit_address);
        } catch (\Exception $e) {
            Session::flash('message', $e->getMessage());
            Session::flash('message-class', 'alert-danger');
            return Redirect::route('home');
        }
    }


	public function getDetails($address)
	{
		$user = Auth::user();

		$distro = Distro::where('deposit_address', $address)->first();
     
		//Only allow public view if distribution is completed
		if(!$distro OR (!$user AND !$distro->complete)){
            return $this->return_error('home', 'Distribution not found');
		}

        //Only allow public view if distribution is completed
        if((!$user OR $user->id != $distro->user_id) && !$distro->complete){
            return Redirect::route('account.auth');
        }

        $extra = json_decode($distro->extra, true);

		if($distro->calculation_type === 'Static') {
            $address_list = DistroTx::where('distribution_id', $distro->id)->orderBy('folding_credit', 'desc')->get();
        } else {
		    $address_list = DistroTx::where('distribution_id', $distro->id)->orderBy('quantity', 'desc')->get();
        }

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
                $tokenpass = app(TokenpassAPI::class);
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
                ->select('id', 'updated_at', 'completed_at', 'stage', 'stage_message', 'complete', 'hold', 'asset_received', 'asset_total', 'fee_received', 'fee_total', 'offchain')
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
		
        //generate deposit address
        $deposit_address = false;
        $address_uuid = false;
        try{
            $deposit_address_details = app(UserAddressManager::class)->newPaymentAddressForUser($user);
            $deposit_address = $deposit_address_details['address'];
            $address_uuid = $deposit_address_details['uuid'];
        }
        catch(Exception $e){
            EventLog::logError('depositAddress.error', $e, [
                'userId' => $user['id'],
            ]);
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

        $new->folding_start_date = $distro->folding_start_date;
        $new->folding_end_date = $distro->folding_end_date;

        $new->distribution_class = $distro->distribution_class;
        $new->calculation_type = $distro->calculation_type;
        $new->total_folders = $distro->total_folders;


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
                $tx->folding_credit = $row->folding_credit;
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
                    ->select('id', 'updated_at', 'completed_at', 'stage', 'stage_message', 'hold', 'complete', 'asset_received', 'asset_total', 'fee_received', 'fee_total', 'offchain')
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

    function getDistributionsHistory()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $distros = Distro::whereDoesntHave('user', function ($query) {
            $query->where('email', '=', config('settings.official_fldc_email'));
        })->where('complete', 1)->paginate(35);
        return view('distribute.public_history', array('user' => $user, 'distros' => $distros, 'type' => 'Public'));
    }

    function getOfficialFldcDistributionsHistory()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $distros = Distro::whereHas('user', function ($query) {
            $query->where('email', '=', config('settings.official_fldc_email'));
        })->where('complete', 1)->paginate(35);
        return view('distribute.public_history', array('user' => $user, 'distros' => $distros, 'type' => 'Official FoldingCoin'));
    }

}
