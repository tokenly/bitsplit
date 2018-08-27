<?php

namespace App\Console\Commands\Development;

use App\Libraries\FeeRecovery\BittrexSeller;
use Illuminate\Console\Command;
use Tokenly\CryptoQuantity\CryptoQuantity;

class PurchaseBTC extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:purchase-btc
        {amount : amount of BTC to purchase as a float }
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purchases BTC from Bittrex';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $amount = $this->argument('amount');

        $msg = "Purchasing $amount BTC at Bittrex";
        $this->info($msg);

        $bittrex_seller = app(BittrexSeller::class);
        $result = $bittrex_seller->purchaseBTC(CryptoQuantity::fromFloat($amount));

        $this->info(json_encode($result, 192));

        $this->comment("done.");
    }
}
