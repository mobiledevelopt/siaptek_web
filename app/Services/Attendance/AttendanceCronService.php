<?php

namespace App\Services\Attendance;

use App\Models\Pegawai;
use Illuminate\Support\Facades\Log;

class AttendanceCronService
{
    public function run()
    {
        if (!$this->isHariKerja()) {
            Log::info('Skip cron: hari libur');
            return;
        }

        Pegawai::where('active', 1)
            ->chunk(1000, function ($users){
                foreach ($users as $user) {
                    app(AttendanceCronHandler::class)->handle($user);
                }
            });

        Log::info('Cron selesai');
    }

    protected function isHariKerja()
    {
        return now()->isWeekday() && !AttendanceCache::isLibur();
    }

}