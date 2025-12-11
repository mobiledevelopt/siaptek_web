<?php

namespace App\Console\Commands;

use App\Models\Apel;
use App\Models\AttendancesPegawai;
use App\Models\ConfigPotTpp;
use App\Models\JadwalApel;
use App\Models\JamAbsen;
use App\Models\Jml_hari_kerja;
use App\Models\KalendarLibur;
use App\Models\Pegawai;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AbsenCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'absen:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        Log::info("run cron ");

        //cek hari sabtu / minggu
        if (date('w') == 0 || date('w') == 6) {
            Log::info("Hari Libur " . date('Y-m-d H:i:s'));
            return;
        }

        //cek kalender libur
        $hari_libur = KalendarLibur::where('tgl', date('Y-m-d'))->first();

        if ($hari_libur != null) {
            Log::info("Hari Libur " . $hari_libur->desc);
            return;
        }

        $jml_hari_kerja = Jml_hari_kerja::where(['bulan' => date('m'), 'tahun' => date('Y')])->first();
        if ($jml_hari_kerja == null) {
            Log::info("Jumlah Hari Kerja Belum Di Input " . date('Y-m-d H:i:s'));
            return;
        }

        //update presensi
        Pegawai::where('active', 1)->chunk(2000, function ($user) {
            foreach ($user as $item) {
                $dataAttendancesPegawai = [];
                $jml_hari_kerja = Jml_hari_kerja::where(['bulan' => date('m'), 'tahun' => date('Y')])->first();
                $tunjangan_per_hari = $item->tpp / $jml_hari_kerja->jml_hari_kerja;
                $total_potongan_tpp = $tunjangan_per_hari;
                $tpp_diterima = $tunjangan_per_hari - $total_potongan_tpp;
                $jam_kosong = "00:00:00";
                $tgl = date('Y-m-d');
                $cek_absen = AttendancesPegawai::where(['pegawai_id' => $item->id, 'dinas_id' => $item->dinas_id, 'date_attendance' => $tgl])->first();

                $configTpp = ConfigPotTpp::all();
                if ($cek_absen === null || $cek_absen->incoming_time === null) {
                    Log::info("alpha id " . $item->id . " " . $item->name);
                    //tidak masuk
                    AttendancesPegawai::create([
                        'dinas_id' => $item->dinas_id,
                        'pegawai_id' => $item->id,
                        'date_attendance' => $tgl,
                        'incoming_time' => $jam_kosong,
                        'outgoing_time' => $jam_kosong,
                        'status' => "Tidak Masuk",
                        'tunjangan_per_hari' => $tunjangan_per_hari,
                        'config_potongan_tpp_id' => $configTpp[13]['id'],
                        'tpp_diterima' => $tpp_diterima,
                        'total_potongan_tpp' => $total_potongan_tpp,
                        'ket_tidak_masuk_kerja' => $configTpp[13]['title'],
                        'potongan_tidak_masuk_kerja_persen' => $configTpp[13]['persentase_potongan'],
                        'potongan_tidak_masuk_kerja' => $total_potongan_tpp
                    ]);
                } elseif ($cek_absen->ket_cuti === null && $cek_absen->ket_tidak_masuk_kerja === null) {
                    Log::info("tidak absen pulang id " . $item->id . " " . $item->name);
                    $potongan_tpp = 0;
                    $potongan_tpp_apel = 0;
                    $potongan_apel_persen = 0;
                    $status_apel_pagi = null;
                    $potongan_tpp_apel_pagi = 0;
                    $potongan_apel_pagi_persen = 0;
                    $status_apel_sore = null;
                    $potongan_tpp_apel_sore = 0;
                    $potongan_apel_sore_persen = 0;

                    //tidak absen pulang
                    if ($cek_absen->status_pulang == null) {
                        $dataAttendancesPegawai['outgoing_time'] = $jam_kosong;
                        $potongan_tpp = $tunjangan_per_hari * 40 / 100 * $configTpp[4]['persentase_potongan'] / 100;
                        $dataAttendancesPegawai['config_potongan_tpp_id'] = $configTpp[4]['id'];
                        $dataAttendancesPegawai['potongan_absen_pulang'] = $potongan_tpp;
                        $dataAttendancesPegawai['potongan_absen_pulang_persen'] = $configTpp[4]['persentase_potongan'];
                        $dataAttendancesPegawai['status_pulang'] = $configTpp[4]['title'];
                    }

                    // cek 2x apel
                    if (date('w') == 5) {

                        $potongan = $configTpp[12]['persentase_potongan'] / 2;

                        // cek apel pagi
                        if ($cek_absen->status_apel_pagi == null || trim($cek_absen->status_apel_pagi) == '') {
                            $potongan_tpp_apel_pagi = $tunjangan_per_hari * 40 / 100 * $potongan / 100;
                            $status_apel_pagi = $configTpp[12]['title'];
                            $potongan_apel_pagi_persen = $potongan;

                            $dataAttendancesPegawai['status_apel_pagi'] = $status_apel_pagi;
                            $dataAttendancesPegawai['potongan_tidak_apel_pagi'] = $potongan_tpp_apel_pagi;
                            $dataAttendancesPegawai['potongan_tidak_apel_pagi_persen'] = $potongan_apel_pagi_persen;
                        }

                        // cek apel sore
                        if ($cek_absen->status_apel_sore == null || trim($cek_absen->status_apel_sore) == '') {
                            $potongan_tpp_apel_sore = $tunjangan_per_hari * 40 / 100 * $potongan / 100;
                            $status_apel_sore = $configTpp[12]['title'];
                            $potongan_apel_sore_persen = $potongan;

                            $dataAttendancesPegawai['status_apel_sore'] = $status_apel_sore;
                            $dataAttendancesPegawai['potongan_tidak_apel_sore'] = $potongan_tpp_apel_sore;
                            $dataAttendancesPegawai['potongan_tidak_apel_sore_persen'] = $potongan_apel_sore_persen;
                        }

                        if ($status_apel_sore == $configTpp[12]['title'] || $status_apel_pagi == $configTpp[12]['title']) {
                            $potongan_tpp_apel = $potongan_tpp_apel_pagi + $potongan_tpp_apel_sore;
                            $dataAttendancesPegawai['status_apel'] = $configTpp[12]['title'];
                            $potongan_apel_persen = $potongan_apel_pagi_persen + $potongan_apel_sore_persen;
                            $dataAttendancesPegawai['potongan_tidak_apel_persen'] = $potongan_apel_persen;
                            $dataAttendancesPegawai['potongan_tidak_apel'] = $potongan_tpp_apel;
                        }
                    } else {
                        // cek apel pagi
                        if ($cek_absen->status_apel_pagi == null || trim($cek_absen->status_apel_pagi) == '') {
                            $potongan_tpp_apel_pagi = $tunjangan_per_hari * 40 / 100 * $configTpp[12]['persentase_potongan'] / 100;
                            $status_apel_pagi = $configTpp[12]['title'];
                            $potongan_apel_pagi_persen = $configTpp[12]['persentase_potongan'];
                            $potongan_tpp_apel = $tunjangan_per_hari * 40 / 100 * $configTpp[12]['persentase_potongan'] / 100;
                            $potongan_apel_persen = $potongan_apel_pagi_persen;
                            $dataAttendancesPegawai['status_apel_pagi'] = $status_apel_pagi;
                            $dataAttendancesPegawai['potongan_tidak_apel_pagi'] = $potongan_tpp_apel_pagi;
                            $dataAttendancesPegawai['potongan_tidak_apel_pagi_persen'] = $potongan_apel_pagi_persen;
                            $dataAttendancesPegawai['status_apel'] = $configTpp[12]['title'];
                            $dataAttendancesPegawai['potongan_tidak_apel_persen'] = $potongan_apel_persen;
                            $dataAttendancesPegawai['potongan_tidak_apel'] = $potongan_tpp_apel;
                        }
                    }


                    $total_potongan_tpp = $cek_absen->total_potongan_tpp + $potongan_tpp + $potongan_tpp_apel;
                    $tpp_diterima = $tunjangan_per_hari - $total_potongan_tpp;

                    $dataAttendancesPegawai['tunjangan_per_hari'] = $tunjangan_per_hari;
                    $dataAttendancesPegawai['tpp_diterima'] = $tpp_diterima;
                    $dataAttendancesPegawai['total_potongan_tpp'] = $total_potongan_tpp;

                    AttendancesPegawai::where("id", $cek_absen->id)->update($dataAttendancesPegawai);
                }
            }
            Log::info("sudah diinput smw ");
        });
    }
}
