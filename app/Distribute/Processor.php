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
		Log::info('---- [Begin] Distro Processing '.timestamp().' ----');
		foreach($get as $k => $row){
			$this->processStage($row);
		}
		Log::info('---- [End] Distro Processing '.timestamp().' ----');
		return true;
	}
	
	protected function processStage($distro)
	{
		if($distro->stage == 0){
			//$this->initializer->init($distro);
			return true;
		}
		$stage = $distro->stageName();
		if(!$stage){
			return false;
		}

		$stage_number = $distro->stage;
		Log::debug('[Begin] processing stage '.$stage_number.' for distro #'.$distro->id);
		$stage = '\\Distribute\\Stages\\'.$stage;
		$stage_handler = new $stage($distro);
		$init = $stage_handler->init();
		Log::debug('[End] processing stage '.$stage_number.' for distro #'.$distro->id);

		return true;
	}
}
