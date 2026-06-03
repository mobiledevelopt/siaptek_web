<?php

namespace App\Services\Attendance;

use Carbon\Carbon;

class AttendanceCalculator
{
    public static function hitungTelat($jadwal, float $tpp, int $jmlHariKerja, $levelTelat)
    {
        $now = Carbon::now();
        $jamMasuk = $now->copy()->setTimeFromTimeString($jadwal->jam_masuk); // pakai tanggal sekarang
        $menit = $now->gt($jamMasuk) ? $now->diffInMinutes($jamMasuk) : 0;
        $maxLevel = $levelTelat->sortByDesc(function ($row) {
            return (int) $row->sampai_menit;
        })->first();
        $maxLateness = $maxLevel ? (int) $maxLevel->sampai_menit : 120;

        if ($menit > $maxLateness) {
            throw new \App\Exceptions\ApiException(
                'Anda melebihi batas maksimal keterlambatan (' . $maxLateness . ' menit) dan dianggap tidak masuk kerja',
                422,
                \App\Constants\ErrorCode::TOO_LATE
            );
        }

        $status = 'Masuk';
        $persenPotong = 0;
        $configPotTppId = null;

        $match = $levelTelat->first(function ($row) use ($menit) {
            return $menit >= (int) $row->dari_meni && $menit <= (int) $row->sampai_menit;
        });

        if ($match) {
            $status = $match->title;
            $persenPotong = $match->persentase_potongan;
            $configPotTppId = $match->id;
        }

        // Hitung tunjangan harian
        $tunjanganPerHari = $jmlHariKerja > 0 ? $tpp / $jmlHariKerja : 0;

        // Total potongan = (40% * persentase telat)
        $totalPotongan = (int) round(($tunjanganPerHari * 40 / 100) * ($persenPotong / 100));

        $tppDiterima = (int) round($tunjanganPerHari - $totalPotongan);

        return [$menit, $status, $totalPotongan, $tppDiterima, $persenPotong, $configPotTppId];

    }
}
