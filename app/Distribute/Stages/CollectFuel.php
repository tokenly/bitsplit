<?php
namespace Distribute\Stages;
use Models\Fuel, Exception, Log, DB;
class CollectFuel extends Stage
{
	public function init()
	{
		parent::init();
		//check fee received, if none then look at any pending and figure out how much fuel to pump
		$distro = $this->distro;
		if($distro->fee_received >= $distro->fee_total){
			$distro->incrementStage();
			$distro->setMessage(); //clear message
			Log::info('Distro Fuel collected - #'.$distro->id);
			return true;		
		}
		if($distro->use_fuel == 1){
			//automatically fuel this distribution
			$pending = $distro->pendingDepositTotals();
			$pending = $pending['fuel'];
			$diff = $distro->fee_total - $pending;
			if($diff > 0){
				try{
					$pump = Fuel::pump($distro->user_id, $distro->deposit_address, $diff);
					if($pump){
						$time = timestamp();
						$tx_data = array('created_at' => $time, 'updated_at' => $time,
										 'distribution_id' => $distro->id, 'asset' => 'BTC',
										 'quantity' => $diff, 'txid' => $pump['txid'], 'confirmed' => 0);
						$save = DB::table('distribution_deposits')->insert($tx_data);
						if(!$save){
							Log::error('Error saving fuel pump for distribution #'.$distro->id);
							return false;
						}
						Log::info('Distro #'.$distro->id.' fuel pumped '.$pump['txid']);
					}
				}
				catch(Exception $e){
					Log::error('Error pumping fuel for distribution #'.$distro->id.': '.$e->getMessage());
					return false;
				}
			}
		}
		return false;
	}
}
