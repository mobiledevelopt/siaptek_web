<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{

    protected $commands = [
        Commands\AbsenCron::class,
        Commands\MoveCompressImage::class
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            Log::info('Cron Working');
        })->twiceDaily(20, 23);
        $schedule->command('absen:cron')->timezone('Asia/jakarta')->twiceDaily(20, 23);
        // $schedule->command('app:move-compress-image')->timezone('Asia/jakarta')->hourly()->between('8:00', '19:00');
        $schedule->command('app:move-compress-image')->timezone('Asia/jakarta')->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
