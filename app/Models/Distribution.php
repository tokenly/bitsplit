<?php
namespace Models;

use Illuminate\Database\Eloquent\Model;
use DB, Mail, User, Log, Exception;
use Tokenly\TokenpassClient\TokenpassAPI;
use Distribute\Initialize;

class Distribution extends Model
{
	
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
        
        //send notifications
        $this->sendCompleteEmailNotification();
        $this->sendUserReceivedNotifications();
        
        //close the transaction monitors
        $initer = new Initialize;
        $initer->stopMonitor($this);        
        
		return $this->save();
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
	
}
