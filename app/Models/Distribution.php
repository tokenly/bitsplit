<?php
namespace Models;

use App\Models\DailyFolder;
use App\Models\FAHFolder;
use Illuminate\Database\Eloquent\Model;
use DB, Mail, User, Log, Exception, Config;
use Illuminate\Support\Facades\Auth;
use Tokenly\TokenpassClient\TokenpassAPI;
use Distribute\Initialize;
use App\Jobs\NotificationReturnJob;
use Tokenly\CurrencyLib\CurrencyUtil;

class Distribution extends Model
{
	
    public static $api_fields = array(
        'id', 'uuid', 'label', 'created_at', 'updated_at', 'stage', 'stage_message', 'complete',
        'deposit_address', 'network', 'asset', 'asset_total', 
        'fee_total', 'asset_received', 'fee_received', 'hold', 'use_fuel', 'webhook',
        'distribution_class', 'calculation_type'
        );

    protected $appends = ['tokens_per_point', 'average_points', 'fah_points', 'percentage_fah_network'];


    public static function getStageMap()
	{
		static $map_cache = false;
		if($map_cache){
			return $map_cache;
		}
		$path = storage_path().'/app/distribution-stage-map.json';
		$get = json_decode(@file_get_contents($path), true);
		if(!is_array($get)){
			throw new \Exception('Cannot open distribution stage map');
		}
		$map_cache = $get;
		return $get;
	}
	
	public static function getStageName($stage)
	{
		$stage = (string)$stage;
		$map = Distribution::getStageMap();
		if(isset($map[$stage])){
			return $map[$stage];
		}
		return false;
	}
	
	public function stageName()
	{
		return Distribution::getStageName($this->stage);
	}
	
	public function incrementStage()
	{
		$this->stage = $this->stage+1;
		$this->stage_message = '';
		return $this->save();
	}
	
	public function backtrackStage($stage = false)
	{
		if(!$stage){
			if($this->stage > 0){
				$this->stage = $this->stage - 1;
			}
		}
		else{
			$this->stage = $stage;
		}
		return $this->save();
	}
	
	public function getExtra()
	{
		$decode = json_decode($this->extra, true);
		if(!is_array($decode)){
			return false;
		}
		return $decode;
	}

    public function getBTCDustSatoshis() {
        if ($this->btc_dust == 0) { return Config::get('settings.default_dust'); }
        return $this->btc_dust;
    }
	
	public function addressCount()
	{
		return DistributionTx::where('distribution_id', $this->id)->count();
	}
	
	public function countComplete()
	{
		return DistributionTx::where('distribution_id', $this->id)->where('confirmed', 1)->count();
	}
	
	public function pendingDepositTotals()
	{
		$output = array('fuel' => 0, 'token' => 0);
		$get = DB::table('distribution_deposits')->where('distribution_id', $this->id)->where('confirmed', 0)->get();
		if($get AND count($get) > 0){
			foreach($get as $row){
				if($row->asset == 'BTC'){
					$output['fuel'] += $row->quantity;
				}
				elseif($row->asset == $this->asset){
					$output['token'] += $row->quantity;
				}
			}
		}
		return $output;
	}
	
	public function setMessage($message = ''){
		$this->stage_message = $message;
		return $this->save();
	}
	
	public function markComplete()
	{
        //mark complete
		$this->complete = 1;
        $this->completed_at = timestamp();
        
        $save = $this->save();
        if(!$save){
            Log::error('Error marking distro #'.$this->id.' complete');
            return false;
        }
        
        //ping webhook
        $this->sendWebhookUpdateNotification();
        
        try{
            //send notifications
            $this->sendCompleteEmailNotification();
            $this->sendUserReceivedNotifications();
        }
        catch(Exception $e){
            Log::error('Error sending email notifications when completing distro #'.$this->id.': '.$e->getMessage());
        }
        
        try{
            //close the transaction monitors
            $initer = new Initialize;
            $initer->stopMonitor($this);            
        }
        catch(Exception $e){
            Log::error('Error closing xchain monitor when completing distro #'.$this->id.': '.$e->getMessage()); 
        }
        
		return true;
	}
    
