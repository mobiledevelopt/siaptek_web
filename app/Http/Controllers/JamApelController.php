<?php

namespace App\Http\Controllers;

use App\Models\JamApel;
use App\Traits\ResponseStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class JamApelController extends Controller
{

    use ResponseStatus;

    function __construct()
    {
        $this->middleware('can:jam-apel-list', ['only' => ['index', 'show']]);
        $this->middleware('can:jam-apel-create', ['only' => ['create', 'store']]);
        $this->middleware('can:jam-apel-edit', ['only' => ['edit', 'update']]);
        $this->middleware('can:jam-apel-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        if ($request->ajax()) {
            $data = JamApel::all();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $actionBtn = '<div class="btn-group dropend">
                            <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                Aksi
                            </button>
                            <ul class="dropdown-menu">
                               <li><a class="dropdown-item" href="' . route('jam-apel.edit', $row->id) . '">Edit</a></li>
                            </ul>
                          </div>';
                    return $actionBtn;
                })->make();
        }
        return view("contents.jam-apel.index")->with([
            "title" => "Jam Apel",
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
        $data = JamApel::where('id', $id)->first();
        return view('contents.jam-apel.form')->with([
            'title' => 'Edit Jam Apel',
            'method' => 'PUT',
            'action' => route('jam-apel.update', $id),
            'data' => $data
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'jam_apel_pagi' => 'required_without:jam_apel_sore',
            'max_apel_pagi' => 'required_with:jam_apel_pagi',
            'jam_apel_sore' => 'required_without:jam_apel_pagi',
            'max_apel_sore' => 'required_with:jam_apel_sore',
        ], [
            'jam_apel_pagi.required_without' => 'Jam Apel Pagi harus terisi jika Jam Apel Sore tidak diisi.',
            'jam_apel_sore.required_without' => 'Jam Apel Sore harus terisi jika Jam Apel Pagi tidak diisi.',
            'max_apel_pagi.required_with' => 'Maksimal Jam Apel Pagi harus terisi.',
            'max_apel_sore.required_with' => 'Maksimal Jam Apel Sore harus terisi.',
        ]);
        if ($validator->passes()) {
            DB::beginTransaction();
            try {

                $data = JamApel::findOrFail($id);

                $data->update([
                    'jam_apel_pagi' => $request->jam_apel_pagi,
                    'jam_apel_sore' => $request->jam_apel_sore,
                    'max_apel_pagi' => $request->max_apel_pagi,
                    'max_apel_sore' => $request->max_apel_sore
                ]);
                DB::commit();
                $response = response()->json($this->responseStore(true, NULL, route('jam-apel.index')));
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
