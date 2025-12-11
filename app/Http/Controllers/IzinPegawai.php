<?php

namespace App\Http\Controllers;

use App\Models\AttendancesPegawai;
use App\Models\IzinPegawai as ModelsIzinPegawai;
use App\Models\Jml_hari_kerja;
use App\Traits\ResponseStatus;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\RedirectResponse;
use App\Models\KalendarLibur;

class IzinPegawai extends Controller
{

    use ResponseStatus;

    function __construct()
    {
        $this->middleware('can:web-izin-list', ['only' => ['index', 'show']]);
        $this->middleware('can:web-izin-create', ['only' => ['create', 'store']]);
        $this->middleware('can:web-izin-edit', ['only' => ['edit', 'update']]);
        $this->middleware('can:web-izin-delete', ['only' => ['destroy']]);
    }

    public function Index(Request $request)
    {
        $start = ($request->dari) ?: date('Y-m' . '-01');
        $end = ($request->hingga) ?: date('Y-m-d');

        if ($request->status) {
            $status = $request['status'];
        } else {
            $status = null;
        }

        if ($request->ajax()) {
            $data = ModelsIzinPegawai::with(['jenis_izin', 'pegawai_', 'dinas']);

            if ($request->user()->role_id != 1) {
                $data = ModelsIzinPegawai::where('dinas_id', $request->user()->dinas_id)->with(['jenis_izin', 'pegawai_', 'dinas']);
            } else {
                $data = ModelsIzinPegawai::with(['jenis_izin', 'pegawai_', 'dinas']);
            }

            if ($request->filled('tgl_awal')) {
                $data->whereDate('tgl', '>=', $request['tgl_awal']);
            }
            if ($request->filled('tgl_akhir')) {
                $data->whereDate('tgl', '<=', $request['tgl_akhir']);
            }
            if ($request->filled('dinas')) {
                $data->where('dinas_id', '=', $request['dinas']);
            }

            if ($request->filled('status')) {
                $data->where('status', '=', $request['status']);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $actionBtn = '<div class="btn-group dropend">
                            <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                Aksi
                            </button>
                            <ul class="dropdown-menu">
                               <li><a class="dropdown-item" href="' .  route('web-izin.show', $row->id) . '">Lihat Detail</a></li>
                            </ul>
                          </div>';
                    return $actionBtn;
                })->make();
        }
        return view("contents.izin.pegawai.index")->with([
            "title" => "Data Izin Pegawai",
            'start' => $start,
            'end' => $end,
            'status' => $status,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        return view("contents.izin.pegawai.detail")->with([
            "title" => "Detail Izin",
            'method' => 'PUT',
            'role' => $request->user()->role_id,
            'action' => route('web-izin.update', $id),
            "detail" => DB::table("izin_pegawai")
                ->select([
                    'izin_pegawai.id as id',
                    'config_potongan_tpp.title as jenis',
                    'dinas.name as dinas',
                    'tgl as date',
                    'sampai_tgl as sampai',
                    'pegawai.name as pegawai',
                    'desc',
                    'attachment',
                    'status',
                    'alasan_ditolak'
                ])
                ->join("pegawai", "pegawai.id", "=", "izin_pegawai.pegawai_id")
                ->join("dinas", "dinas.id", "=", "pegawai.dinas_id")
                ->join("config_potongan_tpp", "config_potongan_tpp.id", "=", "izin_pegawai.jenis_izin_id")
                ->where("izin_pegawai.id", "=", $id)
                ->first()
        ]);
    }

    public function update(Request $request, $id)
    {

        DB::beginTransaction();
        try {
            $data = $request->input();
            $dataIzin = ModelsIzinPegawai::with(['jenis_izin', 'pegawai_'])->where("id", $data['id'])->first();
            ModelsIzinPegawai::where("id", $data['id'])
                ->update([
                    "status" => $data['status'],
                    "alasan_ditolak" => $data["status_note"]
                ]);

            if ($data['status'] === "Di Terima") {

                $begin = new DateTime($dataIzin->tgl);
                $end = new DateTime(date("Y-m-d", strtotime($dataIzin->sampai_tgl . "+1 days")));

                $interval = DateInterval::createFromDateString('1 day');
                $period = new DatePeriod($begin, $interval, $end);

                foreach ($period as $dt) {
                    $jml_hari_kerja = Jml_hari_kerja::where(['bulan' => $dt->format("m"), 'tahun' => $dt->format("Y")])->first();
                    $hari_libur = KalendarLibur::where('tgl', $dt->format("Y-m-d"))->first();

                    if ($jml_hari_kerja == null) {
                        DB::rollBack();
                        return response()->json(['message' => "Jumlah Hari Kerja Bulan " . $dt->format("F") . " Tahun " . $dt->format("Y") . " Belum Di Input"]);
                    }

                    if ($dt->format('w') == 0 || $dt->format('w') == 6) {
                        // hari libur sabtu,minggu
                    } elseif ($hari_libur != null) {
                        // kalender libur
                    } else {

                        $tunjangan_per_hari = $dataIzin->pegawai_->tpp / $jml_hari_kerja->jml_hari_kerja;
                        $potongan_tpp = $tunjangan_per_hari * 40 / 100 * $dataIzin->jenis_izin->persentase_potongan / 100;
                        $total_potongan_tpp = $potongan_tpp;
                        $tpp_diterima = $tunjangan_per_hari - $total_potongan_tpp;

                        if ($dataIzin->jenis_izin->group === "izin") {
                            AttendancesPegawai::updateOrCreate(
                                ['pegawai_id' => $dataIzin->pegawai_id, 'dinas_id' => $dataIzin->dinas_id, 'date_attendance' => $dt->format("Y-m-d")],
                                [
                                    'incoming_time' => "00:00:00",
                                    'outgoing_time' => "00:00:00",
                                    'status' => $dataIzin->jenis_izin->group,
                                    'potongan_tidak_masuk_kerja' => $potongan_tpp,
                                    'potongan_tidak_masuk_kerja_persen' => $dataIzin->jenis_izin->persentase_potongan,
                                    'ket_tidak_masuk_kerja' => $dataIzin->jenis_izin->title,
                                    'tunjangan_per_hari' => $tunjangan_per_hari,
                                    'config_potongan_tpp_id' => $dataIzin->jenis_izin_id,
                                    'tpp_diterima' => $tpp_diterima,
                                    'total_potongan_tpp' => $total_potongan_tpp,
                                    'menit_telat_masuk' => 0,
                                    'potongan_absen_masuk' => 0,
                                    'potongan_absen_pulang' => 0,
                                    'potongan_tidak_apel' => 0,
                                    'status_masuk' => null,
                                    'status_pulang' => null,
                                    'potongan_absen_masuk_persen' => 0,
                                    'potongan_absen_pulang_persen' => 0,
                                    'potongan_tidak_apel_persen' => 0,
                                    'potongan_cuti_persen' => 0,
                                    'potongan_cuti' => 0,
                                    'ket_cuti' => null,
                                    'foto_absen_masuk_path' => null,
                                    'foto_absen_masuk' => null,
                                    'foto_absen_pulang_path' => null,
                                    'foto_absen_pulang' => null,
                                    'status_apel' => null,
                                    'status_apel_pagi' => null,
                                    'potongan_tidak_apel_pagi' => 0,
                                    'potongan_tidak_apel_pagi_persen' => 0,
                                    'foto_apel_pagi_path' => null,
                                    'foto_apel_pagi' => null,
                                    'status_apel_sore' => null,
                                    'potongan_tidak_apel_sore' => 0,
                                    'potongan_tidak_apel_sore_persen' => 0,
                                    'foto_apel_sore_path' => null,
                                    'foto_apel_sore' => null
                                ]
                            );
                        } else {
                            AttendancesPegawai::updateOrCreate(
                                ['pegawai_id' => $dataIzin->pegawai_id, 'dinas_id' => $dataIzin->dinas_id, 'date_attendance' => $dt->format("Y-m-d")],
                                [
                                    'incoming_time' => "00:00:00",
                                    'outgoing_time' => "00:00:00",
                                    'status' => $dataIzin->jenis_izin->group,
                                    'potongan_cuti' => $potongan_tpp,
                                    'potongan_cuti_persen' => $dataIzin->jenis_izin->persentase_potongan,
                                    'ket_cuti' => $dataIzin->jenis_izin->title,
                                    'tunjangan_per_hari' => $tunjangan_per_hari,
                                    'config_potongan_tpp_id' => $dataIzin->jenis_izin_id,
                                    'tpp_diterima' => $tpp_diterima,
                                    'total_potongan_tpp' => $total_potongan_tpp,
                                    'menit_telat_masuk' => 0,
                                    'potongan_absen_masuk' => 0,
                                    'potongan_absen_pulang' => 0,
                                    'potongan_tidak_apel' => 0,
                                    'status_masuk' => null,
                                    'status_pulang' => null,
                                    'potongan_absen_masuk_persen' => 0,
                                    'potongan_absen_pulang_persen' => 0,
                                    'potongan_tidak_apel_persen' => 0,
                                    'potongan_tidak_masuk_kerja' => 0,
                                    'potongan_tidak_masuk_kerja_persen' => 0,
                                    'ket_tidak_masuk_kerja' => null,
                                    'foto_absen_masuk_path' => null,
                                    'foto_absen_masuk' => null,
                                    'foto_absen_pulang_path' => null,
                                    'foto_absen_pulang' => null,
                                    'status_apel' => null,
                                    'status_apel_pagi' => null,
                                    'potongan_tidak_apel_pagi' => 0,
                                    'potongan_tidak_apel_pagi_persen' => 0,
                                    'foto_apel_pagi_path' => null,
                                    'foto_apel_pagi' => null,
                                    'status_apel_sore' => null,
                                    'potongan_tidak_apel_sore' => 0,
                                    'potongan_tidak_apel_sore_persen' => 0,
                                    'foto_apel_sore_path' => null,
                                    'foto_apel_sore' => null
                                ]
                            );
                        }
                    }
                }
            }
            DB::commit();
            $response = response()->json($this->responseStore(true, NULL, route('web-izin.index')));
        } catch (\Throwable $throw) {
            DB::rollBack();
            $response = response()->json(['error' => $throw->getMessage()]);
        }

        return $response;
    }
}
