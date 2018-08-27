<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Tokenly\LaravelEventLog\Facade\EventLog;

class TopUpFeeReserves implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(FeeRecoveryManager $fee_recovery_manager)
    {
        if ($fee_recovery_manager->feeReservesAreAdequate()) {
            EventLog::debug('feeReserves.ok', []);
            return;
        }

        EventLog::debug('feeReserves.purchase', []);
        $fee_recovery_manager->purchaseFeeReserves();
    }

}
