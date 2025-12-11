<?php

namespace App\Http\Controllers;

use App\Models\Pegawai as ModelsPegawai;
use Illuminate\Http\JsonResponse;
use App\Traits\ResponseStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Pegawai extends Controller
{
    use ResponseStatus;

    function __construct()
    {
        $this->middleware('can:pegawai-list', ['only' => ['index', 'show']]);
        $this->middleware('can:pegawai-create', ['only' => ['create', 'store']]);
        $this->middleware('can:pegawai-edit', ['only' => ['edit', 'update']]);
        $this->middleware('can:pegawai-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function Index(Request $request)
    {
        if ($request->ajax()) {
            if ($request->user()->role_id != 1) {
                $data = ModelsPegawai::with(['dinas', 'jenjang_pendidikan'])->where('dinas_id', $request->user()->dinas_id);
            } else {
                $data = ModelsPegawai::with(['dinas', 'jenjang_pendidikan']);
            }

            if ($request->filled('dinas')) {
                $data->where('dinas_id', '=', $request['dinas']);
            }
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $actionBtn = '<div class="btn-group dropend">
                            <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                Aksi
                            </button>
                            <ul class="dropdown-menu">
                               <li><a class="dropdown-item" href="' . route('pegawai.show', $row->id) . '">Lihat Detail</a></li>
                               <li><a class="dropdown-item" href="' . route('pegawai.edit', $row->id) . '">Edit</a></li>
                               <li><a class="dropdown-item btn-delete" href="#" data-id ="' . $row->id . '" >Hapus</a></li>
                            </ul>
                          </div>';
                    return $actionBtn;
                })->make();
        }

        return view("contents.pegawai.index")->with([
            'title' => "Data Pegawai",
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        return view('contents.pegawai.form')->with([
            'title' => 'Tambah Data Pegawai', 'method' => 'POST',
            'action' => route('pegawai.store'),
            "gender" => ['laki-laki', 'perempuan'],
            "agama" => DB::table("religions")->select(['id', 'name'])->get(),
            "jenjang_pendidikan" => DB::table("jenjang_pendidikan")->select(['id', 'name'])->get(),
            "status" => DB::table("marriages")->select(["id", "name"])->get(),
            "pangkat" => DB::table("pangkat_gol")->select(["id", "pangkat", "gol"])->get(),
            "role" => $request->user()->role_id
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $nip_err = ModelsPegawai::where('nip', $request->nip)->first() != null ? ModelsPegawai::where('nip', $request->nip)->first()->name : '';
        $email_err = ModelsPegawai::where('email', $request->email)->first() != null ? ModelsPegawai::where('email', $request->email)->first()->name : '';
        $nik_err = ModelsPegawai::where('id', $request->id)->first() != null ? ModelsPegawai::where('id', $request->id)->first()->name : '';

        $validator = Validator::make($request->all(), [
            'id' => 'required|unique:pegawai',
            'email' => 'required|unique:pegawai',
            'name' => 'required',
            'dinas_id' => 'required',
            'nip' => 'required|unique:pegawai|digits:18',
        ], [
            'id.required' => 'NIK wajib terisi.',
            'id.unique' => 'NIK Sudah Terdaftar dengan nama ' . $nik_err,
            'email.required' => 'Email wajib terisi.',
            'email.unique' => 'Email Sudah Terdaftar dengan nama ' . $email_err,
            'name.required' => 'Nama wajib terisi.',
            'dinas_id' => 'Dinas wajib terisi.',
            'nip' => 'NIP wajib terisi.',
            'nip.unique' => 'NIP Sudah Terdaftar dengan nama ' . $nip_err,
            'nip.digits' => 'NIP wajib 18 digit.',
        ]);

        if ($validator->passes()) {
            DB::beginTransaction();
            try {
                ModelsPegawai::create([
                    "id"                    => str_replace(' ', '', $request->id),
                    "dinas_id"              => $request->dinas_id,
                    "name"                  => $request->name,
                    "jenjang_pendidikan" => $request->jenjang_pendidikan_id,
                    "gelar_depan"                  => $request->gelar_depan,
                    "gelar_belakang"                  => $request->gelar_belakang,
                    "gender"                => $request->gender,
                    "place_of_birth"        => $request->place_of_birth,
                    "date_of_birth"         => $request->date_of_birth,
                    "religion_id"           => $request->religion_id,
                    "marriage_id"           => $request->marriage_id,
                    "email"                 => $request->email,
                    "position_pegawai"      => $request->position_pegawai,
                    "active"                => $request->active,
                    "nip"                   => str_replace(' ', '', $request->nip),
                    "nuptk"                 => str_replace(' ', '', $request->nuptk),
                    "no_hp"                 => $request->no_hp,
                    "sk_cpns"               => $request->sk_cpns,
                    "tgl_cpns"              => $request->tgl_cpns,
                    "sk_pengangkatan"       => $request->sk_pengangkatan,
                    "tmt_pengangkatan"      => $request->tmt_pengangkatan,
                    "status_kepegawaian"    => $request->status_kepegawaian,
                    "pangkat_gol_id"           => $request->pangkat_gol_id,
                    "tmt_pangkat"           => $request->tmt_pangkat,
                    "masa_kerja_tahun"      => $request->masa_kerja_tahun,
                    "masa_kerja_bulan"      => $request->masa_kerja_bulan,
                    "tpp"                   => $request->tpp,
                    "nama_pendidikan" => $request->nama_pendidikan,
                    "thn_lulus_pendidikan" => $request->thn_lulus_pendidikan,
                    "jenjang_pendidikan" => $request->jenjang_pendidikan,
                    "nama_diklat" => $request->nama_diklat,
                    "tgl_diklat" => $request->tgl_diklat,
                    "jam_diklat" => $request->jam_diklat,
                    "password"               => $request->password == null ? password_hash('admin', PASSWORD_BCRYPT) :  password_hash($request->password, PASSWORD_BCRYPT),
                ]);

                DB::commit();
                $response = response()->json($this->responseStore(true, NULL, route('pegawai.index')));
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
        return view('contents.pegawai.detail')
            ->with([
                'title' => 'Detail Pegawai',
                'data' => DB::table('pegawai')
                    ->select([
                        "pegawai.id as nik",
                        "pegawai.name",
                        "pegawai.position_pegawai",
                        "gender",
                        "place_of_birth",
                        "date_of_birth",
                        "email",
                        "no_hp",
                        "nip",
                        "nuptk",
                        "position_pegawai.name as position",
                        "sk_cpns",
                        "gelar_depan",
                        "gelar_belakang",
                        "tgl_cpns",
                        "sk_pengangkatan",
                        "tmt_pengangkatan",
                        "status_kepegawaian",
                        "pangkat_gol_id",
                        "tmt_pangkat",
                        "masa_kerja_tahun",
                        "masa_kerja_bulan",
                        "pangkat_gol.pangkat as pangkat",
                        "pangkat_gol.gol as gol",
                        "dinas.name as dinas",
                        "marriages.name as marriage",
                        "religions.name as religion",
                        "tpp",
                        "nama_pendidikan",
                        "thn_lulus_pendidikan",
                        "jenjang_pendidikan.name as jenjang_pendidikan",
                        "nama_diklat",
                        "tgl_diklat",
                        "jam_diklat"
                    ])
                    ->leftJoin("position_pegawai", "position_pegawai.id", "=", "pegawai.position_pegawai")
                    ->leftJoin("pangkat_gol", "pangkat_gol.id", "=", "pegawai.pangkat_gol_id")
                    ->leftJoin("dinas", "dinas.id", "=", "pegawai.dinas_id")
                    ->leftJoin("marriages", "marriages.id", "=", "pegawai.marriage_id")
                    ->leftJoin("religions", "religions.id", "=", "pegawai.religion_id")
                    ->leftJoin("jenjang_pendidikan", "jenjang_pendidikan.id", "=", "pegawai.jenjang_pendidikan_id")
                    ->where("pegawai.id", $id)
                    ->first()
            ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $id)
    {
        $data = ModelsPegawai::where('id', $id)->first();

        return view('contents.pegawai.form')->with([
            'title' => 'Edit Pegawai',
            'method' => 'PUT',
            'action' => route('pegawai.update', $id),
            'edit' => $data,
            "gender" => ['laki-laki', 'perempuan'],
            "agama" => DB::table("religions")->select(['id', 'name'])->get(),
            "jenjang_pendidikan" => DB::table("jenjang_pendidikan")->select(['id', 'name'])->get(),
            "status" => DB::table("marriages")->select(["id", "name"])->get(),
            "pangkat" => DB::table("pangkat_gol")->select(["id", "pangkat", "gol"])->get(),
            "role" => $request->user()->role_id

        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $nip_err = ModelsPegawai::where('nip', $request->nip)->first() != null ? ModelsPegawai::where('nip', $request->nip)->first()->name : '';
        $email_err = ModelsPegawai::where('email', $request->email)->first() != null ? ModelsPegawai::where('email', $request->email)->first()->name : '';
        $nik_err = ModelsPegawai::where('id', $request->id)->first() != null ? ModelsPegawai::where('id', $request->id)->first()->name : '';

        $validator = Validator::make($request->all(), [
            'email' => [
                'required', Rule::unique('pegawai', 'email')->ignore($id),
            ],
            'id' => [
                'required', Rule::unique('pegawai', 'id')->ignore($id),
            ],
            'name' => 'required',
            'nip' => [
                'required', Rule::unique('pegawai', 'nip')->ignore($id), 'digits:18'
            ]
        ], [
            'id.required' => 'NIK harus terisi.',
            'id.unique' => 'NIK Sudah Terdaftar dengan nama ' . $nik_err,
            'email.required' => 'Email harus terisi.',
            'email.unique' => 'Email Sudah Terdaftar dengan nama ' . $email_err,
            'name.required' => 'Nama harus terisi.',
            'nip' => 'NIP wajib terisi.',
            'nip.unique' => 'NIP Sudah Terdaftar dengan nama ' . $nip_err,
            'nip.digits' => 'NIP wajib 18 digit.',
        ]);

        if ($validator->passes()) {
            DB::beginTransaction();
            try {
                $data_update = [
                    "id"                    => str_replace(' ', '', $request->id),
                    "dinas_id"              => $request->dinas_id,
                    "name"                  => $request->name,
                    "gelar_depan"                  => $request->gelar_depan,
                    "gelar_belakang"                  => $request->gelar_belakang,
                    "jenjang_pendidikan_id" => $request->jenjang_pendidikan_id,
                    "gender"                => $request->gender,
                    "place_of_birth"        => $request->place_of_birth,
                    "date_of_birth"         => $request->date_of_birth,
                    "religion_id"           => $request->religion_id,
                    "marriage_id"           => $request->marriage_id,
                    "email"                 => $request->email,
                    "position_pegawai"      => $request->position_pegawai,
                    "active"                => $request->active,
                    "fake_gps"                => $request->fake_gps ?? 0,
                    "nip"                   => str_replace(' ', '', $request->nip),
                    "nuptk"                 => str_replace(' ', '', $request->nuptk),
                    "no_hp"                 => $request->no_hp,
                    "sk_cpns"               => $request->sk_cpns,
                    "tgl_cpns"              => $request->tgl_cpns,
                    "sk_pengangkatan"       => $request->sk_pengangkatan,
                    "tmt_pengangkatan"      => $request->tmt_pengangkatan,
                    "status_kepegawaian"    => $request->status_kepegawaian,
                    "pangkat_gol_id"           => $request->pangkat_gol_id,
                    "tmt_pangkat"           => $request->tmt_pangkat,
                    "masa_kerja_tahun"      => $request->masa_kerja_tahun,
                    "masa_kerja_bulan"      => $request->masa_kerja_bulan,
                    "tpp"                   => $request->tpp,
                    "nama_pendidikan" => $request->nama_pendidikan,
                    "thn_lulus_pendidikan" => $request->thn_lulus_pendidikan,
                    "jenjang_pendidikan" => $request->jenjang_pendidikan,
                    "nama_diklat" => $request->nama_diklat,
                    "tgl_diklat" => $request->tgl_diklat,
                    "jam_diklat" => $request->jam_diklat,
                ];

                if (!empty($request->password)) {
                    $data_update['password'] = password_hash($request->password, PASSWORD_BCRYPT);
                }

                $data = ModelsPegawai::findOrFail($id);
                $data->update($data_update);

                DB::commit();
                $response = response()->json($this->responseStore(true, NULL, route('pegawai.index')));
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
        $data = ModelsPegawai::find($id);
        DB::beginTransaction();
        try {
            if ($data->delete()) {
                Storage::disk('public')->delete(["images/original/$data->image", "images/thumbnail/$data->image"]);
                $response = response()->json($this->responseDelete(true));
            }
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
             $data = ModelsPegawai::where('dinas_id', $request->user()->dinas_id)->skip($offset)
                ->take($resultCount)
                ->selectRaw('id, name as text')
                ->get();

            $count = ModelsPegawai::where('dinas_id', $request->user()->dinas_id)->where('name', 'LIKE', '%' . $request->q . '%')
                ->get()
                ->count();
        } else {
            $data = ModelsPegawai::where('name', 'LIKE', '%' . $request->q . '%')
                ->orderBy('name')
                ->skip($offset)
                ->take($resultCount)
                ->selectRaw('id, name as text')
                ->get();
            $count = ModelsPegawai::where('name', 'LIKE', '%' . $request->q . '%')
                // ->when(request('jenjang'), function ($q) {
                //     return $q->where('jenjang', \request('jenjang'));
                // })
                ->get()
                ->count();
        }

        // $data = ModelsPegawai::where('name', 'LIKE', '%' . $request->q . '%')
        //     // ->when(request('jenjang'), function ($q) {
        //     //     return $q->where('jenjang', \request('jenjang'));
        //     // })
        //     ->orderBy('name')
        //     ->skip($offset)
        //     ->take($resultCount)
        //     ->selectRaw('id, name as text')
        //     ->get();

        // $count = ModelsPegawai::where('name', 'LIKE', '%' . $request->q . '%')
        //     // ->when(request('jenjang'), function ($q) {
        //     //     return $q->where('jenjang', \request('jenjang'));
        //     // })
        //     ->get()
        //     ->count();

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

    public function export(Request $request)
    {

        $dinas = ($request->dinas) ?: null;

        if ($request->user()->role_id != 1) {
            $data = $this->initData($request->user()->dinas_id);
        } else {
            $data = $this->initData($dinas);
        }

        $url =  $this->generatePegawai($data, $request);
        $attachment = url('/storage/') . '/export_pegawai/' . $url;

        return response()->json(['status' => 'success', 'message' => 'Data berhasil diexport', 'url' => $attachment]);
    }

    protected function initData($id = null)
    {
        $data = ModelsPegawai::with(['dinas', 'jenjang_pendidikan', 'agama', 'status_perkawinan', 'pangkat_gol']);
        if ($id != null) {
            $data->where('dinas_id', $id);
        }

        return $data->get();
    }

    private function generatePegawai($data, $request)
    {
        $styleArray = array(
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        );

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator('Pesisirbaratkab')
            ->setLastModifiedBy('Pesisirbaratkab')
            ->setTitle('Office 2007 XLSX Report Document')
            ->setSubject('Office 2007 XLSX Report Document')
            ->setDescription('Report generator')
            ->setKeywords('office 2007 openxml php')
            ->setCategory('Report File');

        $spreadsheet->getActiveSheet()->setTitle("DATA PEGAWAI");

        $spreadsheet->setActiveSheetIndex(0)->mergeCells("A1:W1");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('A1', 'LAPORAN DATA PEGAWAI');

        $spreadsheet->getActiveSheet()->getStyle('A1:W1')
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1:W1')
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue('A3', 'NO')
            ->setCellValue('B3', 'NIK')
            ->setCellValue('C3', 'NIP')
            ->setCellValue('D3', 'NAMA')
            ->setCellValue('E3', 'DINAS')
            ->setCellValue('F3', 'Jenis Kelamin')
            ->setCellValue('G3', 'Tempat/Tanggal Lahir')
            ->setCellValue('H3', 'Agama')
            ->setCellValue('I3', 'Status Perkawinan')
            ->setCellValue('J3', 'Email')
            ->setCellValue('K3', 'Nomor HP')
            ->setCellValue('L3', 'Jabatan')
            ->setCellValue('M3', 'TMT JABATAN')
            ->setCellValue('N3', 'Pangkat/golongan')
            ->setCellValue('O3', 'TMT Pangkat')
            ->setCellValue('P3', 'Masa Kerja Tahun')
            ->setCellValue('Q3', 'Masa Kerja Bulan')
            ->setCellValue('R3', 'Sekolah / Perguruan Tinggi')
            ->setCellValue('R4', 'Jenjang')
            ->setCellValue('S4', 'Jurusan')
            ->setCellValue('T4', 'Tahun Lulus')
            ->setCellValue('U3', 'Diklat Struktural')
            ->setCellValue('U4', 'Nama Diklat')
            ->setCellValue('V4', 'Tanggal')
            ->setCellValue('W4', 'Jam');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->mergeCells('A3:A4');
        $sheet->mergeCells('B3:B4');
        $sheet->mergeCells('C3:C4');
        $sheet->mergeCells('D3:D4');
        $sheet->mergeCells('E3:E4');
        $sheet->mergeCells('F3:F4');
        $sheet->mergeCells('G3:G4');
        $sheet->mergeCells('H3:H4');
        $sheet->mergeCells('I3:I4');
        $sheet->mergeCells('J3:J4');
        $sheet->mergeCells('K3:K4');
        $sheet->mergeCells('L3:L4');
        $sheet->mergeCells('M3:M4');
        $sheet->mergeCells('N3:N4');
        $sheet->mergeCells('O3:O4');
        $sheet->mergeCells('P3:P4');
        $sheet->mergeCells('Q3:Q4');
        $sheet->mergeCells('R3:T3');
        $sheet->mergeCells('U3:W3');

        $sheet->getStyle('A3:W4')->applyFromArray($styleArray);

        $spreadsheet->getActiveSheet()->getStyle('A3:W4')
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A3:W4')
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (range('A', 'W') as $columnID) {
            $sheet->getColumnDimension($columnID)
                ->setAutoSize(true);
        }
        $col_start = 5;

        $count = 0;
        $i = 4;
        foreach ($data as $val) {
            $i++;
            $count++;
            $nama_pegawai = ($val->gelar_depan == null ? '' : $val->gelar_depan . '. ') . $val->name . ($val->gelar_belakang == null ? '' : ', ' . $val->gelar_belakang);
            $spreadsheet->setActiveSheetIndex(0)
                ->setCellValue('A' . $i, $count)
                ->setCellValue('B' . $i, "'" . $val->id)
                ->setCellValue('C' . $i, "'" . $val->nip)
                ->setCellValue('D' . $i, $nama_pegawai)
                ->setCellValue('E' . $i, $val->dinas->name ?? null)
                ->setCellValue('F' . $i, $val->gender)
                ->setCellValue('G' . $i, $val->place_of_birth . ' / ' . $val->date_of_birth)
                ->setCellValue('H' . $i, $val->agama->name ?? null)
                ->setCellValue('I' . $i, $val->status_perkawinan->name ?? null)
                ->setCellValue('J' . $i, $val->email)
                ->setCellValue('K' . $i, "'" . $val->no_hp)
                ->setCellValue('L' . $i, $val->position_pegawai)
                ->setCellValue('M' . $i, $val->tmt_pengangkatan)
                ->setCellValue('N' . $i, ($val->pangkat_gol->pangkat ?? null) . ' ' . ($val->pangkat_gol->gol ?? null))
                ->setCellValue('O' . $i, $val->tmt_pangkat)
                ->setCellValue('P' . $i, $val->masa_kerja_tahun)
                ->setCellValue('Q' . $i, $val->masa_kerja_bulan)
                ->setCellValue('R' . $i, $val->jenjang_pendidikan->name ?? null)
                ->setCellValue('S' . $i, $val->jenjang_pendidikan)
                ->setCellValue('T' . $i, $val->thn_lulus_pendidikan)
                ->setCellValue('U' . $i, $val->nama_diklat)
                ->setCellValue('V' . $i, $val->tgl_diklat)
                ->setCellValue('W' . $i, $val->jam_diklat);
        }
        $i += 2;

        $spreadsheet->getActiveSheet()->getStyle('B4:B' . $i)->setQuotePrefix(true);
        $spreadsheet->getActiveSheet()->getStyle('C4:C' . $i)->setQuotePrefix(true);
        $spreadsheet->getActiveSheet()->getStyle('K4:K' . $i)->setQuotePrefix(true);

        $sheet->getStyle('A4:W' . $i)->applyFromArray($styleArray);

        $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);
        $protection = $spreadsheet->getActiveSheet()->getProtection();
        $protection->setPassword("ABCDEFGHIJKLMNOPQRSTUVWX");
        $protection->setSheet(true);

        $filename = 'laporan-data-pegawai_' . time() . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save(storage_path('app/public/export_pegawai/' . $filename));
        return $filename;
    }
}
