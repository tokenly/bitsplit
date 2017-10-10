<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DailyFolder;
use App\Models\FAHFolder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use BitWasp\BitcoinLib\BitcoinLib;

class SaveStatsFromFLDC extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:save_stats_fldc  {date? : Optional to scan a specific date} {end? : end date range for multi import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scans the current day\'s saved Folding@Home stats from the FLDC database and saves any entries that we care about to the database.';

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

        $db = DB::connection('fldc');

        $opt_date = $this->argument('date');
        $opt_end = $this->argument('end');
        $date_range = array();
        if(!empty($opt_date) AND !empty($opt_end)){
            //date range
            $start = new \DateTime($opt_date);
            $end = new \DateTime($opt_end);
            $interval = \DateInterval::createFromDateString('1 day');
            $date_range = new \DatePeriod($start, $interval, $end);
        }
        elseif(!empty($opt_date)){
            $date_range[] = new \DateTime($opt_date);
        }
        else{
            //use current date
            $date_range[] = new \DateTime(date('Y-m-d'));
        }
        
        foreach ($date_range as $dt) {
            $date = $dt->format('Y-m-d');
            $table_name = env('FLDC_DB_DATABASE').'.'.$date;
            
            $this->removeFoldersFromDate($date);
            
            try{
                $folders_collection = $db->table($table_name)->get();
            }
            catch(\Exception $e){
                //$this->error('Error loading '.$date.': '.$e->getMessage());
                $folders_collection = false;
            }
            
            if(!$folders_collection){
                $this->error('Could not load '.$date);
                continue;
            }
            
            $folders = array();
            foreach ($folders_collection as $folder) {
                if(!isset($folder->new_credit)){
                    $folder->new_credit = 0;
                }
                $folder = array(
                    'new_credit' => $folder->new_credit,
                    'total_credit' => $folder->totalpts,
                    'team' => 0,
                    'bitcoin_address' => trim($folder->address),
                    'reward_token' => strtoupper($folder->token),
                    'date'         => $date,
                    'username'     => trim($folder->name)
                );
                $folders[] = $folder;
            }

            $insert = DailyFolder::insert($folders);
            if(!$insert){
                $this->error('Error inserting folders for '.$date);
            }
            else{
                $this->info('Success - Folders inserted for '.$date);
            }
        }
    }
    protected function removeFoldersFromDate($date) {
        $this->info('Removing old entries for '.$date.'...');
        DailyFolder::where('date', $date)->delete();
    }
}
