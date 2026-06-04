<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
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
        $schedule->command('app:move-compress-image backfill')->timezone('Asia/jakarta')->dailyAt('02:00');

        $schedule->command('app:presensi-reminder')->everyMinute();

        // Cleanup: Hapus data > 1 bulan dari Pulse, Telescope & Personal Access Tokens (setiap hari jam 01:00 WIB)
        $schedule->call(function () {
            $oneMonthAgo = now()->subMonth();
            $oneMonthAgoTimestamp = $oneMonthAgo->timestamp;

            // Pulse (kolom timestamp berupa unix timestamp integer)
            DB::table('pulse_entries')->where('timestamp', '<', $oneMonthAgoTimestamp)->delete();
            DB::table('pulse_aggregates')->where('bucket', '<', $oneMonthAgoTimestamp)->delete();
            DB::table('pulse_values')->where('timestamp', '<', $oneMonthAgoTimestamp)->delete();

            // Telescope (kolom created_at berupa datetime)
            $oldEntryUuids = DB::table('telescope_entries')
                ->where('created_at', '<', $oneMonthAgo)
                ->pluck('uuid');

            if ($oldEntryUuids->isNotEmpty()) {
                DB::table('telescope_entries_tags')->whereIn('entry_uuid', $oldEntryUuids)->delete();
                DB::table('telescope_entries')->where('created_at', '<', $oneMonthAgo)->delete();
            }

            // Personal Access Tokens (kolom created_at)
            // DB::table('personal_access_tokens')->where('created_at', '<', $oneMonthAgo)->delete();
            DB::table('personal_access_tokens')->where('last_used_at' , '<', $oneMonthAgo)->delete();
            
            Log::info("Cleanup: data > 1 bulan dihapus dari pulse, telescope & personal_access_tokens.");
        })->timezone('Asia/Jakarta')->dailyAt('01:00')
          ->name('cleanup-pulse-telescope-tokens')
          ->withoutOverlapping();
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
