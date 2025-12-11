<?php

namespace App\Traits;

trait CekRange
{

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
