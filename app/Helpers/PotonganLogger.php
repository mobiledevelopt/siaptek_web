<?php

namespace App\Helpers;

use App\Models\AttendancePotonganLog;

class PotonganLogger
{
    /**
     * Log semua potongan dari hasil calculator.
     */
    public static function logFromCalculator($absen, $pegawai, array $calc)
    {
        $types = [
            'telat' => 'Telat',
            'pulang' => 'Tidak Absen Pulang',
            'apel_pagi' => 'Tidak Apel Pagi',
            'apel_sore' => 'Tidak Apel Sore',
        ];

        foreach ($types as $type => $title) {
            $nilai = $calc['potongan'][$type] ?? 0;
            $nilaiFinal = round($nilai['final'] ?? 0);

            AttendancePotonganLog::updateOrCreate(
                [
                    'attendance_id' => $absen->id,
                    'type' => $type,
                ],
                [
                    'pegawai_id' => $pegawai->id,
                    'nilai_raw' => $nilai['raw'] ?? 0,
                    'nilai_final' => $nilaiFinal,
                    'persentase' => ($nilai['persen'] ?? 0 > 0) ? 100 : 0,
                    'keterangan' => $title,
                    'calculated_at' => now(),
                ]
            );
        }
    }
}