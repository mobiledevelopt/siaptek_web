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
    protected $description = 'Run attendance cron, optionally for a specific date (Y-m-d)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->argument('date');
        
        if ($date) {
            Log::info("run cron manual for date: " . $date);
            \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse($date));
        } else {
            Log::info("run cron today");
        }

        app(\App\Services\Attendance\AttendanceCronService::class)->run();
    }
}
