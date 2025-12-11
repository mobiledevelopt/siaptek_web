<?php

namespace App\Http\Controllers;

use App\Models\ConfigPotTpp;
use App\Traits\ResponseStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ConfigTppController extends Controller
{
    use ResponseStatus;

    function __construct()
    {
        $this->middleware('can:config-tpp-list', ['only' => ['index', 'show']]);
        $this->middleware('can:config-tpp-create', ['only' => ['create', 'store']]);
        $this->middleware('can:config-tpp-edit', ['only' => ['edit', 'update']]);
        $this->middleware('can:config-tpp-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = ConfigPotTpp::all();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $actionBtn = '<div class="btn-group dropend">
                            <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                Aksi
                            </button>
                            <ul class="dropdown-menu">
                               <li><a class="dropdown-item" href="' . route('config-tpp.edit', $row->id) . '">Edit</a></li>
                            </ul>
                          </div>';
                    return $actionBtn;
                })->make();
        }
        return view("contents.config-tpp.index")->with([
            "title" => "Config Potongan TPP",
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
        $data = ConfigPotTpp::where('id', $id)->first();
        return view('contents.config-tpp.form')->with([
            'title' => 'Edit Config Potongan TPP',
            'method' => 'PUT',
            'action' => route('config-tpp.update', $id),
            'data' => $data
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        DB::beginTransaction();
        try {

            $data = ConfigPotTpp::findOrFail($id);
            $data->update([
                'dari_meni' => $request->dari_meni,
                'sampai_menit' => $request->sampai_menit,
                'persentase_potongan' => $request->persentase_potongan,
            ]);
            DB::commit();
            $response = response()->json($this->responseStore(true, NULL, route('config-tpp.index')));
        } catch (\Throwable $throw) {
            DB::rollBack();
            $response = response()->json(['error' => $throw->getMessage()]);
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