    public function sendCompleteEmailNotification()
    {
        $user = User::find($this->user_id);
        $distro_tx = DistributionTx::where('distribution_id', $this->id)->get();
        Mail::send('emails.distribution.complete', ['user' => $user, 'distro' => $this, 'distro_tx' => $distro_tx],
            function($m) use ($user) {
                $m->from(env('MAIL_FROM_ADDRESS'));
                $m->to($user->email, $user->username)->subject('BitSplit Distribution #'.$this->id.' Complete - '.timestamp());
        });
    }
    
    public function sendUserReceivedNotifications()
    {
        $extra = json_decode($this->extra, true);
        $distro_tx = DistributionTx::where('distribution_id', $this->id)->get();
        $notify_list = array();
        foreach($distro_tx as $tx){
            $lookup = false;
            if(isset($extra['user_list'][$tx->destination])){
                $lookup = $extra['user_list'][$tx->destination];
            }
            if($lookup AND isset($lookup['email'])){
                if(!isset($notify_list[$lookup['email']])){
                    $notify_item = array();
                    $notify_item['txs'] = array($tx);
                    $notify_item['username'] = $lookup['username'];
                    $notify_item['email'] = $lookup['email'];
                    $notify_list[$lookup['email']] = $notify_item;
                }
                else{
                    $notify_list[$lookup['email']]['txs'][] = $tx;
                }
            }
        }
        if(count($notify_list) == 0){
            return false; //no one to notify
        }
        foreach($notify_list as $email => $row){
            Log::info('Notifiying distro #'.$this->id.' recipient '.$email);
            $username = $row['username'];
            Mail::send('emails.distribution.recipient', ['notify_data' => $row, 'distro' => $this],
                function($m) use ($email, $username) {
                    $m->from(env('MAIL_FROM_ADDRESS'));
                    $m->to($email, $username)->subject('BitSplit Distribution #'.$this->id.' - '.$this->asset.' Received');
            });
        }
    }

