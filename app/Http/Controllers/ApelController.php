<?php

namespace App\Http\Controllers;

use App\Models\Apel;
use App\Models\ApelPesertaDinas;
use App\Traits\ResponseStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Yajra\DataTables\Facades\DataTables;

class ApelController extends Controller
{

    use ResponseStatus;

    function __construct()
    {
        $this->middleware('can:apel-list', ['only' => ['index', 'show']]);
        $this->middleware('can:apel-create', ['only' => ['create', 'store']]);
        $this->middleware('can:apel-edit', ['only' => ['edit', 'update']]);
        $this->middleware('can:apel-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        if ($request->ajax()) {
            $data = Apel::all();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $actionBtn = '<div class="btn-group dropend">
                            <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                Aksi
                            </button>
                            <ul class="dropdown-menu">
                               <li><a class="dropdown-item" href="' . route('apel.edit', $row->id) . '">Edit</a></li>
                               <li><a class="dropdown-item btn-delete" href="#" data-id ="' . $row->id . '" >Hapus</a></li>
                            </ul>
                          </div>';
                    return $actionBtn;
                })->make();
        }
        return view("contents.apel.index")->with([
            "title" => "Jadwal Apel",
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('contents.apel.form')->with([
            'title' => 'Tambah Jadwal Apel', 'method' => 'POST',
            'action' => route('apel.store')
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tgl' => 'required',
            'title' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ], [
            'tgl.required' => 'Tanggal harus terisi.',
            'title.required' => 'Keterangan harus terisi.',
            'latitude.required' => 'Latitude harus terisi.',
            'longitude.required' => 'Longitude harus terisi.',
        ]);
        if ($validator->passes()) {
            DB::beginTransaction();
            try {
                // jika aple untuk seluruh dinas di buat maka apel untuk masing2 dinas tidak boleh lg
                if ($request->dinas == null) {
                    $all = "1";
                    $cek = Apel::where([
                        'tgl' => $request->tgl,
                    ])->first();
                    if ($cek != null) {
                        DB::rollBack();
                        return response()->json(['message' => "Jadwal Apel Sudah Ada"]);
                    }
                } else {
                    $all = "0";
                    //cek apel smw dinas
                    $cek_apel_smw = Apel::where([
                        'tgl' => $request->tgl,
                        'all' => "1"
                    ])->first();

                    if ($cek_apel_smw != null) {
                        DB::rollBack();
                        return response()->json(['message' => "Jadwal Apel Dinas Sudah Ada"]);
                    }

                    $cek = Apel::where([
                        'tgl' => $request->tgl,
                        'all' => $all
                    ])->first();

                    //cek peserta
                    $peserta = ApelPesertaDinas::where([
                        'apel_id' => $cek->id ?? '',
                    ])->whereIn('dinas_id', $request->dinas)->first();
                    if ($cek != null && $peserta != null) {
                        DB::rollBack();
                        return response()->json(['message' => "Jadwal Apel Dinas Sudah Ada"]);
                    }
                }

                $apel = Apel::create([
                    'tgl' => $request->tgl,
                    'title' => $request->title,
                    'all' => $all,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude
                ]);

                if ($request->dinas != null) {
                    $peserta_dinas = [];
                    foreach ($request->dinas as $key) {
                        array_push($peserta_dinas, ['apel_id' => $apel->id, 'dinas_id' => $key]);
                    }

                    ApelPesertaDinas::insert($peserta_dinas);
                }

                QrCode::size(1000)->margin(10)->generate($apel->id, '../public/qrcodes/' . $apel->id . '.svg');
                $apel->qrcode = $apel->id . '.svg';
                $apel->save();

                DB::commit();
                $response = response()->json($this->responseStore(true, NULL, route('apel.index')));
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
        $data = Apel::where('id', $id)->first();
        $dinas = ApelPesertaDinas::with('dinas')->where('apel_id', $id)->get();
        return view('contents.apel.form')->with([
            'title' => 'Edit Jadwal Apel',
            'method' => 'PUT',
            'action' => route('apel.update', $id),
            'data' => $data,
            'dinas' => $dinas
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'tgl' => 'required',
            'title' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ], [
            'tgl.required' => 'Tanggal harus terisi.',
            'title.required' => 'Keterangan harus terisi.',
            'latitude.required' => 'Latitude harus terisi.',
            'longitude.required' => 'Longitude harus terisi.',
        ]);

        if ($validator->passes()) {
            DB::beginTransaction();
            try {

                // $cek = Apel::where('tgl', '=', $request->bln)->where('id', '!=', $id)->first();
                // if ($cek != null) {
                //     DB::rollBack();
                //     return response()->json(['message' => "Data Sudah Ada"]);
                // }

                if ($request->dinas == null) {
                    $all = "1";
                    $cek = Apel::where([
                        'tgl' => $request->tgl,
                        'all' => $all
                    ])->where('id', '!=', $id)->first();
                    if ($cek != null) {
                        DB::rollBack();
                        return response()->json(['message' => "Jadwal Apel Seluruh Dinas Sudah Ada"]);
                    }
                } else {

                    $all = "0";
                    $cek_apel_smw = Apel::where('id', '!=', $id)->where([
                        'tgl' => $request->tgl,
                        'all' => "1"
                    ])->first();

                    if ($cek_apel_smw != null) {
                        DB::rollBack();
                        return response()->json(['message' => "Jadwal Apel Dinas Sudah Ada"]);
                    }
                    //cek apel
                    $cek = Apel::where('id', '!=', $id)->where([
                        'tgl' => $request->tgl,
                        'all' => $all
                    ])->first();
                    //cek peserta
                    $peserta = ApelPesertaDinas::where([
                        'apel_id' => $cek->id ?? '',
                    ])->whereIn('dinas_id', $request->dinas)->first();
                    if ($cek != null && $peserta != null) {
                        DB::rollBack();
                        return response()->json(['message' => "Jadwal Apel Dinas Sudah Ada"]);
                    }
                }

                $data = Apel::findOrFail($id);

                QrCode::size(1000)->margin(10)->generate($id, '../public/qrcodes/' . $id . '.svg');
                $data->update([
                    'tgl' => $request->tgl,
                    'title' => $request->title,
                    'qrcode' => $id . '.svg',
                    'all' => $all,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude
                ]);

                ApelPesertaDinas::where('apel_id', $id)->delete();
                if ($request->dinas != null) {
                    $peserta_dinas = [];
                    foreach ($request->dinas as $key) {
                        array_push($peserta_dinas, ['apel_id' => $id, 'dinas_id' => $key]);
                    }
                    ApelPesertaDinas::insert($peserta_dinas);
                }

                DB::commit();
                $response = response()->json($this->responseStore(true, NULL, route('apel.index')));
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
        $response = response()->json($this->responseDelete(false));
        $data = Apel::find($id);
        DB::beginTransaction();
        try {
            if ($data->delete()) {
                File::delete(public_path('qrcodes/' . $data->qrcode));
                $response = response()->json($this->responseDelete(true));
            }
            DB::commit();
        } catch (\Throwable $throw) {
            DB::rollBack();
            $response = response()->json(['error' => $throw->getMessage()]);
        }
        return $response;
    }
}
