<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use App\Models\ResetImei;
use App\Traits\ResponseStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class ResetImeiController extends Controller
{
    use ResponseStatus;

    function __construct()
    {
        $this->middleware('can:reset-imei-list', ['only' => ['index', 'show']]);
        $this->middleware('can:reset-imei-create', ['only' => ['create', 'store']]);
        $this->middleware('can:reset-imei-edit', ['only' => ['edit', 'update']]);
        $this->middleware('can:reset-imei-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = ResetImei::with(['pegawai', 'dinas']);

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $actionBtn = '<div class="btn-group dropend">
                            <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                Aksi
                            </button>
                            <ul class="dropdown-menu">
                               <li><a class="dropdown-item btn-delete" href="#" data-id ="' . $row->id . '" >Hapus</a></li>
                            </ul>
                          </div>';
                    return $actionBtn;
                })->make();
        }
        return view("contents.reset-imei.index")->with([
            "title" => "Reset Imei",
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('contents.reset-imei.form')->with([
            'title' => 'Tambah Data Reset imei', 'method' => 'POST',
            'action' => route('reset-imei.store')
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pegawai' => 'required',
            'alasan' => 'required',
        ], [
            'pegawai.required' => 'Pegawai harus terisi.',
            'alasan.required' => 'alasan harus terisi.',
        ]);
        if ($validator->passes()) {
            DB::beginTransaction();
            try {

                $pegawai = Pegawai::find($request->pegawai);

                if ($pegawai === null) {
                    DB::rollBack();
                    return response()->json(['message' => "Data Pegawai Tidak di Temukan"]);
                }
                DB::delete('delete from personal_access_tokens where tokenable_id = ' . $pegawai->id);
                $pegawai->imei = null;
                $pegawai->save();

                ResetImei::create([
                    'tgl' => $request->tgl,
                    'pegawai_id' => $request->pegawai,
                    'alasan' => $request->alasan,
                    'dinas_id' => $pegawai->dinas_id
                ]);

                DB::commit();
                $response = response()->json($this->responseStore(true, NULL, route('reset-imei.index')));
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
