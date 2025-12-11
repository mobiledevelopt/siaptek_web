<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendancesPegawaiTest;
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
use Imagick;
use Maestroerror\HeicToJpg;
use Carbon\Carbon;
use App\Jobs\SaveFileJob;
use App\Jobs\SaveImageJob;
use Illuminate\Support\Facades\Log;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class TestController extends Controller
{
    use Upload;

    public function test_clockin(Request $request)
    {
        // Validasi foto selfie
         $validator = Validator::make($request->all(), [
            'file' => 'required|max:15000',
        ], [
            'file.required' => 'Wajib Foto Selfi',
            'file.max' => 'Max ukuran foto 15 MB',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode('  ', $validator->errors()->all())], 422);
        }
        
        // $upload = $this->retrysaveImage();
        // return response()->json(['message' => 'uploads ' . $upload]);
        $upload = $this->saveImage($request->file('file'), 'test_persensi_pegawai','temp_test');
        // dd($upload);
        return response()->json(['message' => 'uploads ' . $upload[1]]);
        
        // Cek hari libur
        $today = Carbon::today();
        $dayOfWeek = $today->dayOfWeek;

        if ($this->isWeekend($dayOfWeek)) {
            return response()->json(['message' => 'Hari ' . ($dayOfWeek == Carbon::SUNDAY ? 'Minggu' : 'Sabtu') . ' Libur Presensi']);
        }

        if (KalendarLibur::where('tgl', $today)->exists()) {
            return response()->json(['message' => 'Hari Ini Libur Presensi']);
        }

        // Ambil jadwal jam absen
        $jadwal_JamAbsen = JamAbsen::find($dayOfWeek <= 4 ? 1 : 2);
        $jadwal_jam_masuk_minimal = Carbon::parse($jadwal_JamAbsen->min_masuk);
        $jadwal_jam_masuk_maksimal = Carbon::parse($jadwal_JamAbsen->max_masuk);
        $jadwal_jam_masuk = Carbon::parse($jadwal_JamAbsen->jam_masuk);

        // Cek jam masuk
        $current_time = Carbon::parse($request->jam_masuk ?? Carbon::now());

        if ($current_time->lessThan($jadwal_jam_masuk_minimal)) {
            return response()->json(['message' => 'Minimal Jam Presensi ' . $jadwal_jam_masuk_minimal->format('H:i')]);
        }

        if ($current_time->greaterThan($jadwal_jam_masuk_maksimal)) {
            return response()->json(['message' => 'Anda melebihi batas maksimal jam masuk dan dianggap tidak masuk kerja']);
        }

        // Ambil data dinas dan validasi koordinat
        $dinas = Dinas::find($request->user()->dinas_id);
        if ($this->isInvalidCoordinates($dinas, $request)) {
            return response()->json(['message' => 'Latitude & Longitude tidak boleh kosong']);
        }

        // Cek presensi sebelumnya
        $attendance = AttendancesPegawaiTest::where('pegawai_id', $request->user()->id)
            ->where('date_attendance', $today)
            ->where('dinas_id', $request->user()->dinas_id)
            ->first();

        if ($attendance && $attendance->incoming_time) {
            return response()->json(['message' => 'Anda sudah presensi masuk']);
        }

        

        DB::beginTransaction();
        try {
            // Handle file upload
            $upload = $this->saveImage($request->file('file'), 'test_persensi_pegawai');
            [$path, $attachment] = $upload;

            // Hitung keterlambatan dan tunjangan
            $total_menit = $current_time->diffInMinutes($jadwal_jam_masuk, false);
            $total_menit = max(0, -$total_menit);
            $tunjangan_per_hari = $this->calculateTunjanganPerHari($request->user()->tpp ?: 1000000, $today);

            // Cek keterlambatan
            [$persentase_potongan_tpp, $ConfigPotTpp_id, $status_masuk] = $this->getPotonganStatus($total_menit);
            $total_potongan_tpp = ($tunjangan_per_hari * $persentase_potongan_tpp) / 100;
            $tpp_diterima = $tunjangan_per_hari - $total_potongan_tpp;

            // Update atau buat entri presensi
            AttendancesPegawaiTest::updateOrCreate(
                [
                    'pegawai_id' => $request->user()->id,
                    'dinas_id' => $request->user()->dinas_id,
                    'date_attendance' => $today
                ],
                [
                    'incoming_time' => $current_time,
                    'tunjangan_per_hari' => $tunjangan_per_hari,
                    'status' => $status_masuk,
                    'menit_telat_masuk' => $total_menit,
                    'total_potongan_tpp' => $total_potongan_tpp,
                    'tpp_diterima' => $tpp_diterima,
                    'ConfigPotTpp_id' => $ConfigPotTpp_id,
                    'foto_absen_masuk_path' => $path,
                    'foto_absen_masuk' => $attachment
                ]
            );

            DB::commit();
            return response()->json(['message' => 'Presensi Masuk success']);
        } catch (\Throwable $throw) {
            DB::rollBack();
            return response()->json(['message' => $throw->getMessage()]);
        }
    }

    public function test_clockout(Request $request)
    {
        $today = Carbon::today();
        $dayOfWeek = $today->dayOfWeek;

        if ($this->isWeekend($dayOfWeek)) {
            return response()->json(['message' => 'Hari ' . ($dayOfWeek == Carbon::SUNDAY ? 'Minggu' : 'Sabtu') . ' Libur Presensi']);
        }

        if (KalendarLibur::where('tgl', $today)->exists()) {
            return response()->json(['message' => 'Hari Ini Libur Presensi']);
        }

        $jadwal_JamAbsen = JamAbsen::find($dayOfWeek <= 4 ? 1 : 2);
        $jadwal_jam_pulang_minimal = Carbon::parse($jadwal_JamAbsen->min_pulang);
        $jadwal_jam_pulang_maksimal = Carbon::parse($jadwal_JamAbsen->max_pulang);

        // Cek jam pulang
        $current_time = Carbon::parse($request->jam_pulang ?? Carbon::now());

        if ($current_time->lessThan($jadwal_jam_pulang_minimal)) {
            return response()->json(['message' => 'Minimal Jam Presensi Pulang ' . $jadwal_jam_pulang_minimal->format('H:i')]);
        }

        $data_attendance = AttendancesPegawaiTest::where('pegawai_id', $request->user()->id)
            ->where('dinas_id', $request->user()->dinas_id)
            ->where('date_attendance', $today)
            ->first();

        if ($data_attendance && $data_attendance->outgoing_time) {
            return response()->json(['message' => 'Anda sudah presensi pulang']);
        }

        if (!$data_attendance) {
            return response()->json(['message' => 'Anda tidak presensi masuk dan di anggap tidak masuk kerja']);
        }

        if ($current_time->greaterThan($jadwal_jam_pulang_maksimal)) {
            return response()->json(['message' => 'Anda melebihi batas maksimal jam Presensi Pulang']);
        }

        $dinas = Dinas::find($request->user()->dinas_id);
        if ($this->isInvalidCoordinates($dinas, $request)) {
            return response()->json(['message' => 'Latitude & Longitude tidak boleh kosong']);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required',
        ], [
            'required' => 'Wajib Foto Selfi',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => implode("  ", $validator->errors()->all())]);
        }

        DB::beginTransaction();
        try {
            $upload = $this->saveImage($request->file('file'), 'test_persensi_pegawai');
            [$path, $attachment] = $upload;

            AttendancesPegawaiTest::updateOrCreate(
                [
                    'pegawai_id' => $request->user()->id,
                    'dinas_id' => $request->user()->dinas_id,
                    'date_attendance' => $today
                ],
                [
                    'outgoing_time' => $current_time,
                    'tunjangan_per_hari' => $this->calculateTunjanganPerHari($request->user()->tpp ?: 1000000, $today),
                    'status' => 'Masuk',
                    'status_pulang' => 'Pulang',
                    'foto_absen_pulang_path' => $path,
                    'foto_absen_pulang' => $attachment
                ]
            );

            DB::commit();
            return response()->json(['message' => 'Presensi Pulang success']);
        } catch (\Throwable $throw) {
            DB::rollBack();
            return response()->json(['message' => $throw->getMessage()]);
        }
    }

    private function isWeekend($dayOfWeek)
    {
        return $dayOfWeek === Carbon::SUNDAY || $dayOfWeek === Carbon::SATURDAY;
    }

    private function isInvalidCoordinates($dinas, $request)
    {
        return is_null($dinas->latitude) || is_null($dinas->longitude) || is_null($request->latitude) || is_null($request->longitude);
    }

    private function calculateTunjanganPerHari($tpp, Carbon $today)
    {
        $jml_hari_kerja = Jml_hari_kerja::where(['bulan' => $today->month, 'tahun' => $today->year])->first();
        return $tpp / $jml_hari_kerja->jml_hari_kerja;
    }

    private function getPotonganStatus($total_menit)
    {
        $level_telat = ConfigPotTpp::where('group', 'masuk');
        foreach ($level_telat as $level) {
            if ($total_menit >= $level->dari_meni && $total_menit <= $level->sampai_menit) {
                return [$level->persentase_potongan, $level->id, $level->title];
            }
        }
        return [0, null, 'Masuk'];
    }

    public function saveImage_old($image, $folder)
    {
        if ($image->getClientOriginalExtension() === 'heic') {
            $path = $this->UploadFile($image, $folder);
            return [$path, url('/storage/') . '/' . $path];
        }

        $imageName = auth()->id() . '_' . time() . '.' . $image->getClientOriginalExtension();
        $img = Image::make($image->getRealPath());
        $img->orientate()->resize(800, 800, function ($constraint) {
            $constraint->aspectRatio();
        });
        $img->stream();

        Storage::disk('public')->put($folder . '/' . $imageName, $img);
        $path = $folder . '/' . $imageName;
        return [$path, url('/storage/') . '/' . $path];
    }
    
    public function saveImage($image, $folder,$tempFolder='temp')
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
        //     ini_set('memory_limit', '-1');
        //     SaveImageJob::dispatchSync($tempPath, $path,$imageName,$tempFolder);
        // }else{
        //     SaveImageJob::dispatch($tempPath, $path,$imageName,$tempFolder);    
        // }
        
        return [$path, url('/storage/') . '/' . $path];
    }
    
    public function retrysaveImage()
    {
        
        // get presensi today
        $attendances = AttendancesPegawai::where('date_attendance',date('2024-10-07'))->where('status','Masuk')->get();
        // $attendances = AttendancesPegawai::where('id','106284')->get();
        foreach($attendances as $item){
            // Check and save images for each path
            $this->processImage($item->foto_absen_masuk_path,"temp");
            $this->processImage($item->foto_absen_pulang_path,"temp");
            $this->processImage($item->foto_apel_pagi_path,"temp_apel");
            $this->processImage($item->foto_apel_sore_path,"temp_apel");
        }
        
    }
    
    private function processImage($imagePath,$tempPath)
    {
        if ($imagePath != null) {
            [$pathFile, $fileName] = explode('/', $imagePath);
            $fullPath = storage_path("app/public/{$pathFile}/{$fileName}");
            if (!file_exists($fullPath)) {
                SaveImageJob::dispatch("{$tempPath}/{$fileName}", "{$pathFile}/{$fileName}",$fileName,$tempPath);    
            }
        } 
    }
}
