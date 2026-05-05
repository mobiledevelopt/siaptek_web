<?php

namespace App\Services\Attendance;

use App\Constants\ErrorCode;
use App\Exceptions\ApiException;
use App\Models\KalendarLibur;
use Carbon\Carbon;

class AttendanceValidator
{
    public static function hariKerja()
    {
        $today = Carbon::today();

        if ($today->isWeekend()) {
            throw new ApiException('Hari Ini Libur Presensi', 422, ErrorCode::WEEKEND);
        }

        $isLibur = cache()->remember(
            'libur_' . $today->toDateString(),
            3600,
            fn() => KalendarLibur::whereDate('tgl', $today)->exists()
        );

        if ($isLibur) {
            throw new ApiException('Tidak ada jadwal presensi hari ini', 422, ErrorCode::HOLIDAY);
        }
    }

    public static function jamMasuk($jadwal)
    {
        if (!$jadwal) {
            throw new ApiException('Jadwal tidak ditemukan', 422, ErrorCode::SCHEDULE_NOT_FOUND);
        }

        $now = Carbon::now();

        $min = Carbon::today()->setTimeFromTimeString($jadwal->min_masuk);
        $max = Carbon::today()->setTimeFromTimeString($jadwal->max_masuk);

        if ($now->lt($min)) {
            throw new ApiException('Minimal Jam Presensi Masuk ' . $min->format('H:i'),422,ErrorCode::TOO_EARLY);
        }

        if ($now->gt($max)) {
            throw new ApiException('Melebihi batas jam masuk',422,ErrorCode::TOO_LATE);
        }
    }

    public static function jamPulang($jadwal)
    {

        if (!$jadwal) {
            throw new ApiException('Jadwal tidak ditemukan', 422, ErrorCode::SCHEDULE_NOT_FOUND);
        }

        $now = Carbon::now();

        $min = Carbon::today()->setTimeFromTimeString($jadwal->min_pulang);
        $max = Carbon::today()->setTimeFromTimeString($jadwal->max_pulang);

        if ($now->lt($min)) {
            throw new ApiException('Minimal Jam Presensi Pulang ' . $min->format('H:i'), 422, ErrorCode::NOT_TIME_YET);
        }

        if ($now->gt($max)) {
            throw new ApiException('Anda melebihi batas maksimal jam Presensi Pulang', 422, ErrorCode::TOO_LATE);
        }
    }

    public static function apel($jadwal, $type)
    {
        if (!$jadwal) {
            throw new ApiException(
                'Tidak ada jadwal apel',
                422,
                ErrorCode::APEL_NOT_SCHEDULED
            );
        }

        $now = now();

        if ($type === 'pagi') {
            if (!$jadwal->apel_pagi) {
                throw new ApiException(
                    'Tidak ada apel pagi hari ini',
                    422,
                    ErrorCode::APEL_NOT_SCHEDULED
                );
            }

            $min = today()->setTimeFromTimeString($jadwal->jam_apel_pagi);
            $max = today()->setTimeFromTimeString($jadwal->max_apel_pagi);
            $msg_min = 'Minimal Jam Apel Pagi' . $min->format('H:i') . "\n";
            $msg_max = 'Anda melebihi batas maksimal jam apel pagi' . "\n";

        } else {
            if (!now()->isFriday()) {
                throw new ApiException(
                    'Apel sore hanya hari Jumat',
                    422,
                    ErrorCode::APEL_NOT_SCHEDULED
                );
            }

            if (!$jadwal->apel_sore) {
                throw new ApiException(
                    'Tidak ada apel sore hari ini',
                    422,
                    ErrorCode::APEL_NOT_SCHEDULED
                );
            }

            $min = today()->setTimeFromTimeString($jadwal->jam_apel_sore);
            $max = today()->setTimeFromTimeString($jadwal->max_apel_sore);
            $msg_min = 'Minimal Jam Apel Sore' . $min->format('H:i') . "\n";
            $msg_max = 'Anda melebihi batas maksimal jam apel sore' . "\n";

        }

        if ($now->lt($min)) {
            throw new ApiException($msg_min, 422, ErrorCode::APEL_TOO_EARLY);
        }

        if ($now->gt($max)) {
            throw new ApiException($msg_max, 422, ErrorCode::APEL_TOO_LATE);
        }
    }

    public static function validateConfig($user, $type = 'masuk')
    {
        // 1. jumlah hari kerja
        $jmlHariKerja = AttendanceCache::jmlHariKerja();

        if (!$jmlHariKerja || $jmlHariKerja <= 0) {
            throw new ApiException(
                'Jumlah hari kerja belum diinput',
                422,
                ErrorCode::WORKDAY_NOT_CONFIGURED
            );
        }

        // 2. jadwal
        $jadwal = AttendanceCache::jadwal($user->dinas_id);

        if (!$jadwal) {
            throw new ApiException(
                'Jadwal tidak ditemukan',
                422,
                ErrorCode::SCHEDULE_NOT_FOUND
            );
        }

        // 3. potongan TPP
        $potonganTpp = AttendanceCache::potonganTpp()
             ->where('group', $type);

        if ($potonganTpp->isEmpty()) {
            throw new ApiException(
                'Konfigurasi potongan TPP belum tersedia',
                422,
                ErrorCode::TPP_CONFIG_NOT_FOUND
            );
        }

        return [$jadwal, $jmlHariKerja, $potonganTpp];
    }
}
