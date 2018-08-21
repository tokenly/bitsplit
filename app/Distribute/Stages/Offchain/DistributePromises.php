<?php

namespace App\Distribute\Stages\Offchain;

use App\Distribute\Stages\Stage;
use Tokenly\LaravelEventLog\Facade\EventLog;

class DistributePromises extends Stage
{
    public function init()
    {
        parent::init();
    }

    protected function goToNextStage($distribution)
    {
        EventLog::info('distribution.offchain.stageComplete', [
            'distributionId' => $distribution->id,
            'stage' => 'DistributePromises',
        ]);

        $distribution->incrementStage();
        $distribution->sendWebhookUpdateNotification();

        return true;
    }

}
