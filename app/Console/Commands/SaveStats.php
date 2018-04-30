<?php
namespace App\Console\Commands;
use App\Models\DailyFolder;
use App\Models\FAHFolder;
use BitWasp\BitcoinLib\BitcoinLib;
use Exception;
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
        try {
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
                $this->info('Processing date '.$date);
                //Don't store duplicated
                $this->removeFoldersFromDate($date);
                Log::debug("bitsplit:save_stats begin processing $date");
                $inserted_count = 0;
                $filename = $date .'.txt';
                $this->info('Loading daily f@h data');
                if(!Storage::disk('s3')->exists($filename)) {
                    echo "The stats file for the date ". $date . " hasn\'t been downloaded yet \n";
                    continue;
                }
                $contents = Storage::disk('s3')->get($filename);
                $stats = storage_path('dailyfolders/' . $filename);
                Storage::disk('dailyfolders')->put($filename, $contents);
                $fp = fopen($stats,'r');
                $folders = array();
                $daily_folders = array();
                $i = 0;
                $h = 0;
                //Calculate total network credits
                $this->info('Processing f@h data');
                $total = 0;
                while (($line = fgets($fp, 4096)) !== false) {
                    //echo 'Line: ' . $i . PHP_EOL;
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
                $used_uuids = array();
                while (($line = fgets($fp, 4096)) !== false) {
                    //echo 'Line: ' . $i . PHP_EOL;
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
                        if (count($arr) < 2) {
                            continue;
                        }
                        if (count($arr) < 3) {
                            //FORMAT: username_address
                            $username = $arr[0];
                            $bitcoin_address = $arr[1];
                            $reward_token = 'FLDC';
                            if (!BitcoinLib::validate_address($bitcoin_address)) {
                                continue;
                            }
                        } else {
                            //FORMAT: username_token_address
                            $username = $arr[0];
                            $reward_token = $arr[1];
                            $bitcoin_address = $arr[2];
                            if (!BitcoinLib::validate_address($bitcoin_address)) {
                                continue;
                            }
                        }
                    }
                    
                    $previous_folder_uuid = md5($username.$bitcoin_address.strtotime($date . ' -1 day'));
                    $previous_daily_folder = DailyFolder::select('total_credit')->where('uuid', $previous_folder_uuid)->first();
                    if(!$previous_daily_folder){
                        //try 2 days before
                        $previous_folder_uuid = md5($username.$bitcoin_address.strtotime($date . ' -2 days'));
                        $previous_daily_folder = DailyFolder::select('total_credit')->where('uuid', $previous_folder_uuid)->first();
                    }
                    
                    if(empty($previous_daily_folder)) {
                        $daily_new_credit = 0;
                    } else {
                        $daily_new_credit =  $newcredit - $previous_daily_folder->total_credit;
                        if($daily_new_credit < 0) {
                            $daily_new_credit = 0;
                        }
                    }
                    
                    $this_folder_uuid = md5($username.$bitcoin_address.strtotime($date));
                    
                    $daily_folders[$this_folder_uuid] = array(
                        'new_credit' => $daily_new_credit,
                        'total_credit' => $newcredit,
                        'team' => $team_number,
                        'bitcoin_address' => $bitcoin_address,
                        'reward_token' => strtoupper($reward_token),
                        'date' => date("Y-m-d", strtotime($date)),
                        'username' => $username,
                        'network_percentage' => ($newcredit * 100) / $total,
                        'uuid' => $this_folder_uuid,
                    );
                    $this->info('Adding folder '.$this_folder_uuid);
                }
                $this->info('Inserting folders');
                DailyFolder::insert($daily_folders);
                $inserted_count += count($daily_folders);
                fclose($fp);
                Log::debug("bitsplit:save_stats end processing $date.  Inserted $inserted_count folders.");
                $this->info('Inserted '.$inserted_count.' folders into db');
            }
            Log::debug("End bitsplit:save_stats");
        } catch (Exception $e) {
            $this->info('Error: '.$e->getMessage());
            Log::error("Error (".$e->getCode().") in in bitsplit:save_stats. ".$e->getMessage());
            throw $e;
        }
        $this->info('..done');
    }
    protected function removeFoldersFromDate($date) {
        $this->info('Removing folders from date '.$date);
        DailyFolder::where('date', $date)->delete();
    }
}
