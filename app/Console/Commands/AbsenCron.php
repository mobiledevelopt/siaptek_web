<?php

namespace App\Console\Commands;

use App\Models\Apel;
use App\Models\AttendancesPegawai;
use App\Models\ConfigPotTpp;
use App\Models\JadwalApel;
use App\Models\JamAbsen;
use App\Models\Jml_hari_kerja;
use App\Models\KalendarLibur;
use App\Models\Pegawai;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AbsenCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'absen:cron {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run attendance cron, optionally for a specific date (Y-m-d) or a whole month (Y-m)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->argument('date');
        
        if ($date) {
            // Jika format YYYY-MM (misal: 2026-07), jalankan backfill sebulan penuh
            if (preg_match('/^\d{4}-\d{2}$/', $date)) {
                $this->handleMonthBackfill($date);
                return;
            }

            Log::info("run cron manual for date: " . $date);
            \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse($date));
        } else {
            Log::info("run cron today");
        }

        app(\App\Services\Attendance\AttendanceCronService::class)->run();
        
        \Carbon\Carbon::setTestNow(null);
    }

    protected function handleMonthBackfill($yearMonth)
    {
        $this->info("Memulai backfill absensi untuk bulan {$yearMonth}...");
        Log::info("run cron manual for month: " . $yearMonth);

        $startDate = \Carbon\Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        if ($endDate->isFuture()) {
            $endDate = now()->startOfDay();
        }

        $period = \Carbon\CarbonPeriod::create($startDate, '1 day', $endDate);
        
        $count = 0;
        foreach ($period as $dt) {
            if ($dt->isWeekday()) {
                $dateString = $dt->format('Y-m-d');
                $this->info("Menjalankan cron untuk tanggal: {$dateString}");
                
                \Carbon\Carbon::setTestNow($dt);
                app(\App\Services\Attendance\AttendanceCronService::class)->run();
                
                $count++;
            }
        }
        
        \Carbon\Carbon::setTestNow(null);
        $this->info("Backfill selesai! Total {$count} hari kerja telah diproses ulang.");
    }
}
