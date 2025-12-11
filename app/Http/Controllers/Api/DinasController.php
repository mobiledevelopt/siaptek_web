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
