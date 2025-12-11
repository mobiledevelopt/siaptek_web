<?php

namespace App\Http\Controllers;

use App\Helpers\FileUpload;
use App\Models\Pengumuman as ModelsPengumuman;
use App\Traits\ResponseStatus;
use Illuminate\Support\Facades\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as FacadesFile;
use Illuminate\View\View;
use Intervention\Image\File as ImageFile;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\Upload;
use Illuminate\Support\Facades\Validator;
use Kutia\Larafirebase\Facades\Larafirebase;

use function PHPSTORM_META\type;

class Pengumuman extends Controller
{
    use Upload, ResponseStatus;
    private $deviceTokens = ['{TOKEN_1}', '{TOKEN_2}'];

    function __construct()
    {
        $this->middleware('can:pengumuman-list', ['only' => ['index', 'show']]);
        $this->middleware('can:pengumuman-create', ['only' => ['create', 'store']]);
        $this->middleware('can:pengumuman-edit', ['only' => ['edit', 'update']]);
        $this->middleware('can:pengumuman-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = ModelsPengumuman::all();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $actionBtn = '<div class="btn-group dropend">
                            <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                Aksi
                            </button>
                            <ul class="dropdown-menu">
                               <li><a class="dropdown-item" href="' . route('web-pengumuman.edit', $row->id) . '">Edit</a></li>
                               <li><a class="dropdown-item btn-delete" href="#" data-id ="' . $row->id . '" >Hapus</a></li>
                            </ul>
                          </div>';
                    return $actionBtn;
                })->make();
        }
        return view("contents.pengumuman.index")->with([
            "title" => "Data Pengumuman"
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('contents.pengumuman.form')->with([
            'title' => 'Tambah Pengumuman',
            'method' => 'POST',
            'action' => route('web-pengumuman.store')
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
            'desc' => 'required',
        ], [
            'tgl.required' => 'Tanggal harus terisi.',
            'title.required' => 'Judul harus terisi.',
            'desc.required' => 'Deskripsi harus terisi.',
        ]);
        if ($validator->passes()) {
            DB::beginTransaction();
            try {

                // $cek = ModelsPengumuman::where([
                //     'tgl' => $request->tgl,
                // ])->first();

                // if ($cek != null) {
                //     DB::rollBack();
                //     return response()->json(['message' => "Data Sudah Ada"]);
                // }

                $data_pengumuman = [
                    'tgl' => $request->tgl,
                    'title' => $request->title,
                    'desc' => $request->desc,
                ];

                if ($request->hasFile('attachment')) {
                    $path = $this->UploadFile($request->file('attachment'), 'lampiran_pengumuman');
                    $attachment = url('/storage/') . '/' . $path;
                    $data_pengumuman['path'] = $path;
                    $data_pengumuman['attachment'] = $attachment;
                }

                if ($request->user()->role_id != 1) {
                    $data_pengumuman['dinas_id'] = $request->user()->dinas_id;
                }

                ModelsPengumuman::create($data_pengumuman);

                DB::commit();
                $response = response()->json($this->responseStore(true, NULL, route('web-pengumuman.index')));
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
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // $data = Apel::where('id', $id)->first();
        return view('contents.pengumuman.form')->with([
            'title' => 'Edit Jadwal Apel',
            'method' => 'PUT',
            'action' => route('web-pengumuman.update', $id),
            'edit' => ModelsPengumuman::find($id)
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
            'desc' => 'required',
        ], [
            'tgl.required' => 'Tanggal harus terisi.',
            'title.required' => 'Judul harus terisi.',
            'desc.required' => 'Deskripsi harus terisi.',
        ]);

        if ($validator->passes()) {
            DB::beginTransaction();
            try {

                $cek = ModelsPengumuman::where('tgl', $request->tgl)->where('id', '!=', $id)->first();

                if ($cek != null) {
                    DB::rollBack();
                    return response()->json(['message' => "Data Sudah Ada"]);
                }

                $data_pengumuman = [
                    'tgl' => $request->tgl,
                    'title' => $request->title,
                    'desc' => $request->desc,
                ];

                $path = null;
                $attachment = null;
                if ($request->hasFile('attachment')) {
                    $path = $this->UploadFile($request->file('attachment'), 'lampiran_pengumuman');
                    $attachment = url('/storage/') . '/' . $path;
                    $data_pengumuman['path'] = $path;
                    $data_pengumuman['attachment'] = $attachment;
                }

                if ($request->user()->role_id != 1) {
                    $data_pengumuman['dinas_id'] = $request->user()->dinas_id;
                }

                $data = ModelsPengumuman::findOrFail($id);
                if ($data->path != null && $path != null) {
                    File::delete(public_path('storage/' . $data->path));
                }
                $data->update($data_pengumuman);
                DB::commit();
                $response = response()->json($this->responseStore(true, NULL, route('web-pengumuman.index')));
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
        $data = ModelsPengumuman::find($id);
        DB::beginTransaction();
        try {
            if ($data->delete()) {
                File::delete(public_path('storage/' . $data->path));
                $response = response()->json($this->responseDelete(true));
            }
            DB::commit();
        } catch (\Throwable $throw) {
            $response = response()->json(['error' => $throw->getMessage()]);
        }
        return $response;
    }

    public function sendMessage()
    {
        return Larafirebase::withTitle('Test Title')
            ->withBody('Test body')
            ->sendMessage($this->deviceTokens);
        return Larafirebase::fromArray(['title' => 'Test Title', 'body' => 'Test body'])->sendMessage($this->deviceTokens);
    }
}
