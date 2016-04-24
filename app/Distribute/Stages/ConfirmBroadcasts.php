<?php
namespace Distribute\Stages;
use Config, UserMeta, DB, Exception, Log, Models\Distribution as Distro, Models\DistributionTx as DistroTx;

class ConfirmBroadcasts extends Stage
{
	public function init()
	{
		$distro = $this->distro;
		$address_count = $distro->addressCount();
		$complete_count = $distro->countComplete();
		if($complete_count == $address_count){
			Log::info('All transactions confirmed for distro '.$distro->id);
			$distro->incrementStage();
			return true;
		}
		else{
			Log::info('Distro '.$distro->id.' tx complete: '.$complete_count.'/'.$address_count);
			return false;
		}
	}
}
