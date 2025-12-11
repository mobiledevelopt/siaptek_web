<?php

namespace App\Console\Commands;

use App\Models\Apel;
use App\Models\AttendancesPegawaiTest;
use App\Models\ConfigPotTpp;
use App\Models\JadwalApel;
use App\Models\JamAbsen;
use App\Models\Jml_hari_kerja;
use App\Models\KalendarLibur;
use App\Models\Pegawai;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestAbsenCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'testabsen:cron';

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
        Pegawai::where('id', 1212321)->chunk(2000, function ($user) {
            foreach ($user as $item) {
                $DataAttendancesPegawai = [];
                $jml_hari_kerja = Jml_hari_kerja::where(['bulan' => date('m'), 'tahun' => date('Y')])->first();
                $tunjangan_per_hari = $item->tpp / $jml_hari_kerja->jml_hari_kerja;
                $total_potongan_tpp = $tunjangan_per_hari;
                $tpp_diterima = $tunjangan_per_hari - $total_potongan_tpp;
                $cek_absen = AttendancesPegawaiTest::where(['pegawai_id' => $item->id, 'dinas_id' => $item->dinas_id, 'date_attendance' => date('Y-m-d')])->first();
                // dd($cek_absen->ket_cuti === null && $cek_absen->ket_tidak_masuk_kerja === null && $cek_absen->status_pulang === null);
                $configTpp = ConfigPotTpp::all();
                if ($cek_absen === null || $cek_absen->incoming_time === null) {
                    Log::info("alpha id " . $item->id . " " . $item->name);
                    //tidak masuk
                    AttendancesPegawaiTest::create([
                        'dinas_id' => $item->dinas_id,
                        'pegawai_id' => $item->id,
                        'date_attendance' => date('Y-m-d'),
                        'incoming_time' => "00:00:00",
                        'outgoing_time' => "00:00:00",
                        'status' => "Tidak Masuk",
                        'tunjangan_per_hari' => $tunjangan_per_hari,
                        'config_potongan_tpp_id' => $configTpp[13]['id'],
                        'tpp_diterima' => $tpp_diterima,
                        'total_potongan_tpp' => $total_potongan_tpp,
                        'ket_tidak_masuk_kerja' => $configTpp[13]['title'],
                        'potongan_tidak_masuk_kerja_persen' => $configTpp[13]['persentase_potongan'],
                        'potongan_tidak_masuk_kerja' => $total_potongan_tpp
                    ]);
                } elseif ($cek_absen->ket_cuti === null && $cek_absen->ket_tidak_masuk_kerja === null && $cek_absen->status_pulang == null) {
                    Log::info("tidak absen pulang id " . $item->id . " " . $item->name);
                    //tidak absen pulang

                    //cek apel
                    // $apel = Apel::where('tgl', date('Y-m-d'))->first();
                    $apel = JadwalApel::where(['dinas_id' => $item->dinas_id, 'hari' => date('w')])->first();
                    $potongan_tpp_apel = 0;
                    $potongan_apel_persen = 0;
                    $status_apel = "";
                    $status_apel_pagi = NULL;
                    $potongan_tpp_apel_pagi = 0;
                    $potongan_apel_pagi_persen = 0;
                    $status_apel_sore = Null;
                    $potongan_tpp_apel_sore = 0;
                    $potongan_apel_sore_persen = 0;

                    // cek 2x apel
                    if ($apel->apel_pagi == 1 && $apel->apel_sore == 1) {

                        $potongan = $configTpp[12]['persentase_potongan'] / 2;

                        // cek apel pagi
                        if ($cek_absen->status_apel_pagi == null || trim($cek_absen->status_apel_pagi) == '') {
                            $potongan_tpp_apel_pagi = $tunjangan_per_hari * 40 / 100 * $potongan / 100;
                            $status_apel_pagi = $configTpp[12]['title'];
                            $potongan_apel_pagi_persen = $potongan;

                            $DataAttendancesPegawai['status_apel_pagi'] = $status_apel_pagi;
                            $DataAttendancesPegawai['potongan_tidak_apel_pagi'] = $potongan_tpp_apel_pagi;
                            $DataAttendancesPegawai['potongan_tidak_apel_pagi_persen'] = $potongan_apel_pagi_persen;
                        }

                        // cek apel sore
                        if ($cek_absen->status_apel_sore == null || trim($cek_absen->status_apel_sore) == '') {
                            $potongan_tpp_apel_sore = $tunjangan_per_hari * 40 / 100 * $potongan / 100;
                            $status_apel_sore = $configTpp[12]['title'];
                            $potongan_apel_sore_persen = $potongan;

                            $DataAttendancesPegawai['status_apel_sore'] = $status_apel_sore;
                            $DataAttendancesPegawai['potongan_tidak_apel_sore'] = $potongan_tpp_apel_sore;
                            $DataAttendancesPegawai['potongan_tidak_apel_sore_persen'] = $potongan_apel_sore_persen;
                        }

                        if ($status_apel_sore == $configTpp[12]['title'] || $status_apel_pagi == $configTpp[12]['title']) {
                            $potongan_tpp_apel = $potongan_tpp_apel_pagi + $potongan_tpp_apel_sore;
                            $status_apel = $configTpp[12]['title'];
                            $potongan_apel_persen = $potongan_apel_pagi_persen + $potongan_apel_sore_persen;
                        }
                    } else {
                        // cek apel pagi
                        if ($apel->apel_pagi == 1 && $cek_absen->status_apel_pagi == null || trim($cek_absen->status_apel_pagi) == '') {
                            $potongan_tpp_apel_pagi = $tunjangan_per_hari * 40 / 100 * $configTpp[12]['persentase_potongan'] / 100;
                            $status_apel_pagi = $configTpp[12]['title'];
                            $potongan_apel_pagi_persen = $configTpp[12]['persentase_potongan'];
                            $potongan_tpp_apel = $tunjangan_per_hari * 40 / 100 * $configTpp[12]['persentase_potongan'] / 100;
                            $status_apel = $configTpp[12]['title'];
                            $potongan_apel_persen = $potongan_apel_pagi_persen;

                            $DataAttendancesPegawai['status_apel_pagi'] = $status_apel_pagi;
                            $DataAttendancesPegawai['potongan_tidak_apel_pagi'] = $potongan_tpp_apel_pagi;
                            $DataAttendancesPegawai['potongan_tidak_apel_pagi_persen'] = $potongan_apel_pagi_persen;
                        }

                        // cek apel sore
                        if ($apel->apel_sore == 1 && $cek_absen->status_apel_sore == null || trim($cek_absen->status_apel_sore) == '') {
                            $potongan_tpp_apel_sore = $tunjangan_per_hari * 40 / 100 * $configTpp[12]['persentase_potongan'] / 100;
                            $status_apel_sore = $configTpp[12]['title'];
                            $potongan_apel_sore_persen = $configTpp[12]['persentase_potongan'];
                            $potongan_tpp_apel = $tunjangan_per_hari * 40 / 100 * $configTpp[12]['persentase_potongan'] / 100;
                            $status_apel = $configTpp[12]['title'];
                            $potongan_apel_persen = $potongan_apel_sore_persen;

                            $DataAttendancesPegawai['status_apel_sore'] = $status_apel_sore;
                            $DataAttendancesPegawai['potongan_tidak_apel_sore'] = $potongan_tpp_apel_sore;
                            $DataAttendancesPegawai['potongan_tidak_apel_sore_persen'] = $potongan_apel_sore_persen;
                        }
                    }

                    $potongan_tpp = $tunjangan_per_hari * 40 / 100 * $configTpp[4]['persentase_potongan'] / 100;
                    $total_potongan_tpp = $cek_absen->total_potongan_tpp + $potongan_tpp + $potongan_tpp_apel;
                    $tpp_diterima = $tunjangan_per_hari - $total_potongan_tpp;

                    $DataAttendancesPegawai['outgoing_time'] = "00:00:00";
                    $DataAttendancesPegawai['tunjangan_per_hari'] = $tunjangan_per_hari;
                    $DataAttendancesPegawai['config_potongan_tpp_id'] = $configTpp[4]['id'];
                    $DataAttendancesPegawai['potongan_absen_pulang'] = $potongan_tpp;
                    $DataAttendancesPegawai['potongan_absen_pulang_persen'] = $configTpp[4]['persentase_potongan'];
                    $DataAttendancesPegawai['tpp_diterima'] = $tpp_diterima;
                    $DataAttendancesPegawai['total_potongan_tpp'] = $total_potongan_tpp;
                    $DataAttendancesPegawai['status_pulang'] = $configTpp[4]['title'];
                    $DataAttendancesPegawai['status_apel'] = $status_apel;
                    $DataAttendancesPegawai['potongan_tidak_apel_persen'] = $potongan_apel_persen;
                    $DataAttendancesPegawai['potongan_tidak_apel'] = $potongan_tpp_apel;

                    AttendancesPegawaiTest::where("id", $cek_absen->id)->update($DataAttendancesPegawai);
                }
            }
            Log::info("sudah diinput smw ");
        });
    }
}
