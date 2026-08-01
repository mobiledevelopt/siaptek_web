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

        $dateStr = today()->format('Y-m-d');
        $globalConfig = [
            'jmlHariKerja' => AttendanceCache::jmlHariKerja($dateStr),
            'configTpp' => AttendanceCache::potonganTpp()->keyBy('group')
        ];

        Pegawai::where('active', 1)
            ->chunk(1000, function ($users) use ($dateStr, $globalConfig) {
                $attendances = \App\Models\AttendancesPegawai::whereIn('pegawai_id', $users->pluck('id'))
                    ->whereIn('date_attendance', [$dateStr, $dateStr . ' 00:00:00'])
                    ->get()
                    ->keyBy('pegawai_id');

                \Illuminate\Support\Facades\DB::transaction(function () use ($users, $attendances, $globalConfig) {
                    \App\Helpers\PotonganLogger::$buffer = []; // Clear buffer for this chunk
                    foreach ($users as $user) {
                        try {
                            app(AttendanceCronHandler::class)->handle($user, $attendances[$user->id] ?? null, true, $globalConfig);
                        } catch (\Throwable $e) {
                            Log::error("AbsenCron Error for Pegawai {$user->id}: " . $e->getMessage());
                        }
                    }
                    \App\Helpers\PotonganLogger::flush(); // Bulk upsert logs
                });
            });

        Log::info('Cron selesai');
    }

    protected function isHariKerja()
    {
        return now()->isWeekday() && !AttendanceCache::isLibur();
    }

}