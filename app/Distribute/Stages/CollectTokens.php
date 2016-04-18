<?php
namespace Distribute\Stages;

class CollectTokens extends Stage
{
	public function init()
	{
		parent::init();
		//received tokens + fee applied to distro via webhook
		//do simple check and increment stage
		$distro = $this->distro;
		if($distro->asset_received >= $distro->asset_total){
			$distro->incrementStage();
			return true;		
		}
		return false;
	}
}
