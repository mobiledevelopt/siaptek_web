<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\KalendarLibur;

class KalendarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $data = new KalendarLibur();
        $data = KalendarLibur::whereDate('tgl', '>=', date('Y-m-d'));
        $data->orderBy('tgl', 'DESC');
        if ($request->has('filter')) {
            $data = $data->where($request->input('filter'));
        }
        if ($request->has('search')) {
            // $data = $data->where('judul', 'like', '%' . $request->input('search') . '%');
        }
        if ($request->has('pagination') && ($request->input('pagination') == 'true' || $request->input('pagination') == 1)) {
            $result = $data->paginate(25)->withQueryString();
        } else {
            $result = $data->get();
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(KalendarLibur $kalendar)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(KalendarLibur $kalendar)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KalendarLibur $kalendar)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KalendarLibur $kalendar)
    {
        //
    }
}
