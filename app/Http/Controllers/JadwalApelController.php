<?php

namespace App\Http\Controllers;

use App\Models\Dinas;
use App\Models\JadwalApel;
use App\Traits\ResponseStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class JadwalApelController extends Controller
{

    use ResponseStatus;

    function __construct()
    {
        $this->middleware('can:jadwal-apel-list', ['only' => ['index', 'show']]);
        $this->middleware('can:jadwal-apel-create', ['only' => ['create', 'store']]);
        $this->middleware('can:jadwal-apel-edit', ['only' => ['edit', 'update']]);
        $this->middleware('can:jadwal-apel-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        if ($request->ajax()) {
            $data = JadwalApel::with('dinas')->get();

            if ($request->user()->role_id != 1) {
                $data = JadwalApel::with('dinas')->where('dinas_id', $request->user()->dinas_id)->get();
            } else {
                $data = JadwalApel::with('dinas')->get();
            }


            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $actionBtn = '<div class="btn-group dropend">
                            <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                Aksi
                            </button>
                            <ul class="dropdown-menu">
                               <li><a class="dropdown-item" href="' . route('jadwal-apel.edit', $row->id) . '">Edit</a></li>
                            </ul>
                          </div>';
                    return $actionBtn;
                })->make();
        }
        return view("contents.jadwal-apel.index")->with([
            "title" => "Jadwal Apel",
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    public function retrive()
    {
        $dinas = Dinas::all();
        foreach ($dinas as $key => $value) {
            # cek exist
            $cek_jadwal_apel = JadwalApel::where('dinas_id', $value->id)->first();
            if ($cek_jadwal_apel == null) {
                DB::beginTransaction();
                try {
                    $jam_apel_pagi = "07:30:00";
                    $max_apel_pagi = "08:15:00";
                    $data = [
                        [
                            'dinas_id' => $value->id, 'hari' => '1', 'apel_pagi' => '1', 'apel_sore' => '0',
                            'jam_apel_pagi' => $jam_apel_pagi, 'max_apel_pagi' => $max_apel_pagi, 'jam_apel_sore' => NULL,
                            'max_apel_sore' => NULL, 'latitude' => $value->latitude, 'longitude' => $value->longitude
                        ],
                        [
                            'dinas_id' => $value->id, 'hari' => '2', 'apel_pagi' => '1', 'apel_sore' => '0',
                            'jam_apel_pagi' => $jam_apel_pagi, 'max_apel_pagi' => $max_apel_pagi, 'jam_apel_sore' => NULL,
                            'max_apel_sore' => NULL, 'latitude' => $value->latitude, 'longitude' => $value->longitude
                        ],
                        [
                            'dinas_id' => $value->id, 'hari' => '3', 'apel_pagi' => '1', 'apel_sore' => '0',
                            'jam_apel_pagi' => $jam_apel_pagi, 'max_apel_pagi' => $max_apel_pagi, 'jam_apel_sore' => NULL,
                            'max_apel_sore' => NULL, 'latitude' => $value->latitude, 'longitude' => $value->longitude
                        ],
                        [
                            'dinas_id' => $value->id, 'hari' => '4', 'apel_pagi' => '1', 'apel_sore' => '0',
                            'jam_apel_pagi' => $jam_apel_pagi, 'max_apel_pagi' => $max_apel_pagi, 'jam_apel_sore' => NULL,
                            'max_apel_sore' => NULL, 'latitude' => $value->latitude, 'longitude' => $value->longitude
                        ],
                        [
                            'dinas_id' => $value->id, 'hari' => '5', 'apel_pagi' => '1', 'apel_sore' => '1',
                            'jam_apel_pagi' => $jam_apel_pagi, 'max_apel_pagi' => $max_apel_pagi, 'jam_apel_sore' => '16:00:00',
                            'max_apel_sore' => '16:30:00', 'latitude' => $value->latitude, 'longitude' => $value->longitude
                        ]
                    ];
                    JadwalApel::insert($data);
                    DB::commit();
                    // echo response()->json($this->responseStore(true, NULL, route('jadwal-apel.index')));
                } catch (\Throwable $throw) {
                    DB::rollBack();
                    dd(response()->json(['error' => $throw->getMessage()]));
                }
            }
        }
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
        $data = JadwalApel::with('dinas')->where('id', $id)->first();
        return view('contents.jadwal-apel.form')->with([
            'title' => 'Edit Jadwal Apel',
            'method' => 'PUT',
            'action' => route('jadwal-apel.update', $id),
            'data' => $data
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'jam_apel_pagi' => 'required_with:apel_pagi',
            'max_apel_pagi' => 'required_with:apel_pagi',
            'jam_apel_sore' => 'required_with:apel_sore',
            'max_apel_sore' => 'required_with:apel_sore',
            'apel_sore' => 'required_without:apel_pagi',
            'apel_pagi' => 'required_without:apel_sore',
        ], [
            'apel_pagi.required_without' => 'Jadwal Apel Pagi harus terisi jika Jadwal Apel Sore tidak diisi.',
            'apel_sore.required_without' => 'Jadwal Apel Sore harus terisi jika Jadwal Apel Pagi tidak diisi.',
            'jam_apel_pagi.required_with' => 'Jam Apel Pagi harus terisi jika Jadwal Apel Pagi dipilih.',
            'jam_apel_sore.required_with' => 'Jam Apel Sore harus terisi jika Jadwal Apel Sore dipilih.',
            'max_apel_pagi.required_with' => 'Maksimal Jam Apel Pagi harus jika Jadwal Apel Pagi dipilih.',
            'max_apel_sore.required_with' => 'Maksimal Jam Apel Sore harus jika Jadwal Apel Sore dipilih.',
        ]);
        if ($validator->passes()) {
            DB::beginTransaction();
            try {

                $data = JadwalApel::findOrFail($id);

                $data->update([
                    'apel_pagi' => $request->apel_pagi,
                    'apel_sore' => $request->apel_sore,
                    'jam_apel_pagi' => $request->jam_apel_pagi,
                    'jam_apel_sore' => $request->jam_apel_sore,
                    'max_apel_pagi' => $request->max_apel_pagi,
                    'max_apel_sore' => $request->max_apel_sore,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude
                ]);
                DB::commit();
                $response = response()->json($this->responseStore(true, NULL, route('jadwal-apel.index')));
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
