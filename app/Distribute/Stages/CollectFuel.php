<?php
namespace Distribute\Stages;

class CollectFuel extends Stage
{
	public function init()
	{
		parent::init();
		//check fee received, if none then look at any pending and figure out how much fuel to pump
		$distro = $this->distro;
		if($distro->fee_received >= $distro->fee_total){
			$distro->incrementStage();
			return true;		
		}
		return false;
	}
}
