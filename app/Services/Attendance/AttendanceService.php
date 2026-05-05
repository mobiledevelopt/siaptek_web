<?php

namespace App\Services\Attendance;

use App\Constants\ErrorCode;
use App\Exceptions\ApiException;
use App\Models\AttendancesPegawai;
use Illuminate\Database\QueryException;

class AttendanceService
{
    public function clockIn($user)
    {

        $absen = AttendanceRepository::today($user->id);

        if ($absen) {
            throw new ApiException('Anda sudah presensi masuk', 422, ErrorCode::ALREADY_CLOCKED_IN);
        }

        AttendanceValidator::hariKerja();

        [$jadwal, $jmlHariKerja, $potonganTpp] = AttendanceValidator::validateConfig($user, 'masuk');

        AttendanceValidator::jamMasuk($jadwal);

        $potonganTppConfig = AttendanceCache::potonganTpp()
            ->where('group', 'masuk');

        [$telat, $status, $potonganTpp, $tppDiterima] = AttendanceCalculator::hitungTelat(
            $jadwal,
            $user->tpp,
            $jmlHariKerja,
            $potonganTppConfig
        );

        return AttendanceRepository::createClockIn(
            $user,
            $jadwal,
            $telat,
            $status,
            $potonganTpp,
            $tppDiterima
        );
    }

    public function clockOut($user)
    {

        $absen = AttendanceRepository::today($user->id);

        if (!$absen) {
            throw new ApiException(
                'Anda belum presensi masuk',
                422,
                ErrorCode::NOT_CLOCKED_IN
            );
        }

        if ($absen->outgoing_time) {
            throw new ApiException(
                'Anda sudah presensi pulang',
                422,
                ErrorCode::ALREADY_CLOCKED_OUT
            );
        }

        AttendanceValidator::hariKerja();

        [$jadwal, $jmlHariKerja, $potonganTpp] = AttendanceValidator::validateConfig($user, 'pulang');

        AttendanceValidator::jamPulang($jadwal);
        $absen->update([
            'outgoing_time' => now(),
            'status_pulang' => 'Pulang'
        ]);

        return $absen->fresh();

        // $updated = AttendancesPegawai::where([
        //     ['pegawai_id', $user->id],
        //     ['dinas_id', $user->dinas_id],
        //     ['date_attendance', today()],
        //     ['outgoing_time', null]
        // ])->update([
        //     'outgoing_time' => now(),
        //     'status_pulang' => 'Pulang'
        // ]);

        // if ($updated === 0) {
        //     throw new \Exception('Belum presensi masuk atau sudah presensi pulang');
        // }
    }

    public function apel($user, $type = 'pagi')
    {

        AttendanceValidator::hariKerja();

        $jadwalApel = AttendanceCache::jadwalApel(
            $user->dinas_id,
            now()->dayOfWeekIso
        );

        AttendanceValidator::apel($jadwalApel, $type);

        $absen = AttendanceRepository::today($user->id);

        if (!$absen) {
            throw new ApiException(
                'Anda belum presensi masuk',
                422,
                ErrorCode::NOT_CLOCKED_IN
            );
        }

        // mapping field
        $field = $type === 'pagi' ? 'status_apel_pagi' : 'status_apel_sore';

        if (!empty($absen->$field)) {
            throw new ApiException(
                'Anda sudah presensi apel ' . $type,
                422,
                ErrorCode::APEL_ALREADY
            );
        }

        $fieldStatus = "status_apel_{$type}";
        $fieldPotongan = "potongan_tidak_apel_{$type}";
        $fieldPersen = "potongan_tidak_apel_{$type}_persen";

        $absen->update([
            'status_apel' => 'Hadir',
            'potongan_tidak_apel' => 0,
            'potongan_tidak_apel_persen' => 0,
            'apel_pagi_at' => $type === 'pagi' ? now() : $absen->apel_pagi_at,
            'apel_sore_at' => $type === 'sore' ? now() : $absen->apel_sore_at,
            $fieldStatus => 'Hadir',
            $fieldPotongan => 0,
            $fieldPersen => 0,
        ]);

        return $absen->fresh();
    }
}
