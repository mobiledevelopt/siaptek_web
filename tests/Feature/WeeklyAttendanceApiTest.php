<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Pegawai;
use App\Models\JadwalApel;
use App\Models\AttendancesPegawai;
use Tests\Traits\CronTestSetup;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Test presensi lengkap selama 1 minggu (Senin - Jumat)
 * 
 * Mencakup:
 * - Clock in + upload foto masuk
 * - Apel pagi + upload foto apel pagi
 * - Apel sore + upload foto apel sore (khusus Jumat)
 * - Clock out + upload foto pulang
 * - Verifikasi semua kolom database terisi dengan benar
 */
class WeeklyAttendanceApiTest extends TestCase
{
    use RefreshDatabase, CronTestSetup;

    protected $user;

    // Senin 4 Mei 2026 s/d Jumat 8 Mei 2026
    protected array $weekDays = [
        1 => ['date' => '2026-05-04', 'label' => 'Senin',  'isFriday' => false],
        2 => ['date' => '2026-05-05', 'label' => 'Selasa', 'isFriday' => false],
        3 => ['date' => '2026-05-06', 'label' => 'Rabu',   'isFriday' => false],
        4 => ['date' => '2026-05-07', 'label' => 'Kamis',  'isFriday' => false],
        5 => ['date' => '2026-05-08', 'label' => 'Jumat',  'isFriday' => true],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCron();
        Storage::fake('public');

        $this->user = Pegawai::factory()->create([
            'tpp' => 1000000,
            'dinas_id' => 1,
            'active' => 1
        ]);
    }

    /**
     * ============================================================
     * TEST UTAMA: Full week Senin - Jumat
     * ============================================================
     */
    public function test_full_week_attendance_senin_sampai_jumat()
    {
        foreach ($this->weekDays as $dayIso => $day) {
            $this->runDayFlow($day['date'], $day['label'], $day['isFriday']);
        }
    }

    /**
     * ============================================================
     * TEST PER HARI: Untuk debugging jika ada yang gagal
     * ============================================================
     */
    public function test_senin_full_flow()
    {
        $this->runDayFlow('2026-05-04', 'Senin', false);
    }

    public function test_selasa_full_flow()
    {
        $this->runDayFlow('2026-05-05', 'Selasa', false);
    }

    public function test_rabu_full_flow()
    {
        $this->runDayFlow('2026-05-06', 'Rabu', false);
    }

    public function test_kamis_full_flow()
    {
        $this->runDayFlow('2026-05-07', 'Kamis', false);
    }

    public function test_jumat_full_flow_with_apel_sore()
    {
        $this->runDayFlow('2026-05-08', 'Jumat', true);
    }

    // ================================================================
    // FLOW HELPER
    // ================================================================

    private function runDayFlow(string $date, string $label, bool $isFriday): void
    {
        // ── 1. CLOCK IN (07:15) ──
        Carbon::setTestNow("{$date} 07:15:00");
        $responseMasuk = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkinnew');
        $responseMasuk->assertStatus(200, "[$label] Clock in gagal: " . $responseMasuk->content());
        $responseMasuk->assertJsonFragment(['message' => 'Presensi berhasil']);
        
        $idAbsen = $responseMasuk->json('id_absen');
        $this->assertNotNull($idAbsen, "[$label] id_absen null setelah clock in");

        // ── 2. UPLOAD FOTO MASUK (07:16) ──
        Carbon::setTestNow("{$date} 07:16:00");
        $this->uploadFoto($idAbsen, 'masuk', $label);

        // ── 3. APEL PAGI (08:05) ──
        Carbon::setTestNow("{$date} 08:05:00");
        $responseApelPagi = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/apelpagi');
        $responseApelPagi->assertStatus(200, "[$label] Apel pagi gagal: " . $responseApelPagi->content());
        $responseApelPagi->assertJsonFragment(['message' => 'Presensi Apel Pagi Berhasil']);

        // ── 4. UPLOAD FOTO APEL PAGI (08:06) ──
        Carbon::setTestNow("{$date} 08:06:00");
        $this->uploadFoto($idAbsen, 'apel_pagi', $label);

        // ── 5. APEL SORE (khusus Jumat) ──
        if ($isFriday) {
            Carbon::setTestNow("{$date} 16:05:00");
            $responseApelSore = $this->actingAs($this->user, 'sanctum')
                ->postJson('/api/apelsore');
            $responseApelSore->assertStatus(200, "[$label] Apel sore gagal: " . $responseApelSore->content());
            $responseApelSore->assertJsonFragment(['message' => 'Presensi Apel Sore Berhasil']);

            // ── 6. UPLOAD FOTO APEL SORE ──
            Carbon::setTestNow("{$date} 16:06:00");
            $this->uploadFoto($idAbsen, 'apel_sore', $label);
        }

        // ── 7. CLOCK OUT ──
        $jamPulang = $isFriday ? '16:15:00' : '15:15:00';
        Carbon::setTestNow("{$date} {$jamPulang}");
        $responsePulang = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkoutnew');
        $responsePulang->assertStatus(200, "[$label] Clock out gagal: " . $responsePulang->content());
        $responsePulang->assertJsonFragment(['message' => 'Presensi pulang berhasil']);

        // ── 8. UPLOAD FOTO PULANG ──
        Carbon::setTestNow("{$date} " . ($isFriday ? '16:16:00' : '15:16:00'));
        $this->uploadFoto($idAbsen, 'pulang', $label);

        // ══════════════════════════════════════
        // VERIFIKASI DATABASE
        // ══════════════════════════════════════
        $absen = AttendancesPegawai::find($idAbsen);
        $this->assertNotNull($absen, "[$label] Record absen tidak ditemukan di DB");

        // Status
        $this->assertEquals('Masuk', $absen->status_masuk, "[$label] status_masuk salah");
        $this->assertEquals('Pulang', $absen->status_pulang, "[$label] status_pulang salah");
        $this->assertEquals('Hadir', $absen->status_apel_pagi, "[$label] status_apel_pagi salah");
        $this->assertEquals('Hadir', $absen->status_apel, "[$label] status_apel salah");

        // Timestamp
        $this->assertNotNull($absen->incoming_time, "[$label] incoming_time null");
        $this->assertNotNull($absen->outgoing_time, "[$label] outgoing_time null");
        $this->assertNotNull($absen->apel_pagi_at, "[$label] apel_pagi_at null");

        // Foto
        $this->assertNotNull($absen->foto_absen_masuk_path, "[$label] foto_absen_masuk_path null");
        $this->assertNotNull($absen->foto_absen_pulang_path, "[$label] foto_absen_pulang_path null");
        $this->assertNotNull($absen->foto_apel_pagi_path, "[$label] foto_apel_pagi_path null");

        // Potongan apel pagi = 0 (karena hadir)
        $this->assertEquals(0, $absen->potongan_tidak_apel_pagi, "[$label] potongan apel pagi harus 0");

        if ($isFriday) {
            $this->assertEquals('Hadir', $absen->status_apel_sore, "[$label] status_apel_sore salah");
            $this->assertNotNull($absen->apel_sore_at, "[$label] apel_sore_at null");
            $this->assertNotNull($absen->foto_apel_sore_path, "[$label] foto_apel_sore_path null");
            $this->assertEquals(0, $absen->potongan_tidak_apel_sore, "[$label] potongan apel sore harus 0");
        }

        // Date
        $this->assertStringContainsString($date, $absen->date_attendance, "[$label] date_attendance salah");

        // Reset Carbon agar tidak bocor ke hari berikutnya
        Carbon::setTestNow();
    }

