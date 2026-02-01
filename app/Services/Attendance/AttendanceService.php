<?php

namespace App\Services\Attendance;

use App\Models\AttendancesPegawai;
use Illuminate\Database\QueryException;

class AttendanceService
{
    public function clockIn($user)
    {
        AttendanceValidator::hariKerja();

        $jadwal = AttendanceCache::jadwal();
        AttendanceValidator::jamMasuk($jadwal);

        [$menitTelat, $statusMasuk] = AttendanceCalculator::hitungTelat($jadwal);

        try {
            return AttendancesPegawai::create([
                'pegawai_id' => $user->id,
                'dinas_id' => $user->dinas_id,
                'date_attendance' => today(),
                'incoming_time' => now(),
                'menit_telat_masuk' => $menitTelat,
                'status_masuk' => $statusMasuk,
                'status' => 'Masuk'
            ]);
        } catch (QueryException $e) {
            throw new \Exception('Anda sudah presensi masuk');
        }
    }

    public function clockOut($user)
    {
        AttendanceValidator::hariKerja();

        $jadwal = AttendanceCache::jadwal();
        AttendanceValidator::jamPulang($jadwal);

        $updated = AttendancesPegawai::where([
            ['pegawai_id', $user->id],
            ['dinas_id', $user->dinas_id],
            ['date_attendance', today()],
            ['outgoing_time', null]
        ])->update([
            'outgoing_time' => now(),
            'status_pulang' => 'Pulang'
        ]);

        if ($updated === 0) {
            throw new \Exception('Belum presensi masuk atau sudah presensi pulang');
        }
    }
}
