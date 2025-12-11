<?php

namespace App\Http\Controllers\Api;


use App\Models\Izin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AttendancesPegawai;
use App\Models\AttendancesTeacher;
use App\Models\DaftarHadirApel;
use App\Models\JadwalApel;
use App\Models\JenisIzin;
use App\Models\KalendarLibur;
use Illuminate\Support\Facades\Validator;
use App\Traits\Upload;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Jobs\SaveFileJob;
use App\Jobs\SaveImageJob;

class DaftarHadirApelController extends Controller
{

    use Upload;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $data = DB::select('select * from attendances_pegawai where pegawai_id = ' . $user->id . ' ORDER BY date_attendance DESC LIMIT 10');
        return response()->json([
            'message' => 'success',
            'data' => $data
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

        $code = 400;
        $response = [
            'message' => 'Data tidak lengkap',
            'data' => []
        ];

        // $validator = Validator::make($request->all(), [
        //     'file' => 'required',
        // ], [
        //     'required' => ':attribute harus terisi.',
        // ]);
        
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
            return response()->json($response, $code);
        }

        // cek jadwal apel pagi / sore
        $jadwal_apel = JadwalApel::where(['dinas_id' => $request->user()->dinas_id, 'hari' => date('w')])->first();

        if ($jadwal_apel->apel_pagi != 1 && $jadwal_apel->apel_sore != 1) {
            return response()->json([
                'message' => 'Tidak ada jadwal apel hari ini' . "\n",
            ]);
        }

        $hari_libur = KalendarLibur::where('tgl', date('Y-m-d'))->first();

        if ($hari_libur != null) {
            return response()->json([
                'message' => 'Hari Ini Libur Apel',
            ]);
        }
        
        // cek batas apel pagi
        if (time() < strtotime('12:00')) {
            if ($jadwal_apel->apel_pagi != 1) {
                return response()->json([
                    'message' => 'Tidak ada jadwal apel pagi hari ini' . "\n",
                ]);
            }
            if ($jadwal_apel->apel_pagi == 1 && time() < strtotime($jadwal_apel->jam_apel_pagi)) {
                return response()->json([
                    'message' => 'Minimal Jam Apel ' . date("H:i", strtotime($jadwal_apel->jam_apel_pagi)) . "\n",
                ]);
            }
            if ($jadwal_apel->apel_pagi == 1 && time() > strtotime($jadwal_apel->max_apel_pagi)) {
                return response()->json([
                    'message' => 'Anda melebihi batas maksimal jam apel pagi' . "\n",
                ]);
            }
        } else {
            // cek batas apel sore
            if ($jadwal_apel->apel_sore != 1) {
                return response()->json([
                    'message' => 'Tidak ada jadwal apel sore hari ini' . "\n",
                ]);
            }
            if ($jadwal_apel->apel_sore == 1 && time() < strtotime($jadwal_apel->jam_apel_sore)) {
                return response()->json([
                    'message' => 'Minimal Jam Apel Sore' . date("H:i", strtotime($jadwal_apel->jam_apel_sore)) . "\n",
                ]);
            }
            if ($jadwal_apel->apel_sore == 1 && time() > strtotime($jadwal_apel->max_apel_sore)) {
                return response()->json([
                    'message' => 'Anda melebihi batas maksimal jam apel sore' . "\n",
                ]);
            }
        }

        // cek presensi apel
        $presensi_apel = AttendancesPegawai::where([
            'pegawai_id' => $request->user()->id,
            'dinas_id' => $request->user()->dinas_id,
            'date_attendance'     => date('Y-m-d')
        ])->first();

        if ($presensi_apel === null) {
            return response()->json([
                'message' => 'Anda Belum Melakukan Presensi Pagi'
            ]);
        }
        
        if (time() < strtotime('12:00')) {
            // cek presensi apel pagi
            if ($presensi_apel !== null && $presensi_apel->status_apel_pagi !== null && trim($presensi_apel->status_apel_pagi) !== '') {
                return response()->json([
                    'message' => 'Anda Sudah Presensi Apel Pagi'
                ]);
            }
        } else {
            // cek presensi apel sore
            if ($presensi_apel !== null && $presensi_apel->status_apel_sore !== null && trim($presensi_apel->status_apel_sore !== '')) {
                return response()->json([
                    'message' => 'Anda Sudah Presensi Apel Sore'
                ]);
            }
        }

        DB::beginTransaction();
        try {

            if ($request->hasFile('file')) {
                // $path = $this->UploadFile($request->file('file'), 'apel_pegawai'); //use the method in the trait
                // $attachment = url('/storage/') . '/' . $path;
                $upload = $this->saveImageNew($request->file('file'), 'apel_pegawai', 'temp_apel');
                $path = $upload[0];
                $attachment = $upload[1];
            }

            if (time() < strtotime('12:00')) {
                AttendancesPegawai::updateOrCreate(
                    [
                        'pegawai_id' => $request->user()->id,
                        'dinas_id' => $request->user()->dinas_id,
                        'date_attendance' => date('Y-m-d')
                    ],
                    [
                        'dinas_id' => $request->user()->dinas_id,
                        'status_apel' => "Hadir",
                        'potongan_tidak_apel' => 0,
                        'potongan_tidak_apel_persen' => 0,
                        'status_apel_pagi' => "Hadir",
                        'potongan_tidak_apel_pagi' => 0,
                        'potongan_tidak_apel_pagi_persen' => 0,
                        'foto_apel_pagi_path' => $path,
                        'foto_apel_pagi' => $attachment
                    ]
                );
            } else {
                AttendancesPegawai::updateOrCreate(
                    [
                        'pegawai_id' => $request->user()->id,
                        'dinas_id' => $request->user()->dinas_id,
                        'date_attendance' => date('Y-m-d')
                    ],
                    [
                        'dinas_id' => $request->user()->dinas_id,
                        'status_apel' => "Hadir",
                        'potongan_tidak_apel' => 0,
                        'potongan_tidak_apel_persen' => 0,
                        'status_apel_sore' => "Hadir",
                        'potongan_tidak_apel_sore' => 0,
                        'potongan_tidak_apel_sore_persen' => 0,
                        'foto_apel_sore_path' => $path,
                        'foto_apel_sore' => $attachment
                    ]
                );
            }

            DB::commit();
            return response()->json([
                'message' => 'Presensi Apel Berhasil'
            ]);
        } catch (\Throwable $throw) {
            DB::rollBack();
            $response = response()->json(['error' => $throw->getMessage()]);
            return response()->json([
                'message' => $response
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Izin $izin)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Izin $izin)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Izin $izin)
    {
        //
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Izin $izin)
    {

        //
    }
    
    public function saveImageNew($image, $folder,$tempFolder='temp')
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
        
        // if(getimagesize($image)[0] > 4000|| getimagesize($image)[1] > 4000){
        //   ini_set('memory_limit', '-1');
        //   SaveImageJob::dispatchSync($tempPath, $path,$imageName,$tempFolder);
        // }else{
        //     SaveImageJob::dispatch($tempPath, $path,$imageName,$tempFolder);    
        // }
        
        return [$path, url('/storage/') . '/' . $path];
    }
    
}
