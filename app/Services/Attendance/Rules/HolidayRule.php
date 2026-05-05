<?php

namespace App\Services\Attendance\Rules;

use Illuminate\Support\Facades\DB;

class HolidayRule implements AttendanceRule
{
    public function applies($user, $absen, $config): bool
    {
        return now()->isWeekend() ||
            DB::table('holidays')
                ->whereDate('date', today())
                ->exists();
    }

    public function process($user, $absen, $config): array
    {
        return [
            'status' => 'Libur',
            'tunjangan_per_hari' => 0,
            'total_potongan_tpp' => 0,
            'tpp_diterima' => 0,
        ];
    }
}