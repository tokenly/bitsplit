<?php
namespace Distribute;
use Models\Distribution as Distro;

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
		$webhook = ''; //add webhook route later
		try{
			$xchain = xchain();
			$monitor = $xchain->newAddressMonitor($distro->deposit_address, $webhook);
		}
		catch(Exception $e)
		{
			$monitor = false;
		}
		if(is_array($monitor)){
			$distro->monitor_uuid = $monitor['uuid'];
			if($first_stage){
				$distro->stage = 1;
			}
			$distro->save();
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
		return true;
	}
	
	
}
