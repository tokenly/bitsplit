<?php

namespace App\Console\Commands\FeeRecovery;

use App\Libraries\FeeRecovery\BittrexSeller;
use App\Libraries\FeeRecovery\FeeRecoveryManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Tokenly\CryptoQuantity\CryptoQuantity;
use Tokenly\LaravelEventLog\Facade\EventLog;

class ProcessFeeRecovery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fee:process-recovery
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks fee ledger and purchases BTC as necessary';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(FeeRecoveryManager $fee_recovery_manager)
    {
        Log::debug("checking fee recovery satus");
        if ($fee_recovery_manager->feeReservesAreAdequate()) {
            EventLog::debug('feeReserves.adequate', []);
            return;
        }

        Log::debug("begin topping up BTC fees");
        $fee_recovery_manager->purchaseFeeReserves();
        Log::debug("finished topping up BTC fees");

        $this->comment("done.");
    }
}
