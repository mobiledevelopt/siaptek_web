<?php

namespace App\Http\Controllers;

use App\Models\AttendancesPegawai;
use App\Models\Jml_hari_kerja;
use App\Services\Attendance\AttendanceCache;
use App\Traits\ResponseStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
                $bln_thun_explode[0] = (int) $bln_thun_explode[0];
                $bln_thun_explode[1] = (int) $bln_thun_explode[1];
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

                // Clear cache via Service
                AttendanceCache::clearJmlHariKerja($bln_thun_explode[0], $bln_thun_explode[1]);

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
                $bln_thun_explode[0] = (int) $bln_thun_explode[0];
                $bln_thun_explode[1] = (int) $bln_thun_explode[1];
                $cek = Jml_hari_kerja::where('bulan', '=', $bln_thun_explode[0])->where('tahun', '=', $bln_thun_explode[1])->where('id', '!=', $id)->first();

                if ($cek != null) {
                    DB::rollBack();
                    return response()->json(['message' => "Data Sudah Ada"]);
                }
                $data = Jml_hari_kerja::findOrFail($id);
                $data->update([
                    'bulan' => $bln_thun_explode[0],
                    'tahun' => $bln_thun_explode[1],
                    'jml_hari_kerja' => $request->jml_hari_kerja,
                ]);

                //hitung ulang perhitungan presensi
                {
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
                            $get_data_presensi->total_potongan_tpp = (int) round($tunjangan_per_hari);
                            $get_data_presensi->potongan_tidak_masuk_kerja = (int) round($tunjangan_per_hari);
                            $get_data_presensi->potongan_absen_masuk = 0;
                            $get_data_presensi->potongan_absen_pulang = 0;
                            $get_data_presensi->potongan_tidak_apel_pagi = 0;
                            $get_data_presensi->potongan_tidak_apel_sore = 0;
                            $get_data_presensi->potongan_cuti = 0;
                            $get_data_presensi->save();
                        } else {
                            $raw_masuk = 0;
                            $raw_pulang = 0;
                            $raw_tidak_masuk = 0;
                            $raw_apel_pagi = 0;
                            $raw_apel_sore = 0;
                            $raw_cuti = 0;

                            if ((float)$value->potongan_absen_masuk > 0 || (float)$value->menit_telat_masuk > 0) {
                                $persen = $value->potongan_absen_masuk_persen;
                                
                                // Selalu force hitung ulang persen dari config berdasarkan menit aktual untuk memperbaiki data salah
                                if ((float)$value->menit_telat_masuk > 0) {
                                    $menit = (int)$value->menit_telat_masuk;
                                    
                                    // Pindahkan fetch level telat ke luar loop jika memungkinkan, tapi karena ini per baris:
                                    // Gunakan all() yang biasanya di-cache atau minimal pakai collection
                                    $levelTelat = \App\Models\ConfigPotTpp::where('group', 'masuk')->get();
                                    
                                    $match = $levelTelat->first(function ($row) use ($menit) {
                                        return $menit >= (int)$row->dari_meni && $menit <= (int)$row->sampai_menit;
                                    });
                                    
                                    if ($match) {
                                        $persen = $match->persentase_potongan;
                                        $get_data_presensi->status_masuk = $match->title;
                                        $get_data_presensi->config_potongan_tpp_id = $match->id;
                                    } else {
                                        $max = $levelTelat->sortByDesc(function ($row) { return (int)$row->sampai_menit; })->first();
                                        if ($max && $menit > (int)$max->sampai_menit) {
                                            $persen = $max->persentase_potongan;
                                            $get_data_presensi->status_masuk = $max->title;
                                            $get_data_presensi->config_potongan_tpp_id = $max->id;
                                        }
                                    }
                                    $get_data_presensi->potongan_absen_masuk_persen = $persen;
                                }

                                if ($persen > 0) {
                                    $raw_masuk = $tunjangan_per_hari * 40 / 100 * $persen / 100;
                                    $get_data_presensi->potongan_absen_masuk = (int) round($raw_masuk);
                                } else {
                                    $get_data_presensi->potongan_absen_masuk = 0;
                                }
                            } else {
                                $get_data_presensi->potongan_absen_masuk = 0;
                            }

                            if ((float)$value->potongan_absen_pulang > 0 || $value->status_pulang == "Tidak Absen Pulang (PSW)") {
                                $persen_pulang = $value->potongan_absen_pulang_persen > 0
                                    ? $value->potongan_absen_pulang_persen
                                    : \App\Models\ConfigPotTpp::where('group', 'pulang')->value('persentase_potongan') ?? 0;
                                $raw_pulang = $tunjangan_per_hari * 40 / 100 * $persen_pulang / 100;
                                $get_data_presensi->potongan_absen_pulang = (int) round($raw_pulang);
                                $get_data_presensi->potongan_absen_pulang_persen = $persen_pulang;
                            } else {
                                $get_data_presensi->potongan_absen_pulang = 0;
                            }

                            if ((float)$value->potongan_tidak_masuk_kerja > 0) {
                                $raw_tidak_masuk = $tunjangan_per_hari * $value->potongan_tidak_masuk_kerja_persen / 100;
                                $get_data_presensi->potongan_tidak_masuk_kerja = (int) round($raw_tidak_masuk);
                            } else {
                                $get_data_presensi->potongan_tidak_masuk_kerja = 0;
                            }

                            if ((float)$value->potongan_tidak_apel_pagi > 0) {
                                $raw_apel_pagi = $tunjangan_per_hari * 40 / 100 * $value->potongan_tidak_apel_pagi_persen / 100;
                                $get_data_presensi->potongan_tidak_apel_pagi = (int) round($raw_apel_pagi);
                            } else {
                                $get_data_presensi->potongan_tidak_apel_pagi = 0;
                            }

                            if ((float)$value->potongan_tidak_apel_sore > 0) {
                                $raw_apel_sore = $tunjangan_per_hari * 40 / 100 * $value->potongan_tidak_apel_sore_persen / 100;
                                $get_data_presensi->potongan_tidak_apel_sore = (int) round($raw_apel_sore);
                            } else {
                                $get_data_presensi->potongan_tidak_apel_sore = 0;
                            }

                            if ((float)$value->potongan_cuti > 0) {
                                $raw_cuti = $tunjangan_per_hari * $value->potongan_cuti_persen / 100;
                                $get_data_presensi->potongan_cuti = (int) round($raw_cuti);
                            } else {
                                $get_data_presensi->potongan_cuti = 0;
                            }

                            $total_raw = $raw_masuk + $raw_pulang + $raw_tidak_masuk + $raw_apel_pagi + $raw_apel_sore + $raw_cuti;
                            $tpp_diterima = (int) round($tunjangan_per_hari - $total_raw);
                            
                            $get_data_presensi->tunjangan_per_hari = $tunjangan_per_hari;
                            $get_data_presensi->tpp_diterima = $tpp_diterima;
                            $get_data_presensi->total_potongan_tpp = (int) round($total_raw);
                            $get_data_presensi->potongan_tidak_apel = (int) round($raw_apel_pagi + $raw_apel_sore);
                            $get_data_presensi->save();
                        }
                    }
                }

                DB::commit();

                // Clear cache via Service
                AttendanceCache::clearJmlHariKerja($bln_thun_explode[0], $bln_thun_explode[1]);

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
                // Clear cache via Service
                AttendanceCache::clearJmlHariKerja($data->bulan, $data->tahun);
                $response = response()->json($this->responseDelete(true));
            }
            DB::commit();
        } catch (\Throwable $throw) {
            $response = response()->json(['error' => $throw->getMessage()]);
        }
        return $response;
    }
}
