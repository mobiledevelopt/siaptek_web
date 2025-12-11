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
        $data = new JamAbsen();
        if ($request->has('filter')) {
            $data = $data->where($request->input('filter'));
        }
        if ($request->has('search')) {
            $data = $data->where('title', 'like', '%' . $request->input('search') . '%');
        }
        if ($request->has('pagination') && ($request->input('pagination') == 'true' || $request->input('pagination') == 1)) {
            $result = $data->paginate(25)->withQueryString();
        } else {
            $result = $data->orderBy('id', 'ASC')->get();
        }

        unset($request, $data);

        foreach ($result as $dat) {
            $dat->jam_masuk = date('H:i', strtotime($dat->jam_masuk));
            $dat->jam_pulang = date('H:i', strtotime($dat->jam_pulang));
        }

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
