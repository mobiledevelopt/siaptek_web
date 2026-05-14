<?php

namespace App\Services\Attendance;

use App\Models\AttendancesPegawai;

class AttendanceRepository
{
    public static function today($pegawaiId)
    {
        return AttendancesPegawai::where('pegawai_id', $pegawaiId)
            ->whereDate('date_attendance', today())
            ->first();
    }

    public static function fromDate($pegawaiId, $date)
    {
        return AttendancesPegawai::where('pegawai_id', $pegawaiId)
            ->whereDate('date_attendance', $date)
            ->first();
    }

    public static function createClockIn($user, $jadwal, $telat, $status, $potonganTpp = 0, $tppDiterima = 0)
    {
        return AttendancesPegawai::create([
            'pegawai_id' => $user->id,
            'dinas_id' => $user->dinas_id,
            'date_attendance' => today(),
            'incoming_time' => now(),
            'menit_telat_masuk' => $telat,
            'status_masuk' => $status,
            'status' => 'Masuk',
            'potongan_absen_masuk' => $potonganTpp,
            'tpp_diterima' => $tppDiterima,
            'total_potongan_tpp' => $potonganTpp,
        ]);
    }
}