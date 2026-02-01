<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dinas;
use App\Models\Radius;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DinasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $dinas = new Dinas();
        if ($request->has('filter')) {
            $dinas = $dinas->where($request->input('filter'));
        }
        if ($request->has('search')) {
            $dinas = $dinas->where('judul', 'like', '%' . $request->input('search') . '%');
        }
        if ($request->has('pagination') && ($request->input('pagination') == 'true' || $request->input('pagination') == 1)) {
            $result = $dinas->paginate(25)->withQueryString();
        } else {
            $result = $dinas->get();
        }

        unset($request, $dinas);

        return response()->json([
            'message' => 'success',
            'data' => $result
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Dinas $dina)
    {
        // Radius
        $range = Radius::where('id', 1)->first()->nilai;

        // KUMPULKAN LOKASI DINAS
        $rawLocations = [
            [
                'lat' => $dina->latitude,
                'lng' => $dina->longitude,
                'label' => 'Lokasi 1'
            ],
            [
                'lat' => $dina->latitude_2,
                'lng' => $dina->longitude_2,
                'label' => 'Lokasi 2'
            ]
        ];

        // FILTER LOKASI VALID (tidak null & tidak 0)
        $locations = array_filter($rawLocations, function ($loc) {
            return !is_null($loc['lat']) &&
                !is_null($loc['lng']) &&
                $loc['lat'] != 0 &&
                $loc['lng'] != 0;
        });

        // Jika semua lokasi tidak valid → tolak presensi
        if (count($locations) == 0) {
            return response()->json([
                'message' => 'Lokasi presensi belum diset',
                'izin_presensi' => false
            ], 400);
        }

        $latUser = $request->latitude;
        $lngUser = $request->longitude;

        $distances = [];
        $insideRange = false;

        foreach ($locations as $loc) {

            $distance = $this->cek_range(
                $loc['lat'],
                $loc['lng'],
                $latUser,
                $lngUser
            );

            $distances[] = [
                'lokasi' => $loc['label'],
                'distance' => $distance,
                'in_range' => $distance <= $range
            ];

            if ($distance <= $range) {
                $insideRange = true;
            }
        }

        if (!$insideRange) {
            DB::table('log_langlong')->insert([
                'id_teacher'  => $request->user()->id,
                'nama_teacher' => $request->user()->name,
                'latitude'     => $latUser,
                'longitude'    => $lngUser,
                'lat_school'   => $dina->latitude,
                'long_school'  => $dina->longitude,
                'radius'       => $distances[0]['distance'] ?? null
            ]);
        }

        return response()->json([
            'message' => 'success',
            'range' => $insideRange, // true = boleh presensi
            'f' => collect($distances)->min('distance'),
            'jarak_semua_lokasi' => $distances,
            'data' => [$dina]
        ]);
    }

    public function showOld(Request $request, Dinas $dina)
    {

        $range = Radius::where('id', 1)->first()->nilai;

        $cek = $this->cek_range($dina->latitude, $dina->longitude, $request->latitude, $request->longitude) >
            $range;

        if ($cek == true) {
            DB::table('log_langlong')->insert(
                array(
                    'id_teacher' => $request->user()->id,
                    'nama_teacher' => $request->user()->name,
                    'latitude' =>  $request->latitude,
                    'longitude' => $request->longitude,
                    'lat_school' => $dina->latitude,
                    'long_school' => $dina->longitude,
                    'radius' => $this->cek_range($dina->latitude, $dina->longitude, $request->latitude, $request->longitude)
                )
            );
        }
        return response()->json([
            'message' => 'success',
            'data' => [$dina],
            'f' => $this->cek_range($dina->latitude, $dina->longitude, $request->latitude, $request->longitude) -
                $range,
            'range' => $this->cek_range($dina->latitude, $dina->longitude, $request->latitude, $request->longitude) > $range  ? false : true,
        ]);
    }

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

    function cek_range($lat, $lang, $lat_, $lang_)
    {
        if ($lat == null || $lang == null || $lat_ == null || $lang_ == null) {
            return 11548733;
        }

        $R = 6371.0710;
        $rlat1 = $lat * (pi() / 180);
        $rlat2 = $lat_ * (pi() / 180);
        $difflat = $rlat2 - $rlat1;
        $difflon = ($lang_ - $lang) * (pi() / 180);

        $d = 2 * $R * asin(sqrt(sin($difflat / 2) * sin($difflat / 2) + cos($rlat1) * cos($rlat2) * sin($difflon / 2) * sin($difflon / 2)));
        return round($d * 1000);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
