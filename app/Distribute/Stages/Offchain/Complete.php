<?php

namespace App\Distribute\Stages\Offchain;

use App\Distribute\Stages\Stage;
use Models\Distribution;
use Tokenly\LaravelEventLog\Facade\EventLog;

class Complete extends Stage
{
    public function init()
    {
        parent::init();

        $distribution = $this->distro;
        return $this->completeDistribution($distribution);
    }

    protected function completeDistribution(Distribution $distribution)
    {
        EventLog::info('distribution.offchain.stageComplete', [
            'distributionId' => $distribution->id,
            'stage' => 'Complete',
        ]);

        $distribution->markComplete();
        return true;
    }
}
