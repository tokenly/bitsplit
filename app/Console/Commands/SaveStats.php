<?php

namespace App\Console\Commands;

use App\Models\DailyFolder;
use App\Models\FAHFolder;
use BitWasp\BitcoinLib\BitcoinLib;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SaveStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:save_stats  {date? : Optional argument to scan a specific date or range of dates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scans the current day\'s saved Folding@Home stats and saves any entries that we care about to the database.';

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
        Log::debug("Begin bitsplit:save_stats");
        if(!empty($this->argument('date'))) {
            $pre_dates = explode('-', $this->argument('date'));
            foreach ($pre_dates as $date) {
                if (\DateTime::createFromFormat('Y/m/d', $date) == false) {
                    die("Please write the date in this format: YYYY/MM/DD or YYYY/MM/DD-YYYYDMM/DD for a range of dates \n");
                }
                $datetime = \DateTime::createFromFormat('Y/m/d', $date);
                $dates[] = $datetime->format('Y/m/d');
            }
        } else {
            $dates[] = date('Y') . '/' . date( 'm'). '/'. date('d');
        }
        FAHFolder::truncate();
        foreach ($dates as $date) {
            $this->removeFoldersFromDate($date);

            Log::debug("bitsplit:save_stats begin processing $date");
            $inserted_count = 0;

            $filename = $date .'.txt';
            if(!Storage::disk('dailyfolders')->exists($filename)) {
                die("That date hasn\'t been downloaded yet \n");
            }
            $stats = storage_path('dailyfolders/' . $filename);
            $fp = fopen($stats,'r');
            $folders = array();
            $i = 0;
            $h = 0;
            while (($line = fgets($fp, 4096)) !== false) {
                echo 'Line: ' . $i . PHP_EOL;
                $i++;
                $h++;
                $data = explode("	", $line);
                if (count($data) < 2) {
                    continue;
                }
                //Skip header rows
                if($i < 3) { continue; }
                $username = $data[0];
                $newcredit = $data[1];
                $total_sum = $data[2];
                $team_number = $data[3];
                $folder = array(
                    'name' => $username,
                    'new_credit' => $newcredit,
                    'total_credit' => $total_sum,
                    'team' => $team_number
                );
                $folders[] = $folder;
                if($h >= 5000) {
                    FAHFolder::insert($folders);
                    $inserted_count += count($folders);
                    $h = 0;
                    $folders = array();
                }
                if ($team_number === 22628 && BitcoinLib::validate_address($username)) {
                    $bitcoin_address = $username;
                    $reward_token = 'FLDC';
                } else {
                    $arr = explode("_", $username);
                    if (count($arr) < 3) {
                        continue;
                    }
                    $username = $arr[0];
                    $reward_token = $arr[1];
                    $bitcoin_address = $arr[2];
                    if (!BitcoinLib::validate_address($bitcoin_address)) {
                        continue;
                    }
                }
                $daily_folder = DailyFolder::where('team', $team_number)->where('bitcoin_address', $bitcoin_address)
                    ->where('date', date("Y-m-d", strtotime($date)))
                    ->first();
                if (!$daily_folder) {
                    $daily_folder = new DailyFolder;
                }
                $daily_folder->new_credit = $newcredit;
                $daily_folder->total_credit = $total_sum;
                $daily_folder->team = $team_number;
                $daily_folder->bitcoin_address = $bitcoin_address;
                $daily_folder->reward_token = strtoupper($reward_token);
                $daily_folder->date = date("Y-m-d", strtotime($date));
                $daily_folder->username = $username;
                $daily_folder->save();
            }
            FAHFolder::insert($folders);
            $inserted_count += count($folders);
            fclose($fp);

            $folder_records_count = FAHFolder::count();
            Log::debug("bitsplit:save_stats end processing $date.  Inserted $inserted_count folders.  There are $folder_records_count records in the database.");
        }

        Log::debug("End bitsplit:save_stats");
    }

    protected function removeFoldersFromDate($date) {
        DailyFolder::where('date', $date)->delete();
    }
}
