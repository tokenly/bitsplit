<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

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
        //$stats = file_get_contents('http://fah-web.stanford.edu/daily_user_summary.txt');
        $stats = file_get_contents('https://www.wikipedia.org/');
        //Storage::put('/../LOL.txt', $stats);
        Storage::disk('dailyfolders')->put('LOL.txt', $stats);
        //file_put_contents("/storage/LOL.txt", fopen(".bz2", 'r'));
    }
}
