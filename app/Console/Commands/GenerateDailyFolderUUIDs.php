<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DailyFolder;

class GenerateDailyFolderUUIDs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:generate-daily-folder-uuids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Loops through all daily_folder entries with null UUID and generates a new UUID';

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
        $folders = DailyFolder::where('uuid', null)->get();
        foreach($folders as $folder){
            $folder->uuid = md5($folder->team.$folder->username.$folder->bitcoin_address.strtotime(date('Y/m/d', strtotime($folder->date))));
            $folder->save();
            $this->info($folder->id.' updated');
        }
        $this->info('..done');
    }
}
