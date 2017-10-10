<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CalculateNewCreditsOnOldFLDC extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:calculateNewCreditsOnOldFLDC {start} {end}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Goes through the old FLDC database and calculates a "new_credit" field';

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
        
        $begin = new \DateTime($this->argument('start').' - 1 day');
        $end = new \DateTime($this->argument('end'));

        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod($begin, $interval, $end);

        $last_points = array();
        foreach($period as $dt){
            $date = $dt->format('Y-m-d');
            $table_name = env('FLDC_DB_DATABASE').'.'.$date;
            try{
                $folders = $db->table($table_name)->get();
            }
            catch(\Exception $e){
                $folders = false;
            }
            if(!$folders){
                $this->error('No table found for '.$table_name);
                continue;
            }
            
            //first, combine points for duplicate entries
            $dupe_folders = array();
            $delete_dupes = array();
            foreach($folders as $folder){
                $key = $folder->name.'_'.$folder->token.'_'.$folder->address;
                if($folder->totalpts <= 0){
                    $delete_dupes[] = $folder->id;
                    continue;
                }
                if(!isset($dupe_folders[$key])){
                    $dupe_folders[$key] = $folder;
                }
                else{
                    $dupe_folders[$key]->totalpts += $folder->totalpts;
                    $delete_dupes[] = $folder->id; //we don't want these duplicates, messing things up..
                }
            }
            
            //set the base 'last_points' array
            foreach($dupe_folders as $folder){
                $key = $folder->name.'_'.$folder->token.'_'.$folder->address; 
                if(!isset($last_points[$key])){
                    $last_points[$key] = 0;
                }
            }
            
            
            //now loop through the combined entries
            foreach($dupe_folders as $folder){
                $key = $folder->name.'_'.$folder->token.'_'.$folder->address;                
                $folder->new_credit = intval($folder->totalpts - $last_points[$key]);
                if($folder->new_credit <= 0){
                    $this->info('Zero credit for folder '.$folder->id.' '.$date);
                    continue;
                }              
                $last_points[$key] = $folder->totalpts;
                
                try{
                    $save = $db->table($table_name)->where('id', $folder->id)->update(array('new_credit' => $folder->new_credit));
                    $this->info('Saved new_credit to database for folder '.$folder->id.' '.$date.' credit: '.$folder->new_credit);
                }
                catch(\Exception $e){
                    $this->error('Error saving new_credit to database for folder '.$folder->id.' '.$date.' credit: '.$folder->new_credit);
                }
            }
            
            //delete dupe and 0 point entries
            if(count($delete_dupes) > 0){
                $delete = $db->table($table_name)->whereIn('id', $delete_dupes)->delete();
                if(!$delete){
                    $this->error('Error deleting duplicate entries for '.$table_name);
                }
                else{
                    $this->info('Removed duplicate entries for '.$table_name);
                }
            }
            
            $this->info('Finished parsing date '.$date);
        }
    }
}
