<?php

namespace App\Distribute\Stages\Offchain;

use Log;
use Models\Distribution;
use Tokenly\LaravelEventLog\Facade\EventLog;

class Initialize
{
    public function init(Distribution $distribution)
    {

        if ($distribution->stage > 0) {
            Log::debug("cannot initialize distribution because it was already in stage " . $distribution->stageName());
            return false;
        }

        $distribution->stage = 1;
        $distribution->save();

        EventLog::info('distribution.offchain.initialize', [
            'distributionId' => $distribution->id,
        ]);

        return true;
    }

}
