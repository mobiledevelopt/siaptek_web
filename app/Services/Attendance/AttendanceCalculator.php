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
        
        $status = 'Masuk';
        $persenPotong = 0;

        foreach ($levelTelat as $row) {

            if ($menit >= $row->dari_meni && $menit <= $row->sampai_menit) {
                $status = $row->title; // Telat 1/2/3/4
                $persenPotong = $row->persentase_potongan;
                break;
            }
        }

        // Hitung tunjangan harian
        $tunjanganPerHari = $jmlHariKerja > 0 ? $tpp / $jmlHariKerja : 0;

        // Total potongan = (40% * persentase telat)
        $totalPotongan = (int)(($tunjanganPerHari * 40 / 100) * ($persenPotong / 100));

        $tppDiterima = (int)($tunjanganPerHari - $totalPotongan);

        return [$menit, $status, $totalPotongan, $tppDiterima];

    }
}
