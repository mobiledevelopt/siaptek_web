<?php

namespace App\Helpers;

use App\Models\AttendancePotonganLog;

class PotonganLogger
{
    public static $buffer = [];

    /**
     * Log semua potongan dari hasil calculator.
     */
    public static function logFromCalculator($absen, $pegawai, array $calc, $bufferOnly = false)
    {
        $types = [
            'telat' => 'Telat',
            'pulang' => 'Tidak Absen Pulang',
            'apel_pagi' => 'Tidak Apel Pagi',
            'apel_sore' => 'Tidak Apel Sore',
        ];

        $now = now()->toDateTimeString();

        foreach ($types as $type => $title) {
            $nilai = $calc['potongan'][$type] ?? 0;
            $nilaiFinal = round($nilai['final'] ?? 0);

            self::$buffer[] = [
                'attendance_id' => $absen->id,
                'pegawai_id' => $pegawai->id,
                'type' => $type,
                'nilai_raw' => $nilai['raw'] ?? 0,
                'nilai_final' => $nilaiFinal,
                'persentase' => (($nilai['persen'] ?? 0) > 0) ? 100 : 0,
                'keterangan' => $title,
                'calculated_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!$bufferOnly) {
            self::flush();
        }
    }

    public static function flush()
    {
        if (empty(self::$buffer)) return;

        AttendancePotonganLog::upsert(
            self::$buffer,
            ['attendance_id', 'type'],
            ['nilai_raw', 'nilai_final', 'persentase', 'calculated_at', 'updated_at']
        );

        self::$buffer = [];
    }
}