<?php

namespace App\Http\Controllers\Api;

use App\Models\Pengumuman;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PengumumanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = Pengumuman::whereDate('tgl', '>=', date('Y-m-d'));
        $dinas_ = $request->user()->dinas_id;
        if ($request->has('filter')) {
            $data = $data->where($request->input('filter'));
        }
        if ($request->has('search')) {
            $data = $data->where('judul', 'like', '%' . $request->input('search') . '%');
        }
        if ($request->has('pagination') && ($request->input('pagination') == 'true' || $request->input('pagination') == 1)) {
            $result = $data->paginate(25)->withQueryString();
        } else {
            $data->where(function ($query) use ($dinas_) {
                $query->where('dinas_id', '=', $dinas_)
                    ->orWhere('dinas_id', '=', NULL);
            });

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
    public function show(Pengumuman $pengumuman)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pengumuman $pengumuman)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pengumuman $pengumuman)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pengumuman $pengumuman)
    {
        //
    }
}
