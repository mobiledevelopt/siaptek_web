<?php

namespace App\Http\Controllers;

use App\Models\JamAbsen;
use App\Traits\ResponseStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class JamAbsenController extends Controller
{

    use ResponseStatus;

    function __construct()
    {
        $this->middleware('can:jam-absen-list', ['only' => ['index', 'show']]);
        $this->middleware('can:jam-absen-create', ['only' => ['create', 'store']]);
        $this->middleware('can:jam-absen-edit', ['only' => ['edit', 'update']]);
        $this->middleware('can:jam-absen-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        if ($request->ajax()) {
            $data = JamAbsen::all();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $actionBtn = '<div class="btn-group dropend">
                            <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                Aksi
                            </button>
                            <ul class="dropdown-menu">
                               <li><a class="dropdown-item" href="' . route('jam-absen.edit', $row->id) . '">Edit</a></li>
                            </ul>
                          </div>';
                    return $actionBtn;
                })->make();
        }
        return view("contents.jam-absen.index")->with([
            "title" => "Jam Presensi",
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
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $data = JamAbsen::where('id', $id)->first();
        return view('contents.jam-absen.form')->with([
            'title' => 'Edit Jam Presensi',
            'method' => 'PUT',
            'action' => route('jam-absen.update', $id),
            'data' => $data
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'jam_masuk' => 'required',
            'jam_pulang' => 'required',
            'min_masuk' => 'required',
            'max_masuk' => 'required',
            'min_pulang' => 'required',
            'max_pulang' => 'required'
        ], [
            'jam_masuk.required' => 'Jam Masuk harus terisi.',
            'jam_pulang.required' => 'Jam Pulang harus terisi.',
            'min_masuk.required' => 'Min Jam Masuk harus terisi.',
            'max_masuk.required' => 'Max Jam Masuk harus terisi.',
            'min_pulang.required' => 'Min Jam Pulang harus terisi.',
            'max_pulang.required' => 'Max Jam Pulang harus terisi.'
        ]);
        if ($validator->passes()) {
            DB::beginTransaction();
            try {

                $data = JamAbsen::findOrFail($id);

                $data->update([
                    'jam_masuk' => $request->jam_masuk,
                    'jam_pulang' => $request->jam_pulang,
                    'min_masuk' => $request->min_masuk,
                    'max_masuk' => $request->max_masuk,
                    'min_pulang' => $request->min_pulang,
                    'max_pulang' => $request->max_pulang
                ]);
                DB::commit();
                $response = response()->json($this->responseStore(true, NULL, route('jam-absen.index')));
            } catch (\Throwable $throw) {
                DB::rollBack();
                $response = response()->json(['error' => $throw->getMessage()]);
            }
        } else {
            $response = response()->json(['error' => $validator->errors()->all()]);
        }
        return $response;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
