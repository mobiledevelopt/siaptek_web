<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Pegawai;
use Tests\Traits\CronTestSetup;
use Carbon\Carbon;

class PegawaiControllerNewApiTest extends TestCase
{
    use RefreshDatabase, CronTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        // Set the current time to a valid clock in time (Wednesday, 08:00:00)
        Carbon::setTestNow('2026-03-04 08:00:00');
        $this->setUpCron();
    }

    public function test_clock_in_new_success_and_duplicate()
    {
        $user = Pegawai::factory()->create([
            'tpp' => 1000000,
            'dinas_id' => 1,
            'active' => 1
        ]);

        // Skenario 1: First Clock In
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/checkinnew');
        
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Presensi berhasil'
        ]);
        $this->assertArrayHasKey('id_absen', $response->json());

        // Skenario 2: Duplicate Clock In (Should be caught by catch block and return 200)
        $response2 = $this->actingAs($user, 'sanctum')->postJson('/api/checkinnew');
        
        $response2->assertStatus(200);
        $response2->assertJson([
            'message' => 'Anda sudah presensi masuk'
        ]);

        // Skenario 3: Sedang Izin (Should be blocked by sedangIzin validation)
        $userIzin = Pegawai::factory()->create([
            'tpp' => 1000000,
            'dinas_id' => 1,
            'active' => 1
        ]);
        \App\Models\IzinPegawai::create([
            'pegawai_id' => $userIzin->id,
            'tgl' => '2026-03-04',
            'sampai_tgl' => '2026-03-06',
            'status' => 'Pengajuan',
            'jenis_izin_id' => 1,
            'dinas_id' => 1
        ]);

        $responseIzin = $this->actingAs($userIzin, 'sanctum')->postJson('/api/checkinnew');
        $responseIzin->assertStatus(200);
        $responseIzin->assertJson([
            'message' => 'Anda tidak bisa presensi karena sedang izin/cuti (sedang dalam proses pengajuan)'
        ]);
    }

    public function test_clock_out_new_scenarios()
    {
        $user = Pegawai::factory()->create([
            'tpp' => 1000000,
            'dinas_id' => 1,
            'active' => 1
        ]);

        // Skenario 3: Clock out before clock in
        // Set time to afternoon (e.g., 16:00:00)
        Carbon::setTestNow('2026-03-04 16:00:00');

        $responseOutEarly = $this->actingAs($user, 'sanctum')->postJson('/api/checkoutnew');
        $responseOutEarly->assertStatus(200);
        $responseOutEarly->assertJson([
            'message' => 'Anda belum presensi masuk'
        ]);

        // Reset to morning to clock in first
        Carbon::setTestNow('2026-03-04 08:00:00');
        $this->actingAs($user, 'sanctum')->postJson('/api/checkinnew');

        // Skenario 4: Normal Clock Out
        Carbon::setTestNow('2026-03-04 16:00:00');
        $responseOutNormal = $this->actingAs($user, 'sanctum')->postJson('/api/checkoutnew');
        
        $responseOutNormal->assertStatus(200);
        $responseOutNormal->assertJson([
            'message' => 'Presensi pulang berhasil'
        ]);
        $this->assertArrayHasKey('id_absen', $responseOutNormal->json());

        // Skenario 5: Duplicate Clock Out
        $responseOutDuplicate = $this->actingAs($user, 'sanctum')->postJson('/api/checkoutnew');
        
        $responseOutDuplicate->assertStatus(200);
        $responseOutDuplicate->assertJson([
            'message' => 'Anda sudah presensi pulang'
        ]);
    }

    public function test_upload_foto_absen()
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $user = Pegawai::factory()->create([
            'tpp' => 1000000,
            'dinas_id' => 1,
            'active' => 1
        ]);

        // Clock In first to get an ID
        $clockInResponse = $this->actingAs($user, 'sanctum')->postJson('/api/checkinnew');
        $idAbsen = $clockInResponse->json('id_absen');

        // Create a dummy image
        $file = \Illuminate\Http\UploadedFile::fake()->image('selfie.jpg');

        // Test Upload Foto
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/upload_foto_absen', [
            'id_absen' => $idAbsen,
            'jenis' => 'masuk',
            'file' => $file
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Foto berhasil diupload'
        ]);

        // Verify the database record was updated
        $absen = \App\Models\AttendancesPegawai::find($idAbsen);
        $this->assertNotNull($absen->foto_absen_masuk_path);
        $this->assertNotNull($absen->foto_absen_masuk);
    }
}
