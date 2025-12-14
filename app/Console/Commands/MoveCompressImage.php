<?php

namespace App\Console\Commands;

use App\Jobs\SaveImageJob;
use App\Models\AttendancesPegawai;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MoveCompressImage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:move-compress-image';

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
        Log::info("app:move-compress-image RUN CRON");
        $attendances = AttendancesPegawai::where('date_attendance', date('Y-m-d'))->where('status', 'Masuk')->get();
        Log::info("attendances count".$attendances->count());
        foreach ($attendances as $item) {
            // Check and save images for each path
            $this->processImage($item->foto_absen_masuk_path, "temp");
            $this->processImage($item->foto_absen_pulang_path, "temp");
            // $this->processImage($item->foto_apel_pagi_path, "temp_apel");
            // $this->processImage($item->foto_apel_sore_path, "temp_apel");
            $this->processImage($item->foto_apel_pagi_path, "temp");
            $this->processImage($item->foto_apel_sore_path, "temp");
        }
    }

    private function processImage($imagePath, $tempPath)
    {
        if ($imagePath != null) {
            [$pathFile, $fileName] = explode('/', $imagePath);
            $fullPath = storage_path("app/public/{$pathFile}/{$fileName}");
            if (!file_exists($fullPath)) {
                Log::info("SaveImageJob");
                SaveImageJob::dispatch("{$tempPath}/{$fileName}", "{$pathFile}/{$fileName}", $fileName, $tempPath);
            }
        }
    }
}
