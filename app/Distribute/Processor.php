<?php
namespace Distribute;
use Distribute\Stages as Stages;
use Exception;
use Illuminate\Support\Facades\Log;
use Models\Distribution as Distro;
use Tokenly\LaravelEventLog\Facade\EventLog;
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
	
	public function processStage($distro)
	{
		if ($distro->stage == 0){
			Log::debug("Distribution {$distro['id']} was not initialized");
			return true;
		}

		if ($distro->complete){
			Log::debug("Distribution {$distro['id']} was already complete");
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
		try {
			$success = $stage_handler->init();
			Log::debug('[End] processing stage '.$stage_name.' ('.$stage_number.') for distro #'.$distro->id);
		} catch (Exception $e) {
			EventLog::logError('processingDistribution.error', $e, [
				'distributionId' => $distro->id,
				'stage' => $stage_name,
			]);
			$success = false;
		}

		return $success;
	}
}
