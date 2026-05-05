<?php

namespace App\Services\Attendance\Rules;

use Illuminate\Support\Facades\DB;

class PermissionRule implements AttendanceRule
{
    public function applies($user, $absen, $config): bool
    {
        return DB::table('permissions')
            ->where('pegawai_id', $user->id)
            ->whereDate('date', today())
            ->where('status', 'approved')
            ->exists();
    }

    public function process($user, $absen, $config): array
    {
        $tunjangan = $user->tpp / $config['jmlHariKerja'];
        $potongan = $tunjangan * 0.5; // contoh: potong 50%

        return [
            'status' => 'Izin',
            'tunjangan_per_hari' => round($tunjangan),
            'total_potongan_tpp' => round($potongan),
            'tpp_diterima' => round($tunjangan - $potongan),
        ];
    }
}