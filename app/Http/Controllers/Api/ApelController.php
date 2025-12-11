<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apel;
use App\Models\JadwalApel;
use App\Models\JenjangPendidikan;
use App\Models\Radius;
use App\Traits\CekRange;
use Illuminate\Http\Request;


class ApelController extends Controller
{
    use CekRange;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = Apel::whereDate('tgl', '=', date('Y-m-d'));

        if ($request->has('filter')) {
            $data = $data->where($request->input('filter'));
        }
        if ($request->has('search')) {
            $data = $data->where('judul', 'like', '%' . $request->input('search') . '%');
        }
        if ($request->has('pagination') && ($request->input('pagination') == 'true' || $request->input('pagination') == 1)) {
            $result = $data->paginate(25)->withQueryString();
        } else {
            $data->where('all', '1');
            if ($data->first() != null) {
                $result = $data->get();
                $result_object = $data->first();
            } else {
                $data = JadwalApel::where('hari', '=', date('w'))->where('dinas_id', $request->user()->dinas_id);
                // dd(date('H:m') >= $data->max_jam_apel_pagi);
                // $data = Apel::whereDate('tgl', '=', date('Y-m-d'));
                // $dinas_ = $request->user()->dinas_id;
                // $data->whereHas(
                //     'peserta_dinas',
                //     function ($q) use ($dinas_) {
                //         $q->where('dinas_id', $dinas_);
                //     }
                // );
                $result = $data->get();
                $result_object = $data->first();
            }
            $radius = Radius::where('id', 2)->first()->nilai;
            $radius_apel = $this->cek_range($result_object->latitude ?? null, $result_object->longitude ?? null, $request->latitude, $request->longitude);
        }

        unset($request, $dinas);

        return response()->json([
            'message' => 'success',
            'data' => $result,
            'f' => $radius_apel - $radius,
            'range' => $radius_apel > $radius ? false : true
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
    public function show(Request $request, JenjangPendidikan $jenjangPendidikan)
    {
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
