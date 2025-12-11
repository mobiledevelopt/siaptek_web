<?php

namespace App\Http\Controllers;

use App\Models\Radius;
use App\Traits\ResponseStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Yajra\DataTables\Facades\DataTables;

class RadiusController extends Controller
{

    use ResponseStatus;

    function __construct()
    {
        $this->middleware('can:radius-list', ['only' => ['index', 'show']]);
        $this->middleware('can:radius-create', ['only' => ['create', 'store']]);
        $this->middleware('can:radius-edit', ['only' => ['edit', 'update']]);
        $this->middleware('can:radius-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        if ($request->ajax()) {
            $data = Radius::all();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $actionBtn = '<div class="btn-group dropend">
                            <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                Aksi
                            </button>
                            <ul class="dropdown-menu">
                               <li><a class="dropdown-item" href="' . route('radius.edit', $row->id) . '">Edit</a></li>
                            </ul>
                          </div>';
                    return $actionBtn;
                })->make();
        }
        return view("contents.radius.index")->with([
            "title" => "Radius",
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('contents.radius.form')->with([
            'title' => 'Tambah Jadwal Radius', 'method' => 'POST',
            'action' => route('radius.store')
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //     'tgl' => 'required',
        //     'title' => 'required',
        // ], [
        //     'tgl.required' => 'Tanggal harus terisi.',
        //     'title.required' => 'Keterangan harus terisi.',
        // ]);
        // if ($validator->passes()) {
        //     DB::beginTransaction();
        //     try {

        //         $cek = Radius::where([
        //             'tgl' => $request->tgl,
        //         ])->first();

        //         if ($cek != null) {
        //             DB::rollBack();
        //             return response()->json(['message' => "Data Sudah Ada"]);
        //         }

        //         $radius = Radius::create([
        //             'tgl' => $request->tgl,
        //             'title' => $request->title
        //         ]);

        //         QrCode::size(1000)->margin(10)->generate($apel->id, '../public/qrcodes/' . $apel->id . '.svg');
        //         $apel->qrcode = $apel->id . '.svg';
        //         $apel->save();

        //         DB::commit();
        //         $response = response()->json($this->responseStore(true, NULL, route('apel.index')));
        //     } catch (\Throwable $throw) {
        //         DB::rollBack();
        //         $response = response()->json(['error' => $throw->getMessage()]);
        //     }
        // } else {
        //     $response = response()->json(['error' => $validator->errors()->all()]);
        // }
        // return $response;
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
        $data = Radius::where('id', $id)->first();
        return view('contents.radius.form')->with([
            'title' => 'Edit ' . $data->title,
            'method' => 'PUT',
            'action' => route('radius.update', $id),
            'data' => $data
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nilai' => 'required',
        ], [
            'nilai.required' => 'Radius harus terisi.',
        ]);
        if ($validator->passes()) {
            DB::beginTransaction();
            try {

                $data = Radius::findOrFail($id);

                $data->update([
                    'nilai' => $request->nilai
                ]);
                DB::commit();
                $response = response()->json($this->responseStore(true, NULL, route('radius.index')));
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
        $data = Radius::find($id);
        DB::beginTransaction();
        try {
            if ($data->delete()) {
                File::delete(public_path('qrcodes/' . $data->qrcode));
                $response = response()->json($this->responseDelete(true));
            }
            DB::commit();
        } catch (\Throwable $throw) {
            $response = response()->json(['error' => $throw->getMessage()]);
        }
        return $response;
    }
}
