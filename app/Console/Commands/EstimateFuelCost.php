<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Models\Fuel;

class EstimateFuelCost extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:estimateFuelCost {tx_count} {--rate=} {--dust=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate how much BTC fuel a certain sized distribution would need';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tx_count = $this->argument('tx_count');
        $rate = $this->option('rate');
        $dust = $this->option('dust');
        
        $estimate = Fuel::calculateFuel($tx_count, $rate, $dust);
        
        $this->info('Estimated cost for '.$tx_count.' distribution txs: '.number_format($estimate / 100000000, 8));
    }
}
