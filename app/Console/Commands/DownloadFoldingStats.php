<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Log;
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
        Log::debug("Begin downloading daily user summary in bitsplit:stats");
        $stats = file_get_contents('http://fah-web.stanford.edu/daily_user_summary.txt');
        $filename = date('Y') . '/' . date( 'm'). '/'. date('d') .'.txt';
        Storage::disk('s3')->put($filename, $stats);
        Log::debug("End downloading daily user summary in bitsplit:stats");
    }
}
