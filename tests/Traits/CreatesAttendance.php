<?php

namespace Tests\Traits;

use App\Models\AttendancesPegawai;
use Carbon\Carbon;

trait CreatesAttendance
{
    /**
     * Helper untuk buat absen cepat.
     */
    public function createAttendance($pegawai, array $data = [])
    {
        return AttendancesPegawai::create([
            'pegawai_id' => $pegawai->id,
            'date_attendance' => $data['date_attendance'] ?? today(),
            'incoming_time' => $data['incoming_time'] ?? '08:00:00',
            'outgoing_time' => $data['outgoing_time'] ?? '17:00:00',
            'apel_pagi_at' => $data['apel_pagi_at'] ?? Carbon::now(),
            'apel_sore_at' => $data['apel_sore_at'] ?? Carbon::now(),
            'status' => $data['status'] ?? 'Masuk',
        ]);
    }
}