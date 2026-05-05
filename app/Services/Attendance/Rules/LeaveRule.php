<?php

namespace App\Services\Attendance\Rules;

use Illuminate\Support\Facades\DB;

class LeaveRule implements AttendanceRule
{
    public function applies($user, $absen, $config): bool
    {
        return DB::table('leaves')
            ->where('pegawai_id', $user->id)
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->where('status', 'approved')
            ->exists();
    }

    public function process($user, $absen, $config): array
    {
        return [
            'status' => 'Cuti',
            'tunjangan_per_hari' => 0,
            'total_potongan_tpp' => 0,
            'tpp_diterima' => 0,
        ];
    }
}