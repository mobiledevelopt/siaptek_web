<?php

namespace App\Services\Attendance;

use App\Models\{JamAbsen, ConfigPotTpp, Dinas, JadwalApel, Jml_hari_kerja};
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AttendanceCache
{
    public static function jadwal()
    {
        $day = Carbon::now()->dayOfWeek;

        $jadwalId = $day <= 4 ? 1 : 2;

        return Cache::remember(
            "jam_absen_$day",
            now()->addHour(),
            fn () => JamAbsen::find($jadwalId)
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
    
    public static function jmlHariKerja()
    {
        $bulan = Carbon::now()->month;
        $tahun = Carbon::now()->year;

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
        $day = $day ?? Carbon::now()->dayOfWeek;

        return Cache::remember(
            "jadwal_apel_{$dinasId}_$day",
            now()->addHour(),
            fn () => JadwalApel::where(['dinas_id' => $dinasId, 'hari' => $day])->first()
        );
    }
    
    public static function isLibur()
    {
        $day = Carbon::now()->dayOfWeek;

        return Cache::remember(
            "is_libur_$day",
            now()->addHour(),
            fn () => in_array($day, [0, 6])
        );
    }

    // --- Invalidation Methods ---

    public static function clearJadwal()
    {
        for ($i = 0; $i <= 6; $i++) {
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
        Cache::forget("jml_hari_kerja_{$bulan}_{$tahun}");
    }

    public static function clearJadwalApel($dinasId, $day = null)
    {
        if ($day !== null) {
            Cache::forget("jadwal_apel_{$dinasId}_$day");
        } else {
            // Jika day null, clear semua hari untuk dinas tersebut
            for ($i = 0; $i <= 6; $i++) {
                Cache::forget("jadwal_apel_{$dinasId}_$i");
            }
        }
    }
}