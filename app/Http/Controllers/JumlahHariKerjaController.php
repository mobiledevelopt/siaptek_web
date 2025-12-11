<?php

namespace App\Http\Controllers;

use App\Models\AttendancesPegawai;
use App\Models\Jml_hari_kerja;
use App\Traits\ResponseStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class JumlahHariKerjaController extends Controller
{
    use ResponseStatus;

    function __construct()
    {
        $this->middleware('can:jumlah-hari-kerja-list', ['only' => ['index', 'show']]);
        $this->middleware('can:jumlah-hari-kerja-create', ['only' => ['create', 'store']]);
        $this->middleware('can:jumlah-hari-kerja-edit', ['only' => ['edit', 'update']]);
        $this->middleware('can:jumlah-hari-kerja-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Jml_hari_kerja::all();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $actionBtn = '<div class="btn-group dropend">
                            <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                Aksi
                            </button>
                            <ul class="dropdown-menu">
                               <li><a class="dropdown-item" href="' . route('jumlah-hari-kerja.edit', $row->id) . '">Edit</a></li>
                               <li><a class="dropdown-item btn-delete" href="#" data-id ="' . $row->id . '" >Hapus</a></li>
                            </ul>
                          </div>';
                    return $actionBtn;
                })->make();
        }
        return view("contents.jml_hari_kerja.index")->with([
            "title" => "Jumlah Hari Kerja",
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('contents.jml_hari_kerja.form')->with([
            'title' => 'Tambah Jumlah Hari Kerja', 'method' => 'POST',
            'action' => route('jumlah-hari-kerja.store')
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bln' => 'required',
            'jml_hari_kerja' => 'required',
        ], [
            'bln.required' => 'Bulan Tahun harus terisi.',
            'jml_hari_kerja.required' => 'Jumlah Hari Kerja harus terisi.',
        ]);
        if ($validator->passes()) {
            DB::beginTransaction();
            try {
                $bln_thun_explode = explode('-', $request->bln);
                $cek = Jml_hari_kerja::where([
                    'bulan' => $bln_thun_explode[0],
                    'tahun' => $bln_thun_explode[1]
                ])->first();

                if ($cek != null) {
                    DB::rollBack();
                    return response()->json(['message' => "Data Sudah Ada"]);
                }

                Jml_hari_kerja::create([
                    'bulan' => $bln_thun_explode[0],
                    'tahun' => $bln_thun_explode[1],
                    'jml_hari_kerja' => $request->jml_hari_kerja,
                ]);
                DB::commit();
                $response = response()->json($this->responseStore(true, NULL, route('jumlah-hari-kerja.index')));
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
        $data = Jml_hari_kerja::where('id', $id)->first();
        return view('contents.jml_hari_kerja.form')->with([
            'title' => 'Edit Jumlah Hari Kerja',
            'method' => 'PUT',
            'action' => route('jumlah-hari-kerja.update', $id),
            'data' => $data
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'bln' => 'required',
            'jml_hari_kerja' => 'required',
        ], [
            'bln.required' => 'Bulan Tahun harus terisi.',
            'jml_hari_kerja.required' => 'Jumlah Hari Kerja harus terisi.',
        ]);
        if ($validator->passes()) {
            DB::beginTransaction();
            try {
                $bln_thun_explode = explode('-', $request->bln);
                $cek = Jml_hari_kerja::where('bulan', '=', $bln_thun_explode[0])->where('tahun', '=', $bln_thun_explode[1])->where('id', '!=', $id)->first();

                if ($cek != null) {
                    DB::rollBack();
                    return response()->json(['message' => "Data Sudah Ada"]);
                }
                $data = Jml_hari_kerja::findOrFail($id);
                $cek_perubahan_jml_hari = $request->jml_hari_kerja != $data->jml_hari_kerja;
                $data->update([
                    'bulan' => $bln_thun_explode[0],
                    'tahun' => $bln_thun_explode[1],
                    'jml_hari_kerja' => $request->jml_hari_kerja,
                ]);

                //hitung ulang perhitungan persensi jika jml hari berubah
                if ($cek_perubahan_jml_hari) {
                    $data_presensi = AttendancesPegawai::with(['pegawai'])->whereYear('date_attendance', $bln_thun_explode[1])->whereMonth('date_attendance', $bln_thun_explode[0])->get();
                    foreach ($data_presensi as $value) {

                        $get_data_presensi = AttendancesPegawai::find($value->id);
                        $total_potongan_tpp = 0;
                        $potongan_tidak_apel = 0;
                        $tpp_diterima = 0;
                        $potongan_absen_masuk = 0;
                        $potongan_absen_pulang = 0;
                        $potongan_tidak_masuk_kerja = 0;
                        $potongan_tidak_apel_pagi = 0;
                        $potongan_tidak_apel_sore = 0;
                        $potongan_cuti = 0;

                        // hitung tpp /hari
                        $tpp_pegawai = $value->pegawai->tpp;
                        $tunjangan_per_hari = $tpp_pegawai / $request->jml_hari_kerja;
                        if ($value->ket_tidak_masuk_kerja == "Tanpa Keterangan") {
                            $tpp_diterima = 0;
                            $get_data_presensi->tunjangan_per_hari = $tunjangan_per_hari;
                            $get_data_presensi->tpp_diterima = $tpp_diterima;
                            $get_data_presensi->total_potongan_tpp = $tunjangan_per_hari;
                            $get_data_presensi->potongan_tidak_masuk_kerja = $tunjangan_per_hari;
                            $get_data_presensi->potongan_absen_masuk = $potongan_absen_masuk;
                            $get_data_presensi->potongan_absen_pulang = $potongan_absen_pulang;
                            $get_data_presensi->potongan_tidak_apel_pagi = $potongan_tidak_apel_pagi;
                            $get_data_presensi->potongan_tidak_apel_sore = $potongan_tidak_apel_sore;
                            $get_data_presensi->potongan_cuti = $potongan_cuti;
                            $get_data_presensi->save();
                        } else {
                            if ((int)$value->potongan_absen_masuk > 0) {
                                $potongan_absen_masuk = $tunjangan_per_hari * 40 / 100 * $value->potongan_absen_masuk_persen / 100;
                                $get_data_presensi->potongan_absen_masuk = $potongan_absen_masuk;
                                $total_potongan_tpp += $potongan_absen_masuk;
                            } else {
                                $get_data_presensi->potongan_absen_masuk = $potongan_absen_masuk;
                            }

                            if ((int)$value->potongan_absen_pulang > 0) {
                                $potongan_absen_pulang = $tunjangan_per_hari * 40 / 100 * $value->potongan_absen_pulang_persen / 100;
                                $get_data_presensi->potongan_absen_pulang = $potongan_absen_pulang;
                                $total_potongan_tpp += $potongan_absen_pulang;
                            } else {
                                $get_data_presensi->potongan_absen_pulang = $potongan_absen_pulang;
                            }

                            if ((int)$value->potongan_tidak_masuk_kerja > 0) {
                                $potongan_tidak_masuk_kerja = $tunjangan_per_hari * $value->potongan_tidak_masuk_kerja_persen / 100;
                                $get_data_presensi->potongan_tidak_masuk_kerja = $potongan_tidak_masuk_kerja;
                                $total_potongan_tpp += $potongan_tidak_masuk_kerja;
                            } else {
                                $get_data_presensi->potongan_tidak_masuk_kerja = $potongan_tidak_masuk_kerja;
                            }

                            if ((int)$value->potongan_tidak_apel_pagi > 0) {
                                $potongan_tidak_apel_pagi = $tunjangan_per_hari * 40 / 100 * $value->potongan_tidak_apel_pagi_persen / 100;
                                $get_data_presensi->potongan_tidak_apel_pagi = $potongan_tidak_apel_pagi;
                                $total_potongan_tpp += $potongan_tidak_apel_pagi;
                                $potongan_tidak_apel += $potongan_tidak_apel_pagi;
                            } else {
                                $get_data_presensi->potongan_tidak_apel_pagi = $potongan_tidak_apel_pagi;
                            }

                            if ((int)$value->potongan_tidak_apel_sore > 0) {
                                $potongan_tidak_apel_sore = $tunjangan_per_hari * 40 / 100 * $value->potongan_tidak_apel_sore_persen / 100;
                                $get_data_presensi->potongan_tidak_apel_sore = $potongan_tidak_apel_sore;
                                $total_potongan_tpp += $potongan_tidak_apel_sore;
                                $potongan_tidak_apel += $potongan_tidak_apel_sore;
                            } else {
                                $get_data_presensi->potongan_tidak_apel_sore = $potongan_tidak_apel_sore;
                            }

                            if ((int)$value->potongan_cuti > 0) {
                                $potongan_cuti = $tunjangan_per_hari * 40 / 100 * $value->potongan_cuti_persen / 100;
                                $get_data_presensi->potongan_cuti = $potongan_cuti;
                                $total_potongan_tpp += $potongan_cuti;
                            } else {
                                $get_data_presensi->potongan_cuti = $potongan_cuti;
                            }

                            $tpp_diterima = $tunjangan_per_hari - $total_potongan_tpp;
                            $get_data_presensi->tunjangan_per_hari = $tunjangan_per_hari;
                            $get_data_presensi->tpp_diterima = $tpp_diterima;
                            $get_data_presensi->total_potongan_tpp = $total_potongan_tpp;
                            $get_data_presensi->potongan_tidak_apel = $potongan_tidak_apel;
                            $get_data_presensi->save();
                        }
                    }
                }

                DB::commit();
                $response = response()->json($this->responseStore(true, NULL, route('jumlah-hari-kerja.index')));
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
        $data = Jml_hari_kerja::find($id);
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
