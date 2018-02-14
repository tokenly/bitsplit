<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DailyFolder;

class CacheTotalUniqueFolders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:cache-total-unique-folders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Caches the total number of unique "daily folders" for foldingcoin';

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
        $total = DailyFolder::countUniqueFolders(false);
        $this->info($total.' unique folders');
        $this->info('..done');        
    }
}
