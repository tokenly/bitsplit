<?php

namespace App\Console\Commands\Withdrawal;

use App\Libraries\Withdrawal\WithdrawalFeeManager;
use Illuminate\Console\Command;

class CacheFLDCQuote extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'withdrawal:cache-fldc-quote
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Loads and caches the latest FLDC quote';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(WithdrawalFeeManager $withdrawal_fee_manager)
    {
        $withdrawal_fee_manager->rebuildLatestFeeQuoteCaches();
        $this->comment("caches rebuilt.");

        $fee = $withdrawal_fee_manager->getLatestFeeQuote();
        $this->comment("Latest fee: ".formattedTokenQuantity($fee));
    }
}
