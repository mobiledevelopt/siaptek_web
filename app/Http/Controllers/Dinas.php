<?php

namespace App\Http\Controllers;

use App\Models\Dinas as ModelsDinas;
use App\Models\JadwalApel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Traits\ResponseStatus;
use Illuminate\Support\Facades\Validator;

class Dinas extends Controller
{
    use ResponseStatus;

    function __construct()
    {
        $this->middleware('can:web-dinas-list', ['only' => ['index', 'show']]);
        $this->middleware('can:web-dinas-create', ['only' => ['create', 'store']]);
        $this->middleware('can:web-dinas-edit', ['only' => ['edit', 'update']]);
        $this->middleware('can:web-dinas-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = ModelsDinas::all();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $actionBtn = '<div class="btn-group dropend">
                            <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                Aksi
                            </button>
                            <ul class="dropdown-menu">
                               <li><a class="dropdown-item" href="' . route('web-dinas.edit', $row->id) . '">Edit</a></li>
                               <li><a class="dropdown-item btn-delete" href="#" data-id ="' . $row->id . '" >Hapus</a></li>
                            </ul>
                          </div>';
                    return $actionBtn;
                })->make();
        }
        return view("contents.dinas.index")->with([
            'title' => 'Data Dinas'
        ]);
    }

    public function create(Request $request)
    {
        return view('contents.dinas.form')->with([
            'title' => 'Dinas Baru',
            'method' => 'POST',
            'action' => route('web-dinas.store')
        ]);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'latitude' => 'required',
            'longitude' => 'required'
        ], [
            'name.required' => 'Nama Dinas wajib terisi.',
            'latitude.required' => 'Latitude wajib terisi.',
            'longitude.required' => 'Longitude wajib terisi.',
        ]);

        if ($validator->passes()) {
            DB::beginTransaction();
            try {

                $row = ModelsDinas::firstOrNew(['id' => $request->id]);

                $row->name = $request->name;
                $row->latitude = trim($request->latitude);
                $row->longitude = trim($request->longitude);
                $row->latitude_2 = trim($request->latitude_2);
                $row->longitude_2 = trim($request->longitude_2);

                if ($row->save()) {
                    $cek_jadwal_apel = JadwalApel::where('dinas_id', $row->id)->first();
                    if ($cek_jadwal_apel == null) {
                        $jam_apel_pagi = "07:30:00";
                        $max_apel_pagi = "08:15:00";
                        $data = [
                            [
                                'dinas_id' => $row->id,
                                'hari' => '1',
                                'apel_pagi' => '1',
                                'apel_sore' => '0',
                                'jam_apel_pagi' => $jam_apel_pagi,
                                'max_apel_pagi' => $max_apel_pagi,
                                'jam_apel_sore' => NULL,
                                'max_apel_sore' => NULL,
                                'latitude' => $row->latitude,
                                'longitude' => $row->longitude
                            ],
                            [
                                'dinas_id' => $row->id,
                                'hari' => '2',
                                'apel_pagi' => '1',
                                'apel_sore' => '0',
                                'jam_apel_pagi' => $jam_apel_pagi,
                                'max_apel_pagi' => $max_apel_pagi,
                                'jam_apel_sore' => NULL,
                                'max_apel_sore' => NULL,
                                'latitude' => $row->latitude,
                                'longitude' => $row->longitude
                            ],
                            [
                                'dinas_id' => $row->id,
                                'hari' => '3',
                                'apel_pagi' => '1',
                                'apel_sore' => '0',
                                'jam_apel_pagi' => $jam_apel_pagi,
                                'max_apel_pagi' => $max_apel_pagi,
                                'jam_apel_sore' => NULL,
                                'max_apel_sore' => NULL,
                                'latitude' => $row->latitude,
                                'longitude' => $row->longitude
                            ],
                            [
                                'dinas_id' => $row->id,
                                'hari' => '4',
                                'apel_pagi' => '1',
                                'apel_sore' => '0',
                                'jam_apel_pagi' => $jam_apel_pagi,
                                'max_apel_pagi' => $max_apel_pagi,
                                'jam_apel_sore' => NULL,
                                'max_apel_sore' => NULL,
                                'latitude' => $row->latitude,
                                'longitude' => $row->longitude
                            ],
                            [
                                'dinas_id' => $row->id,
                                'hari' => '5',
                                'apel_pagi' => '1',
                                'apel_sore' => '1',
                                'jam_apel_pagi' => $jam_apel_pagi,
                                'max_apel_pagi' => $max_apel_pagi,
                                'jam_apel_sore' => '16:00:00',
                                'max_apel_sore' => '16:30:00',
                                'latitude' => $row->latitude,
                                'longitude' => $row->longitude
                            ]
                        ];
                        JadwalApel::insert($data);
                    }
                }

                DB::commit();
                $response = response()->json($this->responseStore(true, NULL, route('web-dinas.index')));
            } catch (\Throwable $throw) {
                DB::rollBack();
                $response = response()->json(['error' => $throw->getMessage()]);
            }
        } else {
            $response = response()->json(['error' => $validator->errors()->all()]);
        }
        return $response;
    }

    public function edit(Request $request, string $id)
    {
        $data = ModelsDinas::find($id);

        return view('contents.dinas.form')->with([
            'title' => 'Update Dinas',
            'method' => 'PUT',
            'action' => route('web-dinas.update', $id),
            'edit' => $data,
        ]);
    }

    public function update(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'latitude' => 'required',
            'longitude' => 'required'
        ], [
            'name.required' => 'Nama Dinas wajib terisi.',
            'latitude.required' => 'Latitude wajib terisi.',
            'longitude.required' => 'Longitude wajib terisi.',
        ]);

        if ($validator->passes()) {
            DB::beginTransaction();
            try {
                $data_update = [
                    "name"      => $request->name,
                    "latitude"  => $request->latitude,
                    "longitude" => $request->longitude,
                    "latitude_2"  => $request->latitude_2,
                    "longitude_2" => $request->longitude_2
                ];

                $data = ModelsDinas::findOrFail($id);
                $data->update($data_update);

                DB::commit();
                $response = response()->json($this->responseStore(true, NULL, route('web-dinas.index')));
            } catch (\Throwable $throw) {
                DB::rollBack();
                $response = response()->json(['error' => $throw->getMessage()]);
            }
        } else {
            $response = response()->json(['error' => $validator->errors()->all()]);
        }
        return $response;
    }

    public function destroy($id)
    {
        $response = response()->json($this->responseDelete(false));
        $data = ModelsDinas::where('id', $id)->first();
        DB::beginTransaction();
        try {
            $data->jadwal_apel()->delete();
            $data->delete();
            $response = response()->json($this->responseDelete(true));
            DB::commit();
        } catch (\Throwable $throw) {
            $response = response()->json(['error' => $throw->getMessage()]);
        }
        return $response;
    }

    public function select2(Request $request)
    {
        $page = $request->page;
        $resultCount = 10;
        $offset = ($page - 1) * $resultCount;

        if ($request->user()->role_id != 1) {

            $data = ModelsDinas::where('id', $request->user()->dinas_id)
                ->skip($offset)
                ->take($resultCount)
                ->selectRaw('id, name as text')
                ->get();

            $count = ModelsDinas::where('id', $request->user()->dinas_id)->where('name', 'LIKE', '%' . $request->q . '%')
                ->get()
                ->count();
        } else {
            $data = ModelsDinas::where('name', 'LIKE', '%' . $request->q . '%')
                ->orderBy('name')
                ->skip($offset)
                ->take($resultCount)
                ->selectRaw('id, name as text')
                ->get();
            $count = ModelsDinas::where('name', 'LIKE', '%' . $request->q . '%')
                ->get()
                ->count();
        }

        $endCount = $offset + $resultCount;
        $morePages = $count > $endCount;

        $results = array(
            "results" => $data,
            "pagination" => array(
                "more" => $morePages
            )
        );

        return response()->json($results);
    }
}
