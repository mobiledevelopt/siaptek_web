<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SaveFileJob;
use App\Jobs\SaveImageJob;
use App\Models\AttendancesPegawai;
use App\Models\Dinas;
use App\Models\Pegawai;
use App\Models\JamAbsen;
use App\Models\Jml_hari_kerja;
use App\Models\ConfigPotTpp;
use App\Models\KalendarLibur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Traits\Upload;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Image;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Imagick;
use Maestroerror\HeicToJpg;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PegawaiController extends Controller
{
    use Upload;

    public function clock_in(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|max:10000',
        ], [
            'file.required' => 'Wajib Foto Selfi',
            'file.max' => 'Max ukuran foto 10 MB',
        ]);

        if ($validator->fails()) {
            $messages = [];
            $errors = $validator->errors();
            foreach ($errors->all() as $message) {
                array_push($messages, $message);
            }
            $response['message'] = implode("  ", $messages);
            return response()->json($response, 422);
        }

        // 0 minggu, 1 senin, 2 selasa, 3 rabu, 4 kamis, 5 jumat, 6 sabtu
        //cek jadwal absen
        if (date('w') == 0) {
            return response()->json([
                'message' => 'Hari Minggu Libur Presensi',
            ]);
        }

        if (date('w') == 6) {
            return response()->json([
                'message' => 'Hari Sabtu Libur Presensi',
            ]);
        }

        $hari_libur = KalendarLibur::where('tgl', date('Y-m-d'))->first();

        if ($hari_libur != null) {
            return response()->json([
                'message' => 'Hari Ini Libur Presensi',
            ]);
        }

        if (date('w') <= 4) {
            $jadwal_JamAbsen = JamAbsen::where('id', 1)->first();
            $jadwal_jam_masuk_minimal = $jadwal_JamAbsen->min_masuk;
            $jadwal_jam_masuk_maksimal = $jadwal_JamAbsen->max_masuk;
            $jadwal_jam_masuk = $jadwal_JamAbsen->jam_masuk;
        } else {
            $jadwal_JamAbsen = JamAbsen::where('id', 2)->first();
            $jadwal_jam_masuk_minimal = $jadwal_JamAbsen->min_masuk;
            $jadwal_jam_masuk_maksimal = $jadwal_JamAbsen->max_masuk;
            $jadwal_jam_masuk = $jadwal_JamAbsen->jam_masuk;
        }

        // cek minimal jam masuk
        if (time() < strtotime($jadwal_jam_masuk_minimal)) {
            return response()->json([
                'message' => 'Minimal Jam Presensi ' . date("H:i", strtotime($jadwal_jam_masuk_minimal)) . "\n",
            ]);
        }

        $jml_hari_kerja = Jml_hari_kerja::where(['bulan' => date('m'), 'tahun' => date('Y')])->first();
        $tunjangan_per_hari = $request->user()->tpp / $jml_hari_kerja->jml_hari_kerja;

        // cek maksimal jam masuk
        if (time() > strtotime($jadwal_jam_masuk_maksimal)) {
            return response()->json([
                'message' => 'Anda melebihi batas maksimal jam masuk dan dianggap tidak masuk kerja' . "\n",
            ]);
        }

        $dinas = Dinas::where('id', $request->user()->dinas_id)->first();

        $cek = AttendancesPegawai::where(
            [
                ['pegawai_id', $request->user()->id],
                ['date_attendance', date('Y-m-d')],
                ['dinas_id', $request->user()->dinas_id]
            ]
        )->first();

        if ($dinas->latitude == null || $dinas->longitude == null) {
            return
                response()->json([
                    'message' => 'Latitude & Longitude Dinas kosong' . "\n",
                ]);
        }

        if ($request->latitude == null || $request->longitude == null) {
            return
                response()->json([
                    'message' => 'Latitude & Longitude anda kosong' . "\n",
                ]);
        }

        if ($cek != null && $cek->incoming_time != null) {
            return response()->json([
                'message' => 'Anda sudah presensi masuk' . "\n",
            ]);
        }

        $path = null;
        $attachment = null;


        DB::beginTransaction();
        try {

            if ($request->hasFile('file')) {
                // $path = $this->UploadFile($request->file('file'), 'persensi_pegawai'); //use the method in the trait
                // $attachment = url('/storage/') . '/' . $path;
                // $upload = $this->saveImage($request->file('file'), 'persensi_pegawai');
                $upload = $this->saveImageNew($request->file('file'), 'persensi_pegawai', 'temp');
                $path = $upload[0];
                $attachment = $upload[1];
            }

            // cek keterlambatan
            /* absen masuk sebelum waktunya */
            if (time() < strtotime($jadwal_jam_masuk)) {
                $total_potongan_tpp = 0;
                $tpp_diterima = $tunjangan_per_hari - $total_potongan_tpp;

                AttendancesPegawai::updateOrCreate(
                    ['pegawai_id' => $request->user()->id, 'dinas_id' => $request->user()->dinas_id, 'date_attendance' => date('Y-m-d')],
                    [
                        'incoming_time' => now(),
                        'dinas_id' => $request->user()->dinas_id,
                        'tunjangan_per_hari' => $tunjangan_per_hari,
                        'status' => 'Masuk',
                        'status_masuk' => 'Masuk',
                        'menit_telat_masuk' => 0,
                        'total_potongan_tpp' => $total_potongan_tpp,
                        'tpp_diterima' => $tpp_diterima,
                        'foto_absen_masuk_path' => $path,
                        'foto_absen_masuk' => $attachment
                    ]
                );
                DB::commit();
                return response()->json([
                    'message' => 'Presensi success'
                ]);
            }

            /* hitung keterlambatan */
            $level_telat = ConfigPotTpp::all();

            $awal  = date_create('2023-12-13' . $jadwal_jam_masuk);
            $akhir = date_create();
            $diff  = date_diff($awal, $akhir);
            $jam_ = $diff->h * 60;
            $total_menit = $diff->i + $jam_;

            $ConfigPotTpp_id = null; //$level_telat[3]['id'];
            $status_masuk = "Masuk"; //$level_telat[3]['title'];
            $persentase_potongan_tpp = 0; //$level_telat[3]['persentase_potongan'];

            if ($total_menit < $level_telat[0]['dari_meni']) {
                $ConfigPotTpp_id = null; //$level_telat[3]['id'];
                $status_masuk = "Masuk"; //$level_telat[3]['title'];
                $persentase_potongan_tpp = 0; //$level_telat[3]['persentase_potongan'];

            }
            if ($total_menit >= $level_telat[0]['dari_meni'] && $total_menit <= $level_telat[0]['sampai_menit']) {
                $persentase_potongan_tpp = $level_telat[0]['persentase_potongan'];
                $ConfigPotTpp_id = $level_telat[0]['id'];
                $status_masuk = $level_telat[0]['title'];
            }
            if ($total_menit >= $level_telat[1]['dari_meni'] && $total_menit <= $level_telat[1]['sampai_menit']) {
                $persentase_potongan_tpp = $level_telat[1]['persentase_potongan'];
                $ConfigPotTpp_id = $level_telat[1]['id'];
                $status_masuk = $level_telat[1]['title'];
            }
            if ($total_menit >= $level_telat[2]['dari_meni'] && $total_menit <= $level_telat[2]['sampai_menit']) {
                $persentase_potongan_tpp = $level_telat[2]['persentase_potongan'];
                $ConfigPotTpp_id = $level_telat[2]['id'];
                $status_masuk = $level_telat[2]['title'];
            }
            if ($total_menit >= $level_telat[3]['dari_meni'] && $total_menit <= $level_telat[3]['sampai_menit']) {
                $persentase_potongan_tpp = $level_telat[3]['persentase_potongan'];
                $ConfigPotTpp_id = $level_telat[3]['id'];
                $status_masuk = $level_telat[3]['title'];
            }

            if ($total_menit > $level_telat[3]['sampai_menit']) {
                $persentase_potongan_tpp = $level_telat[3]['persentase_potongan'];
                $ConfigPotTpp_id = $level_telat[3]['id'];
                $status_masuk = $level_telat[3]['title'];
            }

            $total_potongan_tpp = $tunjangan_per_hari * 40 / 100 * $persentase_potongan_tpp / 100;
            $tpp_diterima = $tunjangan_per_hari - $total_potongan_tpp;

            AttendancesPegawai::updateOrCreate(
                [
                    'pegawai_id' => $request->user()->id,
                    'dinas_id' => $request->user()->dinas_id,
                    'date_attendance' => date('Y-m-d')
                ],
                [
                    'incoming_time' => now(),
                    'dinas_id' => $request->user()->dinas_id,
                    'tunjangan_per_hari' => $tunjangan_per_hari,
                    'status' => 'Masuk',
                    'menit_telat_masuk' => $total_menit,
                    'total_potongan_tpp' => $total_potongan_tpp,
                    'potongan_absen_masuk' => $total_potongan_tpp,
                    'potongan_absen_masuk_persen' => $persentase_potongan_tpp,
                    'tpp_diterima' => $tpp_diterima,
                    'ConfigPotTpp_id' => $ConfigPotTpp_id,
                    'status_masuk' => $status_masuk,
                    'foto_absen_masuk_path' => $path,
                    'foto_absen_masuk' => $attachment
                ]
            );

            DB::commit();
            return response()->json([
                'message' => 'Presensi Masuk success'
            ]);
        } catch (\Throwable $throw) {
            DB::rollBack();
            return response()->json([
                'message' => $throw->getMessage()
            ], 500);
        }
    }

    public function clock_out(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'file' => 'required|max:10000',
        ], [
            'file.required' => 'Wajib Foto Selfi',
            'file.max' => 'Max ukuran foto 10 MB',
        ]);

        if ($validator->fails()) {
            $messages = [];
            $errors = $validator->errors();
            foreach ($errors->all() as $message) {
                array_push($messages, $message);
            }
            $response['message'] = implode("  ", $messages);
            return response()->json($response, 422);
        }

        //cek jadwal absen
        if (date('w') == 0) {
            return response()->json([
                'message' => 'Hari Minggu Libur Presensi',
            ]);
        }

        if (date('w') == 6) {
            return response()->json([
                'message' => 'Hari Sabtu Libur Presensi',
            ]);
        }

        $hari_libur = KalendarLibur::where('tgl', date('Y-m-d'))->first();

        if ($hari_libur != null) {
            return response()->json([
                'message' => 'Hari Ini Libur Presensi',
            ]);
        }

        if (date('w') <= 4) {
            $jadwal_JamAbsen = JamAbsen::where('id', 1)->first();
            $jadwal_jam_pulang_minimal = $jadwal_JamAbsen->min_pulang;
            $jadwal_jam_pulang_maksimal = $jadwal_JamAbsen->max_pulang;
            $jadwal_jam_pulang = $jadwal_JamAbsen->jam_pulang;
        } else {
            $jadwal_JamAbsen = JamAbsen::where('id', 2)->first();
            $jadwal_jam_pulang_minimal = $jadwal_JamAbsen->min_pulang;
            $jadwal_jam_pulang_maksimal = $jadwal_JamAbsen->max_pulang;
            $jadwal_jam_pulang = $jadwal_JamAbsen->jam_pulang;
        }

        // cek minimal jam Pulang
        if (time() < strtotime($jadwal_jam_pulang_minimal)) {
            return response()->json([
                'message' => 'Minimal Jam Presensi Pulang ' . date("H:i", strtotime($jadwal_jam_pulang_minimal)) . "\n",
            ]);
        }

        $jml_hari_kerja = Jml_hari_kerja::where(['bulan' => date('m'), 'tahun' => date('Y')])->first();
        $tunjangan_per_hari = $request->user()->tpp / $jml_hari_kerja->jml_hari_kerja;
        $config_potongan = ConfigPotTpp::all();

        $data_attendance = AttendancesPegawai::where([
            ['pegawai_id', $request->user()->id],
            ['dinas_id', $request->user()->dinas_id,],
            ['date_attendance', date('Y-m-d')]
        ])->first();

        // cek maksimal jam pulang
        if (time() > strtotime($jadwal_jam_pulang_maksimal)) {
            // $potongan_tpp = $tunjangan_per_hari * 40 / 100 * $config_potongan[4]['persentase_potongan'] / 100;
            // $total_potongan_tpp = $data_attendance->total_potongan_tpp + $potongan_tpp;
            // $tpp_diterima = $tunjangan_per_hari - $total_potongan_tpp;

            // Attendances::updateOrCreate(
            //     [
            //         'pegawai_id' => $request->user()->id,
            //         'dinas_id' => $request->user()->dinas_id,
            //         'date_attendance' => date('Y-m-d')
            //     ],
            //     [
            //         'outgoing_time' => now(),
            //         'dinas_id' => $request->user()->dinas_id,
            //         'tunjangan_per_hari' => $tunjangan_per_hari,
            //         'status' => 'Masuk',
            //         'total_potongan_tpp' => $total_potongan_tpp,
            //         'tpp_diterima' => $tpp_diterima,
            //         'ConfigPotTpp_id' => $config_potongan[4]['id'],
            //         'potongan_absen_pulang' => $potongan_tpp,
            //         'potongan_absen_pulang_persen' => $config_potongan[4]['persentase_potongan'],
            //         'status_pulang' => $config_potongan[4]['title'],
            //     ]
            // );

            return response()->json([
                'message' => 'Anda melebihi batas maksimal jam Presensi Pulang' . "\n",
            ]);
        }

        $dinas = Dinas::where('id', $request->user()->dinas_id)->first();

        if ($dinas->latitude == null || $dinas->longitude == null) {
            return
                response()->json([
                    'message' => 'Latitude & Longitude Dinas kosong' . "\n",
                ]);
        }
        if ($request->latitude == null || $request->longitude == null) {
            return
                response()->json([
                    'message' => 'Latitude & Longitude anda kosong' . "\n",
                ]);
        }

        if ($data_attendance != null && $data_attendance->outgoing_time != null) {
            return response()->json([
                'message' => 'Anda sudah presensi pulang' . "\n",
            ]);
        }

        if ($data_attendance == null) {
            return response()->json([
                'message' => 'Anda tidak presensi masuk dan di anggap tidak masuk kerja' . "\n",
            ]);
        }

        $path = null;
        $attachment = null;

        DB::beginTransaction();
        try {

            if ($request->hasFile('file')) {
                // $path = $this->UploadFile($request->file('file'), 'persensi_pegawai'); //use the method in the trait
                // $attachment = url('/storage/') . '/' . $path;

                // $image = $request->file('file');
                // $imageName = auth()->id() . '_' . time() . '.' . $image->getClientOriginalExtension();
                // $img = Image::make($image->getRealPath());
                // $img->orientate()->resize(800, 800, function ($constraint) {
                //     $constraint->aspectRatio();
                // });
                // $img->stream();
                // Storage::disk('public')->put('persensi_pegawai/' . $imageName, $img);
                // $path = 'persensi_pegawai/' . $imageName;
                // $attachment = url('/storage/') . '/persensi_pegawai/' . $imageName;

                // $upload = $this->saveImage($request->file('file'), 'persensi_pegawai');
                $upload = $this->saveImageNew($request->file('file'), 'persensi_pegawai', 'temp');
                $path = $upload[0];
                $attachment = $upload[1];
            }

            AttendancesPegawai::updateOrCreate(
                [
                    'pegawai_id' => $request->user()->id,
                    'dinas_id' => $request->user()->dinas_id,
                    'date_attendance' => date('Y-m-d')
                ],
                [
                    'outgoing_time' => now(),
                    'dinas_id' => $request->user()->dinas_id,
                    'tunjangan_per_hari' => $tunjangan_per_hari,
                    'status' => 'Masuk',
                    'status_pulang' => "Pulang",
                    'foto_absen_pulang_path' => $path,
                    'foto_absen_pulang' => $attachment
                ]
            );

            DB::commit();
            return response()->json([
                'message' => 'Presensi Pulang success',
            ]);
        } catch (\Throwable $throw) {
            DB::rollBack();
            return response()->json([
                'message' => $throw->getMessage()
            ], 500);
        }
    }

    public function clock_in_new(Request $request)
    {
        // ===== VALIDASI AWAL (CEPAT) =====
        if (in_array(date('w'), [0, 6])) {
            return response()->json(['message' => 'Hari Libur Presensi']);
        }

        if (KalendarLibur::whereDate('tgl', today())->exists()) {
            return response()->json(['message' => 'Hari Ini Libur Presensi']);
        }

        // ===== CACHE KONFIGURASI =====

        // $jadwal = cache()->remember(
        //     'jam_absen_' . date('w'),
        //     3600,
        //     fn() => JamAbsen::find(date('w') <= 4 ? 1 : 2)
        // );

        $jadwal = JamAbsen::find(date('w') <= 4 ? 1 : 2);


        $level_telat = cache()->remember(
            'config_pot_tpp',
            3600,
            fn() => ConfigPotTpp::all()
        );

        // ===== HITUNG JAM =====
        $now = time();
        if ($now < strtotime($jadwal->min_masuk)) {
            return response()->json(['message' => 'Minimal Jam Presensi ' . $jadwal->min_masuk]);
        }

        if ($now > strtotime($jadwal->max_masuk)) {
            return response()->json(['message' => 'Anda melebihi batas maksimal jam masuk']);
        }

        $alreadyClockedIn = AttendancesPegawai::where(
            [
                ['pegawai_id', $request->user()->id],
                ['date_attendance', date('Y-m-d')],
                ['dinas_id', $request->user()->dinas_id]
            ]
        )->exists();

        if ($alreadyClockedIn) {
            return response()->json(['message' => 'Anda sudah presensi masuk']);
        }

        $total_menit = max(0, floor(($now - strtotime($jadwal->jam_masuk)) / 60));

        $status_masuk = 'Masuk';
        $persen_potong = 0;

        foreach ($level_telat as $level) {
            if ($total_menit >= $level->dari_meni && $total_menit <= $level->sampai_menit) {
                $status_masuk = $level->title;
                $persen_potong = $level->persentase_potongan;
                break;
            }
        }

        // ===== ATOMIC INSERT (SUPER CEPAT) =====
        try {
            $absen = AttendancesPegawai::create([
                'pegawai_id' => $request->user()->id,
                'dinas_id' => $request->user()->dinas_id,
                'date_attendance' => today(),
                'incoming_time' => now(),
                'status' => 'Masuk',
                'menit_telat_masuk' => $total_menit,
                'status_masuk' => $status_masuk
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'message' => 'Anda sudah presensi masuk'
            ]);
        }

        return response()->json([
            'message' => 'Presensi berhasil',
            'id_absen' => $absen->id
        ]);
    }


    public function clock_out_new(Request $request)
    {
        // ===== VALIDASI HARI =====
        if (in_array(date('w'), [0, 6])) {
            return response()->json(['message' => 'Hari Libur Presensi']);
        }

        if (KalendarLibur::whereDate('tgl', today())->exists()) {
            return response()->json(['message' => 'Hari Ini Libur Presensi']);
        }

        // ===== CACHE JADWAL =====
        $jadwal = cache()->remember(
            'jam_absen_pulang_' . date('w'),
            3600,
            fn() => JamAbsen::find(date('w') <= 4 ? 1 : 2)
        );

        if (time() < strtotime($jadwal->min_pulang)) {
            return response()->json([
                'message' => 'Minimal Jam Presensi Pulang ' . date("H:i", strtotime($jadwal->min_pulang))
            ]);
        }

        if (time() > strtotime($jadwal->max_pulang)) {
            return response()->json(['message' => 'Anda melebihi batas maksimal jam Presensi Pulang']);
        }

        // ===== VALIDASI LOKASI =====
        $dinas = cache()->remember(
            'dinas_' . $request->user()->dinas_id,
            3600,
            fn() => Dinas::find($request->user()->dinas_id)
        );

        if (!$dinas || !$dinas->latitude || !$dinas->longitude) {
            return response()->json(['message' => 'Latitude & Longitude Dinas kosong']);
        }

        if (!$request->latitude || !$request->longitude) {
            return response()->json(['message' => 'Latitude & Longitude anda kosong']);
        }

        // ===== ATOMIC UPDATE =====
        $updated = AttendancesPegawai::where([
            ['pegawai_id', $request->user()->id],
            ['dinas_id', $request->user()->dinas_id],
            ['date_attendance', today()],
            ['outgoing_time', null]
        ])->update([
            'outgoing_time' => now(),
            'status_pulang' => 'Pulang'
        ]);

        if ($updated === 0) {
            return response()->json([
                'message' => 'Anda belum presensi masuk atau sudah presensi pulang'
            ]);
        }

        return response()->json([
            'message' => 'Presensi Pulang berhasil'
        ]);
    }


    public function uploadFoto(Request $request)
    {
        $request->validate([
            'id_absen' => 'required',
            'file' => 'required|image',
            'jenis'    => 'required|string',
        ]);

        $absen = AttendancesPegawai::find($request->id_absen);

        if (!$absen) {
            return response()->json(['message' => 'Absen tidak ditemukan'], 404);
        }

        $upload = $this->saveImageNew($request->file('file'), 'presensi_pegawai_upload_tes', 'temp');

        $path = $upload[0];
        $url = $upload[1];

        switch ($request->jenis) {
            case 'masuk':
                $absen->foto_absen_masuk_path = $path;
                $absen->foto_absen_masuk = $url;
                break;

            case 'pulang':
                $absen->foto_absen_pulang_path = $path;
                $absen->foto_absen_pulang = $url;
                break;

            case 'apel_pagi':
                $absen->foto_apel_pagi_path = $path;
                $absen->foto_apel_pagi = $url;
                break;

            case 'apel_sore':
                $absen->foto_apel_sore_path = $path;
                $absen->foto_apel_sore = $url;
                break;

            default:
                return response()->json([
                    'message' => 'Jenis foto tidak valid'
                ], 422);
        }
        $absen->save();

        return response()->json(['message' => 'Foto berhasil diupload']);
    }

    public function persensi(Request $request)
    {

        // $user = $request->user();
        // $data = AttendancesPegawai::where('pegawai_id', $user->id);
        // if ($request->date != null) {
        //     $data->where('date_attendance', $request->date);
        // }

        return response()->json([
            'message' => 'success',
            'data' => [] //$data->take(5)->orderBy('date_attendance', 'desc')->get()
        ]);
    }

    public function school(Request $request)
    {

        $user = $request->user();
        $data = Dinas::where('id', '1');
        return response()->json([
            'message' => 'success',
            'data' => $data->get()
        ]);
    }

    function cek_range($lat, $lang, $lat_, $lang_)
    {
        $R = 6371.0710;
        $rlat1 = $lat * (pi() / 180);
        $rlat2 = $lat_ * (pi() / 180);
        $difflat = $rlat2 - $rlat1;
        $difflon = ($lang_ - $lang) * (pi() / 180);

        $d = 2 * $R * asin(sqrt(sin($difflat / 2) * sin($difflat / 2) + cos($rlat1) * cos($rlat2) * sin($difflon / 2) * sin($difflon / 2)));
        return round($d * 1000);
    }

    function update_pp(Request $request)
    {
        $user = $request->user();

        $code = 400;
        $response = [
            'message' => 'Data tidak lengkap',
            'data' => []
        ];

        $validator = Validator::make($request->all(), [
            'file' => 'max:10000',
        ], [
            'uploaded' => ':attribute maksimal 10 MB'
        ]);

        if ($validator->fails()) {
            $messages = [];
            $errors = $validator->errors();
            foreach ($errors->all() as $message) {
                array_push($messages, $message);
            }
            $response['message'] = implode("  ", $messages);
            return response()->json($response, $code);
        }

        if ($request->hasFile('file')) {

            try {
                // $path = $this->UploadFile($request->file('file'), 'pp_pegawai');  
                $upload = $this->saveImage($request->file('file'), 'pp_pegawai');
                $path = $upload[0];
            } catch (\Throwable $throw) {
                return response()->json([
                    'message' => $throw->getMessage()
                ]);
            }

            if (!is_null($user->foto_profile)) {
                $this->deleteFile($user->foto_profile_path);
            }
            $data = Pegawai::where('id', $request->user()->id)
                ->update(['foto_profile' => url('/storage/') . '/' . $path, 'foto_profile_path' => $path]);
        }

        return response()->json([
            'message' => 'Foto Profile Berhasil di Update',
            'data' => Pegawai::where('id', $request->user()->id)->first()
        ]);
    }

    function update_pw(Request $request)
    {
        $user = $request->user();

        $code = 400;
        $response = [
            'message' => 'Data tidak lengkap',
            'data' => []
        ];

        $validator = Validator::make($request->all(), [
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            $messages = [];
            $errors = $validator->errors();
            foreach ($errors->all() as $message) {
                array_push($messages, $message);
            }
            $response['message'] = implode("  ", $messages);
            return response()->json($response, $code);
        }


        Pegawai::where('id', $request->user()->id)->update(['password' => Hash::make($request->password)]);


        return response()->json([
            'message' => 'Password Berhasil di Update'
        ]);
    }

    public function update(Request $request, Pegawai $teacher)
    {


        $data = $request->all();
        unset($data['_method']);
        unset($data['file']);

        $code = 400;
        $response = [
            'message' => 'Data tidak lengkap',
            'data' => []
        ];

        $validator = Validator::make($request->all(), [
            'file' => 'max:10000',
            'email' => [
                'required',
                Rule::unique('pegawai', 'email')->ignore($teacher->id),
            ],
            'name' => 'required',
            'nip' => [
                'required',
                Rule::unique('pegawai', 'nip')->ignore($teacher->id),
                'digits:18'
            ]
        ], [
            'uploaded' => ':attribute maksimal 10 MB',
            'email.required' => 'Email harus terisi.',
            'email.unique' => 'Email Sudah Terdaftar.',
            'name.required' => 'Nama harus terisi.',
            'nip' => 'NIP wajib terisi.',
            'nip.unique' => 'NIP Sudah Terdaftar.',
            'nip.digits' => 'NIP wajib 18 digit.',
        ]);

        if ($validator->fails()) {
            $messages = [];
            $errors = $validator->errors();
            foreach ($errors->all() as $message) {
                array_push($messages, $message);
            }
            $response['message'] = implode("  ", $messages);
            return response()->json($response, $code);
        }

        if ($request->hasFile('file')) {

            try {
                // $path = $this->UploadFile($request->file('file'), 'pp_pegawai');
                $upload = $this->saveImage($request->file('file'), 'pp_pegawai');
                $path = $upload[0];
            } catch (\Throwable $throw) {
                return response()->json([
                    'message' => $throw->getMessage()
                ]);
            }

            if (!is_null($teacher->foto_profile)) {
                $this->deleteFile($teacher->foto_profile_path);
            }
            $data['foto_profile'] = url('/storage/') . '/' . $path;
            $data['foto_profile_path'] = $path;

            $teacher->update($data);
        } else {
            $teacher->update($data);
        }

        return response()->json([
            'message' => 'Profile Berhasil di Update',
            'data' => $teacher
        ]);
    }

    public function detail(Request $request)
    {

        $user = Pegawai::with('dinas', 'agama', 'jenjang_pendidikan', 'status_perkawinan')->where('id', $request->user()->id)->firstOrFail();

        return response()->json(
            $user
        );
    }

    public function show(string $id)
    {
        // dd('a')
        try {
            // Cache data pegawai
            $user = Cache::remember("pegawai_{$id}", now()->addMinutes(10), function () use ($id) {
                return Pegawai::findOrFail($id);
            });

            // // Cache versi aplikasi
            // $versi = Cache::remember('app_version', now()->addMinutes(10), function() {
            //     return DB::table('versi')->value('versi');
            // });

            // Menghitung total TPP diterima berdasarkan rentang tanggal bulan ini
            $from = Carbon::now()->startOfMonth()->toDateString();
            $to = Carbon::now()->endOfMonth()->toDateString();

            // $tpp = AttendancesPegawai::where('pegawai_id', $user->id)
            //     ->whereBetween('date_attendance', [$from, $to])
            //     ->sum('tpp_diterima');
            $tpp = 0;
            // Menambahkan versi ke dalam data user
            $user->versi = '1.0.2-dev';

            return response()->json([
                // 'status' => 'success',
                'message' => 'success',
                'results' => ['data' => [$user], 'tpp' => "Rp " . number_format($tpp, 2, ',', '.')]
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pegawai tidak ditemukan'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan. Silakan coba lagi.'
            ], 500);
        }
    }

    public function block_fake_gps(Request $request)
    {

        Pegawai::where('id', $request->user()->id)->update(['fake_gps' => 1]);
        DB::delete('delete from personal_access_tokens where tokenable_id = ' . $request->user()->id);
        return response()->json([
            'message' => 'OK',
        ]);
    }

    public function test_compres(Request $request)
    {
        if ($request->hasFile('file')) {
            try {

                $hari_libur = KalendarLibur::where('tgl', date('Y-m-d'))->first();

                if (date('w') <= 4) {
                    $jadwal_JamAbsen = JamAbsen::where('id', 1)->first();
                    $jadwal_jam_masuk_minimal = $jadwal_JamAbsen->min_masuk;
                    $jadwal_jam_masuk_maksimal = $jadwal_JamAbsen->max_masuk;
                    $jadwal_jam_masuk = $jadwal_JamAbsen->jam_masuk;
                } else {
                    $jadwal_JamAbsen = JamAbsen::where('id', 2)->first();
                    $jadwal_jam_masuk_minimal = $jadwal_JamAbsen->min_masuk;
                    $jadwal_jam_masuk_maksimal = $jadwal_JamAbsen->max_masuk;
                    $jadwal_jam_masuk = $jadwal_JamAbsen->jam_masuk;
                }

                $jml_hari_kerja = Jml_hari_kerja::where(['bulan' => date('m'), 'tahun' => date('Y')])->first();
                $tunjangan_per_hari = $request->user()->tpp / $jml_hari_kerja->jml_hari_kerja;

                $dinas = Dinas::where('id', $request->user()->dinas_id)->first();

                $cek = AttendancesPegawai::where(
                    [
                        ['pegawai_id', $request->user()->id],
                        ['date_attendance', date('Y-m-d')],
                        ['dinas_id', $request->user()->dinas_id]
                    ]
                )->first();


                $upload = $this->saveImageNew($request->file('file'), 'test_persensi_pegawai', 'temp');
                return response()->json([
                    'message' => 'test success',
                    'dataa' => $upload
                ]);
                //   dd($upload);
            } catch (\Throwable $throw) {
                return response()->json([
                    'message' => $throw->getMessage()
                ]);
            }
        }
    }

    public function saveImage($image, $folder)
    {

        if ($image->getClientOriginalExtension() == 'heic') {
            $path = $this->UploadFile($image, $folder);
            $attachment = url('/storage/') . '/' . $path;
        } else {
            $imageName = auth()->id() . '_' . time() . '.' . $image->getClientOriginalExtension();
            $img = Image::make($image->getRealPath());
            $img->orientate()->resize(800, 800, function ($constraint) {
                $constraint->aspectRatio();
            });
            $img->stream();
            Storage::disk('public')->put($folder . '/' . $imageName, $img);
            $attachment = url('/storage/') . '/' . $folder . '/' . $imageName;
            $path = $folder . '/' . $imageName;
        }

        return [$path, $attachment];
    }

    public function saveImageNew($image, $folder, $tempFolder = 'temp')
    {

        // if ($image->getClientOriginalExtension() === 'heic') {

        //     // Generate a unique file name
        //     $filename = time() . '-' . $image->getClientOriginalName();

        //     // Optionally store the file temporarily and get the path
        //     $file = $image->storeAs($tempFolder, $filename, 'public');

        //     // Define the final storage path
        //     $path = $folder . '/' . $filename;

        //     // Dispatch the job to save the file in the background
        //     SaveFileJob::dispatch($file, $folder, $filename,$tempFolder);

        //     return [$path, url('/storage/') . '/' . $path];
        // }

        $imageName = auth()->id() . '_' . time() . '.' . $image->getClientOriginalExtension();
        $path = $folder . '/' . $imageName;
        $tempPath = $image->storeAs($tempFolder, $imageName, 'public');

        Log::info("Image saved successfully: {$imageName}");



        // if(getimagesize($image)[0] > 4000|| getimagesize($image)[1] > 4000){
        //   ini_set('memory_limit', '-1');
        //   SaveImageJob::dispatchSync($tempPath, $path,$imageName,$tempFolder);
        // }else{
        //     SaveImageJob::dispatch($tempPath, $path,$imageName,$tempFolder);    
        // }

        return [$path, url('/storage/') . '/' . $path];
    }
}
