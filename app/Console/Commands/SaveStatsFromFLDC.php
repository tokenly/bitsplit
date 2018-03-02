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
    protected $description = 'Scans the current day\'s saved Folding@Home stats from the original FLDC database and saves any entries that we care about to the new database.';

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
            
            $this->info('Processing date '.$date);
            
            $this->removeFoldersFromDate($date);
            
            $this->info('Loading old FLDC table data');
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
            
            $this->info('Processing folders for '.$date);
            
            $folders = array();
            foreach ($folders_collection as $folder) {
                if(!isset($folder->new_credit)){
                    $folder->new_credit = 0;
                }

                $previous_folder_uuid = md5(trim($folder->name).trim($folder->address).strtotime($date . ' -1 day'));
                $previous_daily_folder = DailyFolder::select('total_credit')->where('uuid', $previous_folder_uuid)->first();
                if(!$previous_daily_folder){
                    //try 2 days before
                    $previous_folder_uuid = md5(trim($folder->name).trim($folder->address).strtotime($date . ' -2 days'));
                    $previous_daily_folder = DailyFolder::select('total_credit')->where('uuid', $previous_folder_uuid)->first();
                }

                $daily_new_credit = 0;
                if(!empty($previous_daily_folder)) {
                    $daily_new_credit = $folder->totalpts - $previous_daily_folder->total_credit;
                }
                if($daily_new_credit < 0) {
                    $daily_new_credit = 0;
                }

                $folder_uuid = md5(trim($folder->name).trim($folder->address).strtotime($date));
                $folder = array(
                    'new_credit' => $daily_new_credit,
                    'total_credit' => $folder->totalpts,
                    'team' => 0,
                    'bitcoin_address' => trim($folder->address),
                    'reward_token' => strtoupper($folder->token),
                    'date'         => $date,
                    'username'     => trim($folder->name),
                    'uuid' => $folder_uuid,
                );
                $folders[] = $folder;
                $this->info('Inserting folder entry '.$folder_uuid);
            }

            $insert = DailyFolder::insert($folders);
            if(!$insert){
                $this->error('Error inserting folders for '.$date);
            }
            else{
                $this->info('Success - '.count($folders).' Folders inserted for '.$date);
            }
            $this->info('..done');
        }
    }
    protected function removeFoldersFromDate($date) {
        $this->info('Removing old entries for '.$date.'...');
        DailyFolder::where('date', $date)->delete();
    }
}
