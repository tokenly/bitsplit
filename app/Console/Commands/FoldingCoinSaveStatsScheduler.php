<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\ScheduleRunCommand;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Log;

class FoldingCoinSaveStatsScheduler extends ScheduleRunCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fldc-stats-schedule:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the FoldingCoin stats processes as a separate process';


    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function __construct()
    {
        // define a custom schedule and skip the normal scheduler
        $this->schedule = new Schedule();
        $this->defineSchedule($this->schedule);

        Command::__construct();
    }

    protected function defineSchedule(Schedule $schedule) {
        // stats schedule is disabled

        // // download the stats
        // $schedule->command('bitsplit:stats')->dailyAt('07:00')->timezone('UTC');

        // // process the stats
        // $schedule->command('bitsplit:save_stats')->dailyAt('07:00')->timezone('UTC');
    
        // // run after the save stats command
        // $schedule->command('bitsplit:cache-total-unique-folders')->dailyAt('07:00')->timezone('UTC');
    }

}
