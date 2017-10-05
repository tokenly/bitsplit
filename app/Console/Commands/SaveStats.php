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
        //Validation
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
        //Actual proccess
        foreach ($dates as $date) {
            //Don't store duplicated
            $this->removeFoldersFromDate($date);
            Log::debug("bitsplit:save_stats begin processing $date");
            $inserted_count = 0;
            $filename = $date .'.txt';
            if(!Storage::disk('s3')->exists($filename)) {
                die("That date hasn\'t been downloaded yet \n");
            }
            $stats = storage_path('dailyfolders/' . $filename);
            $fp = fopen($stats,'r');
            $folders = array();
            $daily_folders = array();
            $i = 0;
            $h = 0;
            //Calculate total network credits
            $total = 0;
            while (($line = fgets($fp, 4096)) !== false) {
                echo 'Line: ' . $i . PHP_EOL;
                $i++;
                $h++;
                $data = explode("	", $line);
                if (count($data) < 2) {
                    continue;
                }
                //Skip header rows
                if ($i < 3) {
                    continue;
                }
                $total += $data[1];
            }
            rewind($fp);

            //Store daily folder
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
                $team_number = $data[3];
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
                $previous_daily_folder = $daily_folder = DailyFolder::where('team', $team_number)->where('bitcoin_address', $bitcoin_address)
                    ->where('date', date("Y-m-d", strtotime($date . ' -1 day')))
                    ->first();
                if(empty($previous_daily_folder)) {
                    $daily_new_credit = 0;
                } else {
                    $daily_new_credit =  $newcredit - $previous_daily_folder->total_credit;
                }
                $daily_folder = DailyFolder::where('team', $team_number)->where('bitcoin_address', $bitcoin_address)
                    ->where('date', date("Y-m-d", strtotime($date)))
                    ->first();
                if ($daily_folder) {
                    $daily_folder->delete();
                }
                $daily_folders[] = array(
                    'new_credit' => $daily_new_credit,
                    'total_credit' => $newcredit,
                    'team' => $team_number,
                    'bitcoin_address' => $bitcoin_address,
                    'reward_token' => strtoupper($reward_token),
                    'date' => date("Y-m-d", strtotime($date)),
                    'username' => $username,
                    'network_percentage' => ($newcredit * 100) / $total
                );
            }
            DailyFolder::insert($daily_folders);
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