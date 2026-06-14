<?php

namespace App\Services\Attendance;

use App\Models\{JamAbsen, ConfigPotTpp, Dinas, JadwalApel, Jml_hari_kerja};
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttendanceCache
{
    public static function jadwal()
    {
        $day = (string) Carbon::now()->dayOfWeek;
        $jadwalId = (int)$day <= 4 ? 1 : 2;

        return Cache::remember(
            "jam_absen_$day",
            now()->addHour(),
            function () use ($day, $jadwalId) {
                // Log::info("Cache MISS: jam_absen_$day (generating new data)");
                return JamAbsen::find($jadwalId);
            }
        );
    }

    public static function potonganTpp()
    {
        return Cache::remember(
            'config_pot_tpp',
            now()->addHour(),
            fn () => ConfigPotTpp::all()
        );
    }

    public static function dinas($id)
    {
        return Cache::remember(
            "dinas_$id",
            now()->addHour(),
            fn () => Dinas::find($id)
        );
    }
    
    public static function jmlHariKerja($date = null)
    {
        $parsedDate = $date ? Carbon::parse($date) : Carbon::now();
        $bulan = $parsedDate->month;
        $tahun = $parsedDate->year;

        return Cache::remember(
            "jml_hari_kerja_{$bulan}_{$tahun}",
            3600,
            fn () => Jml_hari_kerja::where([
                'bulan' => $bulan,
                'tahun' => $tahun
            ])->first()?->jml_hari_kerja ?? 0
        );
    }

    public static function jadwalApel($dinasId, $day = null)
    {
        $day = (string) ($day ?? Carbon::now()->dayOfWeek);

        return Cache::remember(
            "jadwal_apel_{$dinasId}_$day",
            now()->addHour(),
            function () use ($dinasId, $day) {
                // Log::info("Cache MISS: jadwal_apel_{$dinasId}_$day (generating new data)");
                return JadwalApel::where(['dinas_id' => $dinasId, 'hari' => $day])->first();
            }
        );
    }
    
    public static function isLibur()
    {
        $today = Carbon::now();
        $dateStr = $today->toDateString();

        return Cache::remember(
            "is_libur_{$dateStr}",
            now()->addHour(),
            function () use ($today) {
                if ($today->isWeekend()) {
                    return true;
                }
                return \App\Models\KalendarLibur::whereDate('tgl', $today)->exists();
            }
        );
    }

    // --- Invalidation Methods ---

    public static function clearJadwal()
    {
        for ($i = 0; $i <= 6; $i++) {
            Log::info("Cache FORGET: jam_absen_$i");
            Cache::forget("jam_absen_$i");
        }
    }

    public static function clearPotonganTpp()
    {
        Cache::forget('config_pot_tpp');
    }

    public static function clearDinas($id)
    {
        Cache::forget("dinas_$id");
    }

    public static function clearJmlHariKerja($bulan, $tahun)
    {
        $bulan = (int) $bulan;
        $tahun = (int) $tahun;
        Cache::forget("jml_hari_kerja_{$bulan}_{$tahun}");
    }

    public static function clearJadwalApel($dinasId, $day = null)
    {
        if ($day !== null) {
            Log::info("Cache FORGET: jadwal_apel_{$dinasId}_$day");
            Cache::forget("jadwal_apel_{$dinasId}_$day");
        } else {
            // Jika day null, clear semua hari untuk dinas tersebut
            for ($i = 0; $i <= 6; $i++) {
                Log::info("Cache FORGET: jadwal_apel_{$dinasId}_$i");
                Cache::forget("jadwal_apel_{$dinasId}_$i");
            }
        }
    }
}