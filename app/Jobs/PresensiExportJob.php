<?php

namespace App\Jobs;

use App\Http\Controllers\PresensiPegawai;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;

class PresensiExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 7200;
    public $tries = 3;

    private $jobId;
    private $payload;

    public function __construct($jobId, array $payload)
    {
        $this->jobId = $jobId;
        $this->payload = $payload;
    }

    public function handle(PresensiPegawai $controller)
    {
        try {
            $result = $controller->processExportInQueue($this->payload);

            $this->writeStatus([
                'status' => 'success',
                'message' => $result['message'] ?? 'Data berhasil diexport',
                'url' => $result['url'] ?? null,
                'filename' => $result['filename'] ?? null,
                'files' => $result['files'] ?? [],
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('PresensiExportJob gagal: ' . $e->getMessage(), [
                'job_id' => $this->jobId,
                'exception' => $e->getTraceAsString(),
            ]);

            $this->writeStatus([
                'status' => 'failed',
                'message' => 'Export gagal diproses',
                'error' => $e->getMessage(),
            ]);

            // Jangan throw ulang - error sudah dicatat di status JSON dan log.
            // Throw menyebabkan MaxAttemptsExceededException yang menyembunyikan error asli.
        }
    }

    private function writeStatus(array $payload)
    {
        $statusPath = storage_path('app/public/export_presensi/jobs/' . $this->jobId . '.json');
        $statusDir = dirname($statusPath);

        if (!File::exists($statusDir)) {
            File::makeDirectory($statusDir, 0755, true);
        }

        $payload['job_id'] = $this->jobId;
        $payload['updated_at'] = now()->toDateTimeString();

        File::put($statusPath, json_encode($payload));
    }
}
