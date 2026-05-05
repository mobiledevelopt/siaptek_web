<?php

namespace App\Services\Attendance\Rules;

/**
 * Rule for handling alpha attendance cases. Optional
 */

class AlphaRule implements AttendanceRule
{
    public function applies($user, $absen, $config): bool
    {
        return !$absen || empty($absen->incoming_time) || $absen->incoming_time === '00:00:00';
    }

    public function process($user, $absen, $config): array
    {
        $tunjangan = $user->tpp / $config['jmlHariKerja'];
        $alpha = $config['configTpp']['alfa'];

        return [
            'status' => 'Tidak Masuk',
            'tunjangan_per_hari' => round($tunjangan),
            'total_potongan_tpp' => round($tunjangan),
            'tpp_diterima' => 0,
            'ket_tidak_masuk_kerja' => $alpha->title,
        ];
    }
}