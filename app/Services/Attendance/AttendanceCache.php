<?php

namespace App\Services\Attendance;

use App\Models\{JamAbsen, ConfigPotTpp, Dinas};
use Illuminate\Support\Facades\Cache;

class AttendanceCache
{
    public static function jadwal()
    {
        return Cache::remember(
            'jam_absen_' . date('w'),
            3600,
            fn () => JamAbsen::find(date('w') <= 4 ? 1 : 2)
        );
    }

    public static function potonganTpp()
    {
        return Cache::remember(
            'config_pot_tpp',
            3600,
            fn () => ConfigPotTpp::all()
        );
    }

    public static function dinas($id)
    {
        return Cache::remember(
            "dinas_{$id}",
            3600,
            fn () => Dinas::find($id)
        );
    }
}
