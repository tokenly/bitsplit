<?php

namespace App\Console\Commands;

use App\Models\DailyFolder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
        $total_count = DailyFolder::where('uuid', null)->count();
        $offset = 0;

        while (true) {
            $folders = DailyFolder::where('uuid', null)->limit(2500)->get();
            if ($folders->count() == 0) {
                break;
            }

            $uuids = [];
            foreach($folders as $folder) {
                $uuid = md5($folder->username.$folder->bitcoin_address.strtotime(date('Y/m/d', strtotime($folder->date))));
                $uuids[$folder->id] = $uuid;

                ++$offset;
            }

            $this->bulkUpdateValues(DailyFolder::getModel()->getTable(), $uuids);

            if ($offset % 2500 === 0 or $offset >= $total_count) {
                $this->info("Updated $offset of $total_count folders");
            }
        }

        $this->info('done');
    }

    protected function bulkUpdateValues($table, array $values)
    {
        $cases = [];
        $ids = [];
        $params = [];

        foreach ($values as $id => $value) {
            $id = (int) $id;
            $cases[] = "WHEN {$id} then ?";
            $params[] = $value;
            $ids[] = $id;
        }

        $ids = implode(',', $ids);
        $cases = implode(' ', $cases);
        $params[] = Carbon::now();

        return DB::update("UPDATE `{$table}` SET `uuid` = CASE `id` {$cases} END, `updated_at` = ? WHERE `id` in ({$ids})", $params);
    }

}
