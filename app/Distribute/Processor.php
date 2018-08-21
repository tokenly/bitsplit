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
		$stage_name = $distro->stageName();
		if(!$stage_name){
			return false;
		}

		$stage_number = $distro->stage;
		Log::debug('[Begin] processing stage '.$stage_name.' ('.$stage_number.') for distro #'.$distro->id);
		$stage_class = $distro->stageClass();
		$stage_handler = new $stage_class($distro);
		$init = $stage_handler->init();
		Log::debug('[End] processing stage '.$stage_name.' ('.$stage_number.') for distro #'.$distro->id);

		return true;
	}
}
