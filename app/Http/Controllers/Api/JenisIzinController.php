<?php

namespace App\Http\Controllers\Api;


use App\Models\JenisIzin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ConfigPotTpp;
use Illuminate\Support\Facades\Validator;
use App\Traits\Upload;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class JenisIzinController extends Controller
{
    use Upload;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = ConfigPotTpp::where('group', 'izin')->Orwhere('group', 'cuti');
        if ($request->has('filter')) {
            // $data = $data->where($request->input('filter'));
        }
        if ($request->has('search')) {
            // $data = $data->where('judul', 'like', '%' . $request->input('search') . '%');
        }
        if ($request->has('pagination') && ($request->input('pagination') == 'true' || $request->input('pagination') == 1)) {
            $result = $data->paginate(25)->withQueryString();
        } else {
            $result = $data->orderBy('id', 'DESC')->get();
        }

        unset($request, $data);

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
    public function show(JenisIzin $jenisIzin)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(JenisIzin $jenisIzin)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, JenisIzin $jenisIzin)
    {
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(JenisIzin $jenisIzin)
    {
    }
}
