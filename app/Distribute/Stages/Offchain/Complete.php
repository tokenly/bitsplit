<?php

namespace App\Distribute\Stages\Offchain;

use App\Distribute\Stages\Stage;
use Tokenly\LaravelEventLog\Facade\EventLog;

class Complete extends Stage
{
    public function init()
    {
        parent::init();
    }

    protected function completeDistribution($distribution)
    {
        EventLog::info('distribution.offchain.stageComplete', [
            'distributionId' => $distribution->id,
            'stage' => 'Complete',
        ]);

        $distribution->markComplete();
        return true;
    }
}
