<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Pegawai;
use Tests\Traits\CronTestSetup;
use Carbon\Carbon;
use App\Models\JadwalApel;
use App\Models\AttendancesPegawai;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FullDayAttendanceApiTest extends TestCase
{
    use RefreshDatabase, CronTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCron();
        Storage::fake('public');
    }

    public function test_full_day_attendance_flow()
    {
        $user = Pegawai::factory()->create([
            'tpp' => 1000000,
            'dinas_id' => 1,
            'active' => 1
        ]);

        // Mock jadwal apel pagi dan sore untuk hari Jumat
        // Karena secara default sistem membatasi apel sore hanya hari Jumat
        JadwalApel::updateOrCreate([
            'dinas_id' => 1,
            'hari' => 5 // Jumat
        ], [
            'apel_pagi' => 1,
            'jam_apel_pagi' => '07:00:00',
            'max_apel_pagi' => '08:00:00',
            'apel_sore' => 1,
            'jam_apel_sore' => '15:00:00',
            'max_apel_sore' => '16:00:00',
        ]);

        // ==========================================
        // 1. Absen Masuk (07:15)
        // ==========================================
        Carbon::setTestNow('2026-03-06 07:15:00'); // Jumat, 6 Maret 2026
        
        $responseMasuk = $this->actingAs($user, 'sanctum')->postJson('/api/checkinnew');
        $responseMasuk->assertStatus(200);
        $responseMasuk->assertJson(['message' => 'Presensi berhasil']);
        
        $idAbsen = $responseMasuk->json('id_absen');
        $this->assertNotNull($idAbsen);

        $absenDebug = AttendancesPegawai::find($idAbsen);
        \Illuminate\Support\Facades\Log::info('DEBUG ABSEN', [
            'incoming_time' => $absenDebug->incoming_time,
            'status_masuk' => $absenDebug->status_masuk,
            'menit_telat_masuk' => $absenDebug->menit_telat_masuk,
            'potongan_absen_masuk' => $absenDebug->potongan_absen_masuk
        ]);

        // ==========================================
        // 2. Upload Foto Masuk (07:16)
        // ==========================================
        Carbon::setTestNow('2026-03-06 07:16:00');
        $fotoMasuk = UploadedFile::fake()->image('masuk.jpg');
        $responseUploadMasuk = $this->actingAs($user, 'sanctum')->postJson('/api/upload_foto_absen', [
            'id_absen' => $idAbsen,
            'jenis' => 'masuk',
            'file' => $fotoMasuk
        ]);
        $responseUploadMasuk->assertStatus(200);
        $responseUploadMasuk->assertJson(['message' => 'Foto berhasil diupload']);

        // ==========================================
        // 3. Apel Pagi (07:30)
        // ==========================================
        Carbon::setTestNow('2026-03-06 07:30:00');
        $responseApelPagi = $this->actingAs($user, 'sanctum')->postJson('/api/apelpagi');
        $responseApelPagi->assertStatus(200);
        $responseApelPagi->assertJson(['message' => 'Presensi Apel Pagi Berhasil']);

        // ==========================================
        // 4. Upload Foto Apel Pagi (07:31)
        // ==========================================
        Carbon::setTestNow('2026-03-06 07:31:00');
        $fotoApelPagi = UploadedFile::fake()->image('apel_pagi.jpg');
        $responseUploadApelPagi = $this->actingAs($user, 'sanctum')->postJson('/api/upload_foto_absen', [
            'id_absen' => $idAbsen,
            'jenis' => 'apel_pagi',
            'file' => $fotoApelPagi
        ]);
        $responseUploadApelPagi->assertStatus(200);
        $responseUploadApelPagi->assertJson(['message' => 'Foto berhasil diupload']);

        // ==========================================
        // 5. Apel Sore (15:30)
        // ==========================================
        Carbon::setTestNow('2026-03-06 15:30:00');
        $responseApelSore = $this->actingAs($user, 'sanctum')->postJson('/api/apelsore');
        $responseApelSore->assertStatus(200);
        $responseApelSore->assertJson(['message' => 'Presensi Apel Sore Berhasil']);

        // ==========================================
        // 6. Upload Foto Apel Sore (15:31)
        // ==========================================
        Carbon::setTestNow('2026-03-06 15:31:00');
        $fotoApelSore = UploadedFile::fake()->image('apel_sore.jpg');
        $responseUploadApelSore = $this->actingAs($user, 'sanctum')->postJson('/api/upload_foto_absen', [
            'id_absen' => $idAbsen,
            'jenis' => 'apel_sore',
            'file' => $fotoApelSore
        ]);
        $responseUploadApelSore->assertStatus(200);
        $responseUploadApelSore->assertJson(['message' => 'Foto berhasil diupload']);

        // ==========================================
        // 7. Absen Pulang (16:15)
        // ==========================================
        Carbon::setTestNow('2026-03-06 16:15:00');
        $responsePulang = $this->actingAs($user, 'sanctum')->postJson('/api/checkoutnew');
        $responsePulang->assertStatus(200);
        $responsePulang->assertJson(['message' => 'Presensi pulang berhasil']);

        // ==========================================
        // 8. Upload Foto Pulang (16:16)
        // ==========================================
        Carbon::setTestNow('2026-03-06 16:16:00');
        $fotoPulang = UploadedFile::fake()->image('pulang.jpg');
        $responseUploadPulang = $this->actingAs($user, 'sanctum')->postJson('/api/upload_foto_absen', [
            'id_absen' => $idAbsen,
            'jenis' => 'pulang',
            'file' => $fotoPulang
        ]);
        $responseUploadPulang->assertStatus(200);
        $responseUploadPulang->assertJson(['message' => 'Foto berhasil diupload']);

        // ==========================================
        // VERIFIKASI AKHIR DATABASE
        // ==========================================
        $absen = AttendancesPegawai::find($idAbsen);
        
        // Cek kelengkapan status
        $this->assertEquals('Masuk', $absen->status_masuk);
        $this->assertEquals('Pulang', $absen->status_pulang);
        $this->assertEquals('Hadir', $absen->status_apel_pagi);
        $this->assertEquals('Hadir', $absen->status_apel_sore);
        $this->assertEquals('Hadir', $absen->status_apel);

        // Cek kelengkapan foto
        $this->assertNotNull($absen->foto_absen_masuk_path);
        $this->assertNotNull($absen->foto_absen_pulang_path);
        $this->assertNotNull($absen->foto_apel_pagi_path);
        $this->assertNotNull($absen->foto_apel_sore_path);
    }
}
