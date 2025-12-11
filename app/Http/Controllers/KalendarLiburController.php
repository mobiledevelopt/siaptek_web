<?php

namespace App\Http\Controllers;

use App\Models\KalendarLibur;
use App\Traits\ResponseStatus;
use App\Traits\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class KalendarLiburController extends Controller
{
    use ResponseStatus, Upload;

    function __construct()
    {
        $this->middleware('can:kalendar-libur-list', ['only' => ['index', 'show']]);
        $this->middleware('can:kalendar-libur-create', ['only' => ['create', 'store']]);
        $this->middleware('can:kalendar-libur-edit', ['only' => ['edit', 'update']]);
        $this->middleware('can:kalendar-libur-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        // File::delete(public_path($path));
        // File::delete(url('/storage/') . '/' . $path);
        // dd(public_path('storage/lampiran_kalendar_libur/1_1703090302.xlsx'));
        if ($request->ajax()) {
            $data = KalendarLibur::all();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $actionBtn = '<div class="btn-group dropend">
                            <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                Aksi
                            </button>
                            <ul class="dropdown-menu">
                               <li><a class="dropdown-item" href="' . route('kalendar-libur.edit', $row->id) . '">Edit</a></li>
                               <li><a class="dropdown-item btn-delete" href="#" data-id ="' . $row->id . '" >Hapus</a></li>
                            </ul>
                          </div>';
                    return $actionBtn;
                })->make();
        }
        return view("contents.kalendar-libur.index")->with([
            "title" => "Kalendar Libur",
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('contents.kalendar-libur.form')->with([
            'title' => 'Tambah Kalendar Libur', 'method' => 'POST',
            'action' => route('kalendar-libur.store')
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tgl' => 'required',
            'desc' => 'required',
        ], [
            'tgl.required' => 'Tanggal harus terisi.',
            'desc.required' => 'Keterangan harus terisi.',
        ]);
        if ($validator->passes()) {
            DB::beginTransaction();
            try {

                $cek = KalendarLibur::where([
                    'tgl' => $request->tgl,
                ])->first();

                if ($cek != null) {
                    DB::rollBack();
                    return response()->json(['message' => "Data Sudah Ada"]);
                }

                $attachment = null;
                $path = null;

                if ($request->hasFile('attachment')) {
                    $path = $this->UploadFile($request->file('attachment'), 'lampiran_kalendar_libur'); //use the method in the trait
                    $attachment = url('/storage/') . '/' . $path;
                }

                KalendarLibur::create([
                    'tgl' => $request->tgl,
                    'desc' => $request->desc,
                    'attachment' => $attachment,
                    'attachment_path' => $path
                ]);

                DB::commit();
                $response = response()->json($this->responseStore(true, NULL, route('kalendar-libur.index')));
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
        $data = KalendarLibur::where('id', $id)->first();
        return view('contents.kalendar-libur.form')->with([
            'title' => 'Edit Kalendar Libur',
            'method' => 'PUT',
            'action' => route('kalendar-libur.update', $id),
            'data' => $data
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'tgl' => 'required',
            'desc' => 'required',
        ], [
            'tgl.required' => 'Tanggal harus terisi.',
            'desc.required' => 'Keterangan harus terisi.',
        ]);
        if ($validator->passes()) {
            DB::beginTransaction();
            try {

                $cek = KalendarLibur::where('tgl', '=', $request->bln)->where('id', '!=', $id)->first();

                if ($cek != null) {
                    DB::rollBack();
                    return response()->json(['message' => "Data Sudah Ada"]);
                }
                $data = KalendarLibur::findOrFail($id);

                // dd($request->hasFile('attachment'));
                $data->update([
                    'tgl' => $request->tgl,
                    'desc' => $request->desc
                ]);

                if ($request->hasFile('attachment')) {
                    $path = $this->UploadFile($request->file('attachment'), 'lampiran_kalendar_libur');
                    $attachment = url('/storage/') . '/' . $path;

                    if ($data->attachment_path != null) {
                        File::delete(public_path('storage/' . $data->attachment_path));
                    }

                    $data->update([
                        'attachment' => $attachment,
                        'attachment_path' => $path
                    ]);
                }

                DB::commit();
                $response = response()->json($this->responseStore(true, NULL, route('kalendar-libur.index')));
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
        $data = KalendarLibur::find($id);
        DB::beginTransaction();
        try {
            if ($data->delete()) {
                if ($data->attachment_path != null) {
                    File::delete(public_path('storage/' . $data->attachment_path));
                }
                $response = response()->json($this->responseDelete(true));
            }
            DB::commit();
        } catch (\Throwable $throw) {
            $response = response()->json(['error' => $throw->getMessage()]);
        }
        return $response;
    }
}
