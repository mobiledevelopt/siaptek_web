<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ResponseStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class AdminDinas extends Controller
{
    use ResponseStatus;

    function __construct()
    {
        $this->middleware('can:admin-list', ['only' => ['index', 'show']]);
        $this->middleware('can:admin-create', ['only' => ['create', 'store']]);
        $this->middleware('can:admin-edit', ['only' => ['edit', 'update']]);
        $this->middleware('can:admin-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {

        if ($request->ajax()) {
            $data = User::with(['dinas', 'roles']);

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $actionBtn = '<div class="btn-group dropend">
                            <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                Aksi
                            </button>
                            <ul class="dropdown-menu">
                               <li><a class="dropdown-item" href="' . route('admin.edit', $row->id) . '">Edit</a></li>
                               <li><a class="dropdown-item btn-delete" href="#" data-id ="' . $row->id . '" >Hapus</a></li>
                            </ul>
                          </div>';
                    return $actionBtn;
                })->make();
        }
        return view("contents.admin-dinas.index")->with([
            "title" => "Admin Dinas",
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('contents.admin-dinas.form')->with([
            'title' => 'Tambah Data Admin Dinas', 'method' => 'POST',
            'action' => route('admin.store')
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required',
            'password' => 'required',
            'role_id' => 'required'
        ], [
            'name.required' => 'Nama Admin harus terisi.',
            'email.required' => 'Email harus terisi.',
            'password.required' => 'Password harus terisi.',
            'role_id.required' => 'Role harus terisi.',
        ]);
        if ($validator->passes()) {
            DB::beginTransaction();
            try {
                User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role_id' => $request->role_id,
                    'dinas_id' => $request->dinas_id,
                    'active' => $request->active ?? 2

                ]);

                DB::commit();
                $response = response()->json($this->responseStore(true, NULL, route('admin.index')));
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
        $data = User::with(['dinas', 'roles'])->where('id', $id)->first();
        return view('contents.admin-dinas.form')->with([
            'title' => 'Edit Admin Dinas',
            'method' => 'PUT',
            'action' => route('admin.update', $id),
            'edit' => $data
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required',
        ], [
            'name.required' => 'Nama harus terisi.',
            'email.required' => 'Email harus terisi.',
        ]);
        if ($validator->passes()) {
            DB::beginTransaction();
            try {

                $data = User::findOrFail($id);
                $field_user = array(
                    'dinas_id' => $request->dinas_id,
                    'role_id' => $request->role_id,
                    'name' => $request->name,
                    'email' => $request->email,
                    'active' => $request->active ?? 2
                );

                if ($request->password) {
                    $field_user['password'] = Hash::make($request->password);
                }

                $data->update($field_user);

                DB::commit();
                $response = response()->json($this->responseStore(true, NULL, route('admin.index')));
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
        $data = User::find($id);
        DB::beginTransaction();
        try {
            if ($data->delete()) {
                $response = response()->json($this->responseDelete(true));
            }
            DB::commit();
        } catch (\Throwable $throw) {
            $response = response()->json(['error' => $throw->getMessage()]);
        }
        return $response;
    }
}
