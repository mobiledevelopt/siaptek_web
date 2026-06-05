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
    protected $signature = 'app:move-compress-image {date? : Tanggal spesifik (Y-m-d), "backfill" untuk semua hari yang belum diproses, atau kosong untuk hari ini}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move & compress attendance images. Supports today (default), specific date, or backfill for all past unprocessed dates.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dateArg = $this->argument('date');

        if ($dateArg === 'backfill') {
            $this->handleBackfill();
        } elseif ($dateArg) {
            $this->info("Processing images for date: {$dateArg}");
            // Log::info("app:move-compress-image manual run for date: {$dateArg}");
            $this->processDate($dateArg);
        } else {
            $this->handleRecent();
        }
    }

    /**
     * Process only recently updated records (last 65 minutes).
     * Cron runs every 1 jam, so 65-minute window provides safe overlap.
     */
    private function handleRecent()
    {
        $dispatched = 0;

        AttendancesPegawai::where('updated_at', '>=', now()->subMinutes(65))
            ->where('status', 'Masuk')
            ->where(function ($q) {
                $q->whereNotNull('foto_absen_masuk_path')
                  ->orWhereNotNull('foto_absen_pulang_path')
                  ->orWhereNotNull('foto_apel_pagi_path')
                  ->orWhereNotNull('foto_apel_sore_path');
            })
            ->chunk(500, function ($attendances) use (&$dispatched) {
                foreach ($attendances as $item) {
                    $dispatched += $this->processImage($item->foto_absen_masuk_path, "temp");
                    $dispatched += $this->processImage($item->foto_absen_pulang_path, "temp");
                    $dispatched += $this->processImage($item->foto_apel_pagi_path, "temp");
                    $dispatched += $this->processImage($item->foto_apel_sore_path, "temp");
                    $dispatched += $this->processImage($item->foto_apel_pagi_path, "temp_apel");
                    $dispatched += $this->processImage($item->foto_apel_sore_path, "temp_apel");
                }
            });

        if ($dispatched > 0) {
            $this->info("Recent: Dispatched {$dispatched} images for compression.");
            // Log::info("app:move-compress-image recent check dispatched {$dispatched} images.");
        }
    }

    /**
     * Process all past dates that still have uncompressed images in temp folders.
     */
    private function handleBackfill()
    {
        // HANYA CEK 7 HARI KE BELAKANG KARENA CRON BERJALAN SEMINGGU SEKALI
        $dates = AttendancesPegawai::where('date_attendance', '<', date('Y-m-d'))
            ->where('date_attendance', '>=', now()->subDays(7)->format('Y-m-d'))
            ->where('status', 'Masuk')
            ->where(function ($query) {
                $query->whereNotNull('foto_absen_masuk_path')
                      ->orWhereNotNull('foto_absen_pulang_path')
                      ->orWhereNotNull('foto_apel_pagi_path')
                      ->orWhereNotNull('foto_apel_sore_path');
            })
            ->distinct()
            ->orderBy('date_attendance', 'asc')
            ->pluck('date_attendance')
            ->unique();

        $this->info("Backfill: found {$dates->count()} dates to check.");
        // Log::info("app:move-compress-image backfill: {$dates->count()} dates to check.");

        $totalDispatched = 0;

        foreach ($dates as $date) {
            $dispatched = $this->processDate($date);
            $totalDispatched += $dispatched;
        }

        $this->info("Backfill complete. Total images dispatched: {$totalDispatched}");
        // Log::info("app:move-compress-image backfill complete. Total dispatched: {$totalDispatched}");
    }

    /**
     * Process images for a specific date.
     *
     * @param string $date Format Y-m-d
     * @return int Number of images dispatched
     */
    private function processDate(string $date): int
    {
        $dispatched = 0;

        AttendancesPegawai::where('date_attendance', $date)
            ->where('status', 'Masuk')
            ->chunk(100, function ($attendances) use (&$dispatched, $date) {
                // Log::info("move-compress-image [{$date}] chunk count: " . $attendances->count());
                foreach ($attendances as $item) {
                    // Check and save images for each path
                    $dispatched += $this->processImage($item->foto_absen_masuk_path, "temp");
                    $dispatched += $this->processImage($item->foto_absen_pulang_path, "temp");
                    $dispatched += $this->processImage($item->foto_apel_pagi_path, "temp");
                    $dispatched += $this->processImage($item->foto_apel_sore_path, "temp");
                    $dispatched += $this->processImage($item->foto_apel_pagi_path, "temp_apel");
                    $dispatched += $this->processImage($item->foto_apel_sore_path, "temp_apel");
                }
            });

        if ($dispatched > 0) {
            $this->info("[{$date}] Dispatched {$dispatched} images for compression.");
        }

        return $dispatched;
    }

    /**
     * Process a single image path.
     *
     * @param string|null $imagePath
     * @param string $tempPath
     * @return int 1 if dispatched, 0 if skipped
     */
    private function processImage($imagePath, $tempPath): int
    {
        if ($imagePath != null) {
            $parts = explode('/', $imagePath);
            if (count($parts) < 2) return 0;
            [$pathFile, $fileName] = $parts;
            
            $fullPath = storage_path("app/public/{$pathFile}/{$fileName}");
            $tempFullPath = storage_path("app/public/{$tempPath}/{$fileName}");

            if (!file_exists($fullPath)) {
                if (file_exists($tempFullPath)) {
                    SaveImageJob::dispatch("{$tempPath}/{$fileName}", "{$pathFile}/{$fileName}", $fileName, $tempPath);
                    return 1;
                }
            }
        }
        return 0;
    }
}
