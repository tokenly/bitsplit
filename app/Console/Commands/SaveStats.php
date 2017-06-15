<?php

namespace App\Console\Commands;

use App\Models\DailyFolder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use \LinusU\Bitcoin\AddressValidator;

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
        foreach ($dates as $date) {
            $filename = $date .'.txt';
            if(!Storage::disk('dailyfolders')->exists($filename)) {
                die("That date hasn\'t been downloaded yet \n");
            }
            $stats = storage_path('dailyfolders/' . $filename);
            $fp = fopen($stats,'r');
            while ( !feof($fp) ) {
                $line = fgets($fp, 2048);
                $data = str_getcsv($line, "\t");
                if (count($data) < 2) {
                    continue;
                }
                $username = $data[0];
                $newcredit = $data[1];
                $total_sum = $data[2];
                $team_number = $data[3];
                if ($team_number === 22628 && AddressValidator::isValid($username)) {
                    $bitcoin_address = $username;
                    $reward_token = 'FLDC';
                } else {
                    $arr = explode("_", $username);
                    if (count($arr) < 3) {
                        continue;
                    }
                    $name = $arr[0];
                    $reward_token = $arr[1];
                    $bitcoin_address = $arr[2];
                    if (!AddressValidator::isValid($bitcoin_address)) {
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
                $daily_folder->reward_token = $reward_token;
                $daily_folder->date = date("Y-m-d", strtotime($date));
                $daily_folder->save();
            }
        }
    }
}