    private function uploadFoto(int $idAbsen, string $jenis, string $label): void
    {
        $foto = UploadedFile::fake()->image("{$jenis}.jpg");
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/upload_foto_absen', [
                'id_absen' => $idAbsen,
                'jenis'    => $jenis,
                'file'     => $foto
            ]);
        $response->assertStatus(200, "[$label] Upload foto {$jenis} gagal: " . $response->content());
        $response->assertJsonFragment(['message' => 'Foto berhasil diupload']);
    }

    // ================================================================
    // EDGE CASES
    // ================================================================

    /**
     * Duplicate clock in harus gagal
     */
    public function test_double_clock_in_harus_ditolak()
    {
        Carbon::setTestNow('2026-05-04 07:15:00'); // Senin

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkinnew')
            ->assertStatus(200);

        // Coba clock in lagi
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkinnew');
        
        $this->assertStringContainsString('sudah presensi', strtolower($response->json('message')));
    }

    /**
     * Apel pagi sebelum clock in harus gagal
     */
    public function test_apel_pagi_sebelum_clock_in_harus_ditolak()
    {
        Carbon::setTestNow('2026-05-04 08:05:00'); // Senin

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/apelpagi');
        
        $this->assertStringContainsString('belum', strtolower($response->json('message')));
    }

    /**
     * Double apel pagi harus ditolak
     */
    public function test_double_apel_pagi_harus_ditolak()
    {
        Carbon::setTestNow('2026-05-04 07:15:00'); // Senin
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkinnew')
            ->assertStatus(200);

        Carbon::setTestNow('2026-05-04 08:05:00');
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/apelpagi')
            ->assertStatus(200);

        // Coba apel pagi lagi
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/apelpagi');

        $this->assertStringContainsString('sudah', strtolower($response->json('message')));
    }

    /**
     * Apel sore di hari bukan Jumat harus ditolak
     */
    public function test_apel_sore_di_hari_selasa_harus_ditolak()
    {
        Carbon::setTestNow('2026-05-05 07:15:00'); // Selasa
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkinnew')
            ->assertStatus(200);

        Carbon::setTestNow('2026-05-05 16:05:00');
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/apelsore');

        // Harus ditolak karena bukan Jumat
        $this->assertNotEquals('Presensi Apel Sore Berhasil', $response->json('message'));
    }

    /**
     * Weekend (Sabtu) harus ditolak
     */
    public function test_clock_in_sabtu_harus_ditolak()
    {
        Carbon::setTestNow('2026-05-09 07:15:00'); // Sabtu

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkinnew');

        $this->assertStringContainsString('libur', strtolower($response->json('message')));
    }

    /**
     * Weekend (Minggu) harus ditolak
     */
    public function test_clock_in_minggu_harus_ditolak()
    {
        Carbon::setTestNow('2026-05-10 07:15:00'); // Minggu

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkinnew');

        $this->assertStringContainsString('libur', strtolower($response->json('message')));
    }
}
