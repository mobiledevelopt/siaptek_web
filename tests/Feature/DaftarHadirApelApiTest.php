<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Pegawai;
use Tests\Traits\CronTestSetup;
use Carbon\Carbon;
use App\Models\JadwalApel;

class DaftarHadirApelApiTest extends TestCase
{
    use RefreshDatabase, CronTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCron();
    }

    public function test_apel_pagi_and_sore_scenarios()
    {
        $user = Pegawai::factory()->create([
            'tpp' => 1000000,
            'dinas_id' => 1,
            'active' => 1
        ]);

        // Mock jadwal apel pagi dan sore untuk hari ini (Rabu, 2026-03-04)
        JadwalApel::updateOrCreate([
            'dinas_id' => 1,
            'hari' => 3 // Rabu
        ], [
            'apel_pagi' => 1,
            'jam_apel_pagi' => '07:00:00',
            'max_apel_pagi' => '08:00:00',
            'apel_sore' => 0 // Set 0 dulu
        ]);

        Carbon::setTestNow('2026-03-04 07:30:00');

        // Skenario 1: Apel pagi sebelum clock in (Should fail gracefully)
        $response1 = $this->actingAs($user, 'sanctum')->postJson('/api/apelpagi');
        $response1->assertStatus(200);
        $response1->assertJson([
            'message' => 'Anda belum presensi masuk'
        ]);

        // Clock In dulu
        Carbon::setTestNow('2026-03-04 07:00:00');
        $this->actingAs($user, 'sanctum')->postJson('/api/checkinnew');

        // Skenario 2: Apel Pagi Normal
        Carbon::setTestNow('2026-03-04 07:30:00');
        $response2 = $this->actingAs($user, 'sanctum')->postJson('/api/apelpagi');
        $response2->assertStatus(200);
        $response2->assertJson([
            'message' => 'Presensi Apel Pagi Berhasil'
        ]);
        $this->assertArrayHasKey('id_absen', $response2->json());

        // Skenario 3: Apel Pagi Duplicate (Should fail gracefully)
        $response3 = $this->actingAs($user, 'sanctum')->postJson('/api/apelpagi');
        $response3->assertStatus(200);
        $response3->assertJson([
            'message' => 'Anda sudah presensi apel pagi'
        ]);
        
        // Skenario 4: Apel Sore tapi tidak ada jadwal
        Carbon::setTestNow('2026-03-04 15:30:00');
        $response4 = $this->actingAs($user, 'sanctum')->postJson('/api/apelsore');
        $response4->assertStatus(200);
        // Error from Validator::apel
        // In the mock, apel_sore is 0, but is it Friday?
        // AttendanceValidator only allows apel sore on Friday. Let's see the response.
    }
}
