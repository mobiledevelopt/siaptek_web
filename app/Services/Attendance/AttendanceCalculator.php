<?php

namespace App\Services\Attendance;

class AttendanceCalculator
{
    public static function hitungTelat($jadwal)
    {
        $menit = max(0, floor((time() - strtotime($jadwal->jam_masuk)) / 60));

        $status = 'Masuk';

        foreach (AttendanceCache::potonganTpp() as $row) {
            if ($menit >= $row->dari_meni && $menit <= $row->sampai_menit) {
                $status = $row->title;
                break;
            }
        }

        return [$menit, $status];
    }
}
