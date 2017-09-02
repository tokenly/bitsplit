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
    protected $signature = 'bitsplit:save_stats_fldc  {date? : Optional argument to scan a specific date or range of dates}';

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
        if (!empty($this->argument('date'))) {
            $pre_dates = explode('-', $this->argument('date'));
            foreach ($pre_dates as $date) {
                if (\DateTime::createFromFormat('Y/m/d', $date) == false) {
                    die("Please write the date in this format: YYYY/MM/DD or YYYY/MM/DD-YYYYDMM/DD for a range of dates \n");
                }
                $datetime = \DateTime::createFromFormat('Y/m/d', $date);
                $dates[] = $datetime->format('Y-m-d');
            }
        } else {
            $dates[] = date('Y') . '-' . date('m') . '-' . date('d');
        }
        $repeat_folders = array();
        foreach ($dates as $date) {
            $this->removeFoldersFromDate($date);

            $folders_collection = DB::connection('fldc')->table($date)->get();
            $folders = array();
            foreach ($folders_collection as $folder) {;
                $data = json_decode(json_encode($folder), true);


                $folder = array(
                    'new_credit' => $data['new_credit'],
                    'total_credit' => $data['totalpts'],
                    'team' => 0,
                    'bitcoin_address' => $data['address'],
                    'reward_token' => strtoupper($data['token']),
                    'date'         => date("Y-m-d", strtotime($date)),
                    'username'     => $data['name']
                );
                $folders[] = $folder;
            }

            DailyFolder::insert($folders);
        }
    }
    protected function removeFoldersFromDate($date) {
        DailyFolder::where('date', $date)->delete();
    }
}
