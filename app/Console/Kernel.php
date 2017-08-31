<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
         Commands\Distribute::class,
         Commands\PumpFuel::class,
         Commands\RefreshFuelBalances::class,
         Commands\NewAddress::class,
         Commands\ListDistros::class,
         Commands\MarkComplete::class,
         Commands\GetDistro::class,
         Commands\GetDistroTxList::class,
         Commands\DeleteDistro::class,
         Commands\DeleteDistroTx::class,
         Commands\ResetDistroMonitor::class,
         Commands\UpdateDistro::class,
         Commands\UpdateDistroTx::class,
         Commands\ListUsers::class,
         Commands\GetUser::class,
         Commands\SetAdmin::class,
         Commands\ShowBalances::class,
         Commands\ResendWebhook::class,
         Commands\CloneDistro::class,
        Commands\EstimateFuelCost::class,
        Commands\DownloadFoldingStats::class,
        Commands\SaveStats::class,

        // vendor commands
        \Tokenly\ConsulHealthDaemon\Console\ConsulHealthMonitorCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
         $schedule->command('bitsplit:distribute')->everyMinute();
         $schedule->command('bitsplit:stats')->dailyAt('03:00')->timezone('EST');
         $schedule->command('bitsplit:save_stats')->dailyAt('03:00')->timezone('EST');
    }
}
