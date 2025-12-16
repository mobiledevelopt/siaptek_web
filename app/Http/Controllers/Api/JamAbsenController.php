<?php

namespace App\Http\Controllers\Api;


use App\Models\JenisIzin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ConfigPotTpp;
use App\Models\JamAbsen;
use Illuminate\Support\Facades\Validator;
use App\Traits\Upload;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class JamAbsenController extends Controller
{
    use Upload;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $data = new JamAbsen();
        // if ($request->has('filter')) {
        //     $data = $data->where($request->input('filter'));
        // }
        // if ($request->has('search')) {
        //     $data = $data->where('title', 'like', '%' . $request->input('search') . '%');
        // }
        // if ($request->has('pagination') && ($request->input('pagination') == 'true' || $request->input('pagination') == 1)) {
        //     $result = $data->paginate(25)->withQueryString();
        // } else {
            // $result = $data->orderBy('id', 'ASC')->get();
        // }

        // unset($request, $data);

        // foreach ($result as $dat) {
        //     $dat->jam_masuk = date('H:i', strtotime($dat->jam_masuk));
        //     $dat->jam_pulang = date('H:i', strtotime($dat->jam_pulang));
        // }

        $result = [
            [
                "id" => 1,
                "title" => "Senin - Kamis",
                "jam_masuk" => "16:20",
                "jam_pulang" => "03:00",
                "min_masuk" => "13:30:00",
                "max_masuk" => "18:00:00",
                "min_pulang" => "01:00:00",
                "max_pulang" => "20:00:00",
                "created_at" => "2023-12-11T10:02:19.000000Z",
                "updated_at" => "2025-12-11T09:46:06.000000Z"
            ],
            [
                "id" => 2,
                "title" => "Jumat",
                "jam_masuk" => "07:30",
                "jam_pulang" => "16:30",
                "min_masuk" => "06:30:00",
                "max_masuk" => "09:30:00",
                "min_pulang" => "16:30:00",
                "max_pulang" => "18:00:00",
                "created_at" => "2023-12-11T10:02:54.000000Z",
                "updated_at" => "2025-04-07T16:02:45.000000Z"
            ]
        ];
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
    }

    /**
     * Display the specified resource.
     */
    public function show(JamAbsen $jam_absen)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(JamAbsen $jam_absen)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, JamAbsen $jam_absen)
    {
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(JamAbsen $jam_absen)
    {
    }
}
