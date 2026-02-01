<?php

namespace App\Traits;

trait CekRange
{

    public function cek_range_new($lat1, $lon1, $lat2, $lon2)
    {
        // Radius bumi dalam meter
        $earthRadius = 6371000;

        // Ubah derajat ke radian
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        // Haversine formula
        $latDiff = $lat2 - $lat1;
        $lonDiff = $lon2 - $lon1;

        $a = sin($latDiff / 2) * sin($latDiff / 2)
            + cos($lat1) * cos($lat2)
            * sin($lonDiff / 2) * sin($lonDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        // Jarak dalam meter
        $distance = $earthRadius * $c;

        return $distance;
    }

    public static function cek_range($lat, $lang, $lat_, $lang_)
    {
        if ($lat == null || $lang == null || $lat_ == null || $lang_ == null) {
            return 11548733;
        }

        $r = 6371.0710;
        $rlat1 = $lat * (pi() / 180);
        $rlat2 = $lat_ * (pi() / 180);
        $difflat = $rlat2 - $rlat1;
        $difflon = ($lang_ - $lang) * (pi() / 180);

        $d = 2 * $r * asin(sqrt(sin($difflat / 2) * sin($difflat / 2) + cos($rlat1) * cos($rlat2) * sin($difflon / 2) * sin($difflon / 2)));

        return round($d * 1000);
    }
}