    public static function processAddressList($list, $value_type, $csv = false, $cut_csv_head = false, $calculation_type = 'even')
    {
		$xchain = xchain();
        $array = true;
        if(!is_array($list)){
            $array = false;
            $list = explode("\n", str_replace("\r", "", trim($list)));
            if($csv){
                if($cut_csv_head){
                    if(isset($list[0])){
                        unset($list[0]);
                    }
                }
            }
        }
		$tokenpass = new TokenpassAPI;
		$address_list = array();
		foreach($list as $lk => $row){
            if($array){
                if(is_array($row) AND isset($row['address']) AND isset($row['amount'])){
                    $parse_row = array($row['address'], $row['amount']);
                }
                else{
                    $parse_row = array($lk, $row);
                }
            }
            else{
                if($csv){
                    $parse_row = str_getcsv($row);
                }
                else{
                    $parse_row = explode(',', $row);
                }
            }
			if(isset($parse_row[0]) AND isset($parse_row[1])){
				$address = trim($parse_row[0]);
                /*
                //disable for now, addresses are validated elsewhere in this fork
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
				}*/
                if(!$address){
                    continue;
                }
				if($value_type == 'percent' && $calculation_type === 'even'){
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
	
	public static function divideTotalBetweenList($list, $total)
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
    
    public function sendWebhookUpdateNotification()
    {
        if(trim($this->webhook) == ''){
            return;
        }

        $payload = $this->getWebhookNotificationPayload();
        Log::info('Sending webhook status update ('.$this->stageName().') notification for distro #'.$this->id.' to '.$this->webhook);

        $user = User::find($this->user_id);
        app('App\Distribute\WebhookCaller')->sendWebhook($payload['event'], $user, $this->webhook, $payload);
    }
    
    protected function getWebhookNotificationPayload()
    {
        $output = array();
        $output['event'] = 'update';
        if($this->complete == 1){
            $output['event'] = 'complete';
        }
        $output['notificationId'] = null; //filled automatically by notification code
        $output['distributionId'] = $this->uuid;
        $output['label'] = $this->label;
        $output['createdAt'] = $this->created_at;
        $output['updatedAt'] = $this->updated_at;
        $output['stage'] = $this->stage;
        $output['stageMessage'] = $this->stage_message;
        $output['complete'] = $this->complete;
        $output['depositAddress'] = $this->deposit_address;
        $output['network'] = $this->network;
        $output['asset'] = $this->asset;
        $output['assetTotal'] = $this->asset_total;
        $output['feeTotal'] = $this->fee_total;
        $output['assetReceived'] = $this->asset_received;
        $output['feeReceived'] = $this->fee_received;
        $output['hold'] = $this->hold;
        $output['use_fuel'] = $this->use_fuel;
        $output['webhook'] = $this->webhook;
        $output['assetTotalFloat'] = CurrencyUtil::satoshisToValue($output['assetTotal']);
        $output['feeTotalFloat'] = CurrencyUtil::satoshisToValue($output['feeTotal']);
        $output['assetReceivedFloat'] = CurrencyUtil::satoshisToValue($output['assetReceived']);
        $output['feeReceivedFloat'] = CurrencyUtil::satoshisToValue($output['feeReceived']);
        $output['stageName'] = self::getStageName($output['stage']);
        
        return $output;
    }
    
    public static function getFoldingAddressList($folding_start_date, $folding_end_date, $asset, $distribution_class, $extra) {
	    //Pass user as a parameter
        $user = Auth::user();
        //Set general query
        $query = DailyFolder::whereBetween('date', [$folding_start_date, $folding_end_date]);
        if($extra['calculation_type'] === 'unique') {
            switch ($extra['scan_distros_from']) {
                case 'My Account':
                    $old_rows = DB::table('distribution_tx')
                        ->join('distributions', 'distribution_tx.distribution_id', '=', 'distributions.id')
                        ->where('distributions.complete', 1)
                        ->where('distributions.user_id', $user->id)
                        ->where('distributions.asset', $asset)
                        ->groupBy('distribution_tx.destination')
                        ->get(['distribution_tx.destination']);
                        break;
                case 'Official FLDC':
                    $old_rows = DB::table('distribution_tx')
                        ->join('distributions', 'distribution_tx.distribution_id', '=', 'distributions.id')
                        ->join('users', 'users.id', '=', 'distributions.user_id')
                        ->where('distributions.complete', 1)
                        ->where('distributions.asset', $asset)
                        ->where('users.email', \Illuminate\Support\Facades\Config::get('settings.official_fldc_email'))
                        ->groupBy('distribution_tx.destination')
                        ->get(['distribution_tx.destination']);
                        break;
                case 'All Accounts':
                    $old_rows = DB::table('distribution_tx')
                        ->join('distributions', 'distribution_tx.distribution_id', '=', 'distributions.id')
                        ->where('distributions.complete', 1)
                        ->where('distributions.asset', $asset)
                        ->groupBy('distribution_tx.destination')
                        ->get(['distribution_tx.destination']);
                        break;
            }
            $addresses = [];
            foreach ($old_rows as $row) {
                $addresses[] = $row->destination;
            }
            $query->whereNotIn('daily_folders.bitcoin_address', $addresses);
        }
        //TODO: Make parameters less flexible by having named params instead on an "extra" array
	    switch ($distribution_class) {
            case 'Minimum FAH points':
                $folding_address_list = $query
                    ->where(function ($sub_query) use ($asset, $extra)  {
                        $sub_query->where('reward_token', 'ALL')
                            ->orWhere('reward_token',  $asset);
                    } )
                    ->selectRaw('*, SUM(new_credit) AS new_credit')
                    ->where('new_credit', '>=' , $extra['minimum_fah_points'])
                    ->groupBy('bitcoin_address')
                    ->get();
                break;
            case 'Top Folders':
                $folding_address_list = $query
                    ->where(function ($sub_query) use ($asset, $extra)  {
                        $sub_query->where('reward_token', 'ALL')
                            ->orWhere('reward_token',  $asset);
                    } )
                    ->selectRaw('*, SUM(new_credit) AS new_credit')
                    ->orderBy('new_credit', 'desc')
                    ->groupBy('bitcoin_address')
                    ->limit($extra['amount_top_folders'])
                    ->get();
                break;
            case 'Random':
                $query = $query
                    ->where(function ($sub_query) use ($asset, $extra)  {
                        $sub_query->where('reward_token', 'ALL')
                            ->orWhere('reward_token',  $asset);
                    } )
                    ->where('new_credit', '>', 0)
                    ->selectRaw('*, SUM(new_credit) AS new_credit')
                    ->orderBy('new_credit', 'desc')
                    ->groupBy('bitcoin_address')->get();
                    
                $folding_address_list = array();
                $choose_amount = 1;
                if(isset($extra['amount_random_folders']) AND intval($extra['amount_random_folders']) > 0){
                    $choose_amount = intval($extra['amount_random_folders']);
                }
                if(isset($extra['weight_cache_by_fah']) && $extra['weight_cache_by_fah']) {
                    //randomize selection but use new_credit to determine probabilities
                    $total_credit = 0;
                    $total_count = 0;
                    foreach($query as $k => $daily_folder){
                        $total_credit += $daily_folder->new_credit;
                        $total_count++;
                    }
                    $winners = 0;
                    while($winners < $choose_amount && $winners < $total_count){
                        foreach($query as $k => $daily_folder){
                            if(isset($folding_address_list[$k])){
                                //already won
                                continue;
                            }
                            $probability = ($daily_folder->new_credit / $total_credit) * 1000;
                            $lucky = mt_rand(0, 1000);
                            if($lucky <= $probability){
                                //winner
                                $folding_address_list[$k] = $daily_folder;
                                $winners++;
                            }
                        }
                    }
                } else {
                    //get completely (pseudo)random addresses
                    $keys = array();
                    foreach($query as $k => $daily_folder){
                        $keys[] = $k;
                    }
                    $select_keys = array_rand($keys, $choose_amount);
                    foreach($select_keys as $k){
                        $folding_address_list[$k] = $query[$k];
                    }
                }
                $folding_address_list = array_values($folding_address_list);
                break;
            default:
                $folding_address_list = $query
                    ->where(function ($sub_query) use ($asset) {
                        $sub_query->where('reward_token', 'ALL')
                            ->orWhere('reward_token',  $asset);
                    })
                    ->selectRaw('*, SUM(new_credit) AS new_credit')
                    ->groupBy('bitcoin_address')
                    ->get();
                break;
        }
        return $folding_address_list;
    }

    function getFahPointsAttribute() {
        return DistributionTx::where('distribution_id', $this->id)->sum('folding_credit');
    }

    function getAveragePointsAttribute() {
        if($this->total_folders <= 0){
            return 0;
        }
        return $this->fah_points / $this->total_folders;
    }

    function getTokensPerPointAttribute() {
	    $fah_points = $this->fah_points;
	    if(empty($fah_points)) {
            return 0;
	    }
	    $asset_total = bcdiv((string)$this->asset_total, "100000000", "8");
        return bcdiv($asset_total, (string)$fah_points, "8");
    }

    function getPercentageFahNetworkAttribute() {
	    $new_credit = $this->fah_points;
        $folding_start_date = date("Y-m-d", strtotime($this->folding_start_date)).' 00:00:00';
        $folding_end_date = date("Y-m-d", strtotime($this->folding_end_date)).' 23:59:59';

        $datediff = strtotime($this->folding_end_date) - strtotime($this->folding_start_date);
        $days =  floor($datediff / (60 * 60 * 24));
        
        if($days <= 0){
            return 0;
        }
        
        return DailyFolder::whereBetween('date', [$folding_start_date, $folding_end_date])->sum('network_percentage') / $days;
    }

    public function user()
    {
        return $this->belongsTo('User');
    }
}
