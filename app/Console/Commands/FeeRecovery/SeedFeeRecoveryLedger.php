<?php

namespace App\Console\Commands\FeeRecovery;

use App\Models\FeeRecoveryLedgerEntry;
use App\Repositories\FeeRecoveryLedgerEntryRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Tokenly\CryptoQuantity\CryptoQuantity;

class SeedFeeRecoveryLedger extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fee:seed-ledger
        {amount : amount of BTC to add to the ledger }
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manuall adds fee BTC to the ledger';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(FeeRecoveryLedgerEntryRepository $fee_recovery_ledger)
    {
        $amount = $this->argument('amount');
        Log::debug("adding $amount to the ledger");

        $fee_recovery_ledger->credit(CryptoQuantity::fromFloat($amount), 'BTC', FeeRecoveryLedgerEntry::TYPE_DEPOSIT);

        $this->comment("done.");
    }
}
