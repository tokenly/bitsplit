<?php
namespace Distribute;
use Models\Distribution as Distro;
use Log;
use Distribute\Stages as Stages;
class Processor
{
	function __construct()
	{
		$this->initializer = new Initialize;
	}
	
	public function processDistributions()
	{
		$get = Distro::where('complete', 0)->where('hold', 0)->get();
		if(!$get OR count($get) == 0){
			//nothing to do
			return false;
		}
		Log::info('----Start Distro Processing '.timestamp().' ----');
		foreach($get as $k => $row){
			$this->processStage($row);
		}
		Log::info('----End----');
		return true;
	}
	
	protected function processStage($distro)
	{
		Log::info('Processing stage '.$distro->stage.' for distro #'.$distro->id);
		if($distro->stage == 0){
			$this->initializer->init($distro);
			return true;
		}
		$stage = $distro->stageName();
		if(!$stage){
			return false;
		}
		$stage = '\\Distribute\\Stages\\'.$stage;
		$load = new $stage($distro);
		$init = $load->init();
		return true;
	}
}
