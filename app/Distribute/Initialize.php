<?php
namespace Distribute;
use Models\Distribution as Distro;
use Log;

class Initialize
{
	public function init($distro)
	{
		$this->startMonitor($distro);
	}
	
	public function startMonitor($distro, $first_stage = true)
	{
		if($distro->monitor_uuid != '' OR $distro->hold == 1){
			return false;
		}
		$webhook = route('hooks.distro.deposit').'?nonce='.hash('sha256', $distro->user_id.':'.$distro->address_uuid); 
		try{
			$xchain = xchain();
			$monitor = $xchain->newAddressMonitor($distro->deposit_address, $webhook);
		}
		catch(Exception $e)
		{
			$monitor = false;
		}
		if(is_array($monitor)){
			$distro->monitor_uuid = $monitor['id'];
			if($first_stage){
				$distro->stage = 1;
			}
			$distro->save();
			Log::info('Started distro receive monitor for #'.$distro->id);
			return true;
		}
		return false;
	}
	
	public function stopMonitor($distro)
	{
		if($distro->monitor_uuid == '' OR $distro->hold == 1){
			return false;
		}
		$xchain = xchain();
		$destroy = $xchain->destroyAddressMonitor($distro->monitor_uuid);
		Log::info('Stopped distro receive monitor for #'.$distro->id);
		return true;
	}
	
	
}
