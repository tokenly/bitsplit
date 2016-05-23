<?php
namespace Distribute;
use Models\Distribution as Distro;
use Log, Exception;
use Tokenly\TokenpassClient\TokenpassAPI;

class Initialize
{
	public function init($distro)
	{
		$this->startMonitor($distro);
        $this->registerToTokenpassProvisionalWhitelist($distro);
	}
	
	public function startMonitor($distro, $first_stage = true, $force = false)
	{
        if(!$force){
            if($distro->monitor_uuid != ''){
                return false;
            }
        }
		$webhook = route('hooks.distro.deposit').'?nonce='.hash('sha256', $distro->user_id.':'.$distro->address_uuid); 
		$send_webhook = route('hooks.distro.send').'?nonce='.hash('sha256', $distro->user_id.':'.$distro->address_uuid); 
		try{
			$xchain = xchain();
			$monitor = $xchain->newAddressMonitor($distro->deposit_address, $webhook);
			$send_monitor = $xchain->newAddressMonitor($distro->deposit_address, $send_webhook, 'send');
		}
		catch(Exception $e)
		{
			$monitor = false;
			$send_monitor = false;
		}
		if(is_array($monitor) AND is_array($send_monitor)){
			$distro->monitor_uuid = $monitor['id'];
			$distro->send_monitor_uuid = $send_monitor['id'];
			if($first_stage){
				$distro->stage = 1;
			}
			$distro->save();
			Log::info('Started distro monitors for #'.$distro->id);
			return array('send' => $send_monitor['id'], 'receive' => $monitor['id']);
		}
		return false;
	}
	
	public function stopMonitor($distro)
	{
		if($distro->monitor_uuid == ''){
			return false;
		}
		$xchain = xchain();
        try{
            $destroy = $xchain->destroyAddressMonitor($distro->monitor_uuid);
            $destroy_sender = $xchain->destroyAddressMonitor($distro->send_monitor_uuid);
        }
        catch(Exception $e){
            Log::info('Error stopping distro monitor: '.$e->getMessage());
            return false;
        }
		Log::info('Stopped distro receive monitor for #'.$distro->id);
		return true;
	}
    
    public function registerToTokenpassProvisionalWhitelist($distro)
    {
        $tokenpass = new TokenpassAPI;
        try{
            $register = $tokenpass->registerProvisionalSourceWithProof($distro->deposit_address, $distro->asset);
        }
        catch(Exception $e){
            Log::error('Error registering distro #'.$distro->id.' to Tokenpass provisional source whitelist: '.$e->getMessage());
            return false;
        }
        if(!$register){
            Log::error('Failed registering distro #'.$distro->id.' to Tokenpass provisional source whitelist');
            return false;
        }
        Log::info('Registered distro #'.$distro->id.' to Tokenpass provisional whitelist');
        return true;
    }
    
    public function deleteFromTokenpassProvisionalWhitelist($distro)
    {
        $tokenpass = new TokenpassAPI;
        try{
            $delete = $tokenpass->deleteProvisionalSource($distro->deposit_address);
        }
        catch(Exception $e){
            Log::error('Error deleting distro #'.$distro->id.' from Tokenpass provisional source whitelist: '.$e->getMessage());
            return false;
        }
        if(!$delete){
            Log::error('Failed deleting distro #'.$distro->id.' from Tokenpass provisional source whitelist');
            return false;
        }
        Log::info('Deleted distro #'.$distro->id.' from Tokenpass provisional whitelist');
        return true; 
    }
    
}
