<?php

namespace App\Services\Attendance;

use App\Models\KalendarLibur;

class AttendanceValidator
{
    public static function hariKerja()
    {
        if (in_array(date('w'), [0, 6])) {
            throw new \Exception('Hari Libur Presensi');
        }

        if (KalendarLibur::whereDate('tgl', today())->exists()) {
            throw new \Exception('Hari Ini Libur Presensi');
        }
    }

    public static function jamMasuk($jadwal)
    {
        $now = time();

        if ($now < strtotime($jadwal->min_masuk)) {
            throw new \Exception('Belum waktu presensi masuk');
        }

        if ($now > strtotime($jadwal->max_masuk)) {
            throw new \Exception('Melebihi batas jam masuk');
        }
    }

    public static function jamPulang($jadwal)
    {
        $now = time();

        if ($now < strtotime($jadwal->min_pulang)) {
            throw new \Exception('Belum waktu presensi pulang');
        }

        if ($now > strtotime($jadwal->max_pulang)) {
            throw new \Exception('Melebihi batas jam pulang');
        }
    }
}
