<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DownloadFoldingStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads and saves the latest Folding@Home public stats';

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

    }
}
