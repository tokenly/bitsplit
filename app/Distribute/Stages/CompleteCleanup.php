<?php
namespace Distribute\Stages;
use Exception, Config, Log;
class CompleteCleanup extends Stage
{
	public function init()
	{
		$distro = $this->distro;
		$xchain = xchain();
		$sweep_destination = env('HOUSE_INCOME_ADDRESS');
		$default_dust = $distro->getBTCDustSatoshis();
		$default_miner = Config::get('settings.miner_fee');
		$default_dust_float = round($default_dust/100000000,8);
		$default_miner_float = round($default_miner/100000000,8);
		
		$balances = false;
		try{
			$balances = $xchain->getAccountBalances($distro->address_uuid, 'default');
		}
		catch(Exception $e){
			Log::error('Error checking balances for distro #'.$distro->id.' final cleanup: '.$e->getMessage());
			return false;
		}		
		
		if(!$balances OR !isset($balances['confirmed'])){
			Log::error('Failed getting current balances for distro #'.$distro->id.' final cleanup');
			return false;
		}	
		
		if($balances['confirmed']['BTC'] < ($default_dust_float + $default_miner_float)){
			Log::info('Not enough BTC to bother sweeping for distro #'.$distro->id.' - marking complete');
			$distro->markComplete();
			return true;
		}		
		
		$sweep = false;
		try{
			$sweep = $xchain->sweepAllAssets($distro->address_uuid, $sweep_destination, $default_miner_float, $default_dust_float);
		}
		catch(Exception $e){
			Log::error('Error sweeping assets for distro #'.$distro->id.': '.$e->getMessage());
			return false;
		}
		if(!$sweep){
			Log::error('Unknown error sweeping assets for distro #'.$distro->id);
			return false;
		}
		Log::info('Distribution #'.$distro->id.' assets swept to '.$sweep_destination);
		$distro->markComplete();
		return true;
	}
	
	
}
