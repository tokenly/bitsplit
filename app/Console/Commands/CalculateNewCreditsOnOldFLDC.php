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
        
        $begin = new \DateTime($this->argument('start'));
        $end = new \DateTime($this->argument('end'));

        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod($begin, $interval, $end);

        $last_points = array();
        foreach($period as $dt){
            $date = $dt->format('Y-m-d');
            try{
                $folders = $db->table($date)->get();
            }
            catch(\Exception $e){
                $folders = false;
            }
            if(!$folders){
                $this->error('No table found for '.$date);
                continue;
            }
            $date_data = array();
            foreach($folders as $folder){
                $folder->new_credit = 0;
                $key = $folder->name.'_'.$folder->token.'_'.$folder->address;
                if(isset($last_points[$key])){
                    if($last_points[$key] > $folder->totalpts){
                        continue;
                    }
                    $folder->new_credit = intval($folder->totalpts - $last_points[$key]);
                    if($folder->new_credit < 0){
                        $folder->new_credit = 0;
                    }              
                }
                if(!isset($last_points[$key]) OR $folder->totalpts > $last_points[$key]){
                    $last_points[$key] = $folder->totalpts;
                }
                $date_data[] = $folder;
                //$db->table($date)->where('id', $folder->id)->update(array('new_credit' => $folder->new_credit));
            }
            $encoded = json_encode($date_data);
            $put = Storage::disk('local')->put('old-'.$date.'.json', json_encode($date_data));
            if(!$put){
                $this->error('Error saving json for '.$date);
            }
            else{
                $this->info('JSON saved for '.$date);
            }
        }
    }
}
