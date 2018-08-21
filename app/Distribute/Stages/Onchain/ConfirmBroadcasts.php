<?php

namespace App\Distribute\Stages\Onchain;

use App\Distribute\Stages\Stage;
use Config, UserMeta, DB, Exception, Log, Models\Distribution as Distro, Models\DistributionTx as DistroTx;
use Tokenly\LaravelEventLog\Facade\EventLog;

class ConfirmBroadcasts extends Stage
{
	public function init()
	{
		$distro = $this->distro;
		$address_count = $distro->addressCount();
		$complete_count = $distro->countComplete();
		if($complete_count == $address_count){
			$this->goToNextStage($distro);
			return true;
		}
		else{
			EventLog::debug('distribution.confirming', [
			    'distributionId' => $distro->id,
			    'completedCount' => $complete_count,
			    'totalCount' => $address_count,
			]);
			return false;
		}
	}

	protected function goToNextStage($distribution)
	{
		EventLog::info('distribution.stageComplete', [
		    'distributionId' => $distribution->id,
		    'stage' => 'ConfirmBroadcasts',
		]);

		$distribution->incrementStage();
        $distribution->sendWebhookUpdateNotification();
		return true;
	}

}
