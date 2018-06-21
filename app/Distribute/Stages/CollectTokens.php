<?php
namespace Distribute\Stages;
use Log;
use Tokenly\LaravelEventLog\Facade\EventLog;
class CollectTokens extends Stage
{
	public function init()
	{
		parent::init();
		//received tokens + fee applied to distro via webhook
		//do simple check and increment stage
		$distro = $this->distro;
		if($distro->asset_received >= $distro->asset_total){
			$distro->setMessage(); //clear message
			$this->goToNextStage($distro);
			return true;		
		}
		return false;
	}

	protected function goToNextStage($distribution)
	{
		EventLog::info('distribution.stageComplete', [
		    'distributionId' => $distribution->id,
		    'stage' => 'CollectTokens',
		]);

		$distribution->incrementStage();
        $distribution->sendWebhookUpdateNotification();

		return true;
	}

}
