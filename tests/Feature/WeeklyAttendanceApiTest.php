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
     * TEST + EXPORT CSV: Jalankan seminggu lalu export ke CSV
     * ============================================================
     */
    public function test_full_week_dan_export_csv()
    {
        foreach ($this->weekDays as $dayIso => $day) {
            $this->runDayFlow($day['date'], $day['label'], $day['isFriday']);
        }

        // Ambil semua record presensi minggu ini
        $records = AttendancesPegawai::where('pegawai_id', $this->user->id)
            ->orderBy('date_attendance')
            ->get();

        $this->assertCount(5, $records, 'Harus ada 5 record (Senin-Jumat)');

        // Definisi kolom CSV
        $headers = [
            'No',
            'Hari',
            'Tanggal',
            'Jam Masuk',
            'Status Masuk',
            'Menit Telat',
            'Foto Masuk',
            'Apel Pagi',
            'Apel Pagi At',
            'Foto Apel Pagi',
            'Pot. Apel Pagi',
            'Apel Sore',
            'Apel Sore At',
            'Foto Apel Sore',
            'Pot. Apel Sore',
            'Jam Pulang',
            'Status Pulang',
            'Foto Pulang',
            'Status Apel',
            'Total Potongan',
            'TPP Diterima',
        ];

        $csvPath = base_path('tests/hasil_presensi_mingguan.csv');
        $file = fopen($csvPath, 'w');

        // BOM for Excel UTF-8
        fwrite($file, "\xEF\xBB\xBF");
        fputcsv($file, $headers);

        $dayLabels = [
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
            4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 0 => 'Minggu'
        ];

        foreach ($records as $i => $r) {
            $carbonDate = \Carbon\Carbon::parse($r->date_attendance);
            $hari = $dayLabels[$carbonDate->dayOfWeek] ?? '-';

            fputcsv($file, [
                $i + 1,
                $hari,
                $carbonDate->format('Y-m-d'),
                $r->incoming_time,
                $r->status_masuk,
                $r->menit_telat_masuk ?? 0,
                $r->foto_absen_masuk_path ? '✅' : '❌',
                $r->status_apel_pagi ?? '-',
                $r->apel_pagi_at ?? '-',
                $r->foto_apel_pagi_path ? '✅' : '❌',
                $r->potongan_tidak_apel_pagi ?? 0,
                $r->status_apel_sore ?? '-',
                $r->apel_sore_at ?? '-',
                $r->foto_apel_sore_path ? '✅' : '❌',
                $r->potongan_tidak_apel_sore ?? 0,
                $r->outgoing_time,
                $r->status_pulang ?? '-',
                $r->foto_absen_pulang_path ? '✅' : '❌',
                $r->status_apel ?? '-',
                $r->total_potongan_tpp ?? 0,
                $r->tpp_diterima ?? 0,
            ]);
        }

        fclose($file);

        $this->assertFileExists($csvPath);
        echo "\n\n📄 CSV berhasil di-export ke: {$csvPath}\n";

        // Print tabel ringkasan ke console
        echo "\n┌─────┬─────────┬────────────┬──────────┬─────────────┬──────────────┬──────────────┬────────────┬──────────────┐\n";
        echo "│ No  │ Hari    │ Tanggal    │ Masuk    │ Status      │ Apel Pagi    │ Apel Sore    │ Pulang     │ Foto Lengkap │\n";
        echo "├─────┼─────────┼────────────┼──────────┼─────────────┼──────────────┼──────────────┼────────────┼──────────────┤\n";

        foreach ($records as $i => $r) {
            $carbonDate = \Carbon\Carbon::parse($r->date_attendance);
            $hari = str_pad($dayLabels[$carbonDate->dayOfWeek] ?? '-', 7);
            $tanggal = $carbonDate->format('Y-m-d');
            $masuk = str_pad($r->incoming_time ?? '-', 8);
            $status = str_pad($r->status_masuk ?? '-', 11);
            $apelPagi = str_pad($r->status_apel_pagi ?? '-', 12);
            $apelSore = str_pad($r->status_apel_sore ?? '-', 12);
            $pulang = str_pad($r->outgoing_time ?? '-', 10);

            $fotoOk = ($r->foto_absen_masuk_path && $r->foto_absen_pulang_path && $r->foto_apel_pagi_path);
            if ($carbonDate->dayOfWeek === 5) { // Jumat
                $fotoOk = $fotoOk && $r->foto_apel_sore_path;
            }
            $fotoStatus = str_pad($fotoOk ? '✅ Lengkap' : '❌ Kurang', 12);

            $no = str_pad($i + 1, 3);
            echo "│ {$no} │ {$hari} │ {$tanggal} │ {$masuk} │ {$status} │ {$apelPagi} │ {$apelSore} │ {$pulang} │ {$fotoStatus} │\n";
        }
        echo "└─────┴─────────┴────────────┴──────────┴─────────────┴──────────────┴──────────────┴────────────┴──────────────┘\n";
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

    // ================================================================
    // TEST DENGAN VARIASI POTONGAN + EXPORT CSV
    // ================================================================

    /**
     * Skenario seminggu dengan berbagai potongan:
     *
     * Senin  : Masuk tepat waktu, apel pagi ✅, pulang ✅         → Potongan 0
     * Selasa : Masuk TELAT 20 menit (Telat 1 = 15%), apel ✅, pulang ✅ → Potongan telat
     * Rabu   : Masuk tepat, TIDAK apel pagi, pulang ✅            → Potongan apel pagi
     * Kamis  : Masuk tepat, apel pagi ✅, TIDAK pulang            → Potongan pulang
     * Jumat  : Masuk TELAT 45 menit, TIDAK apel pagi, apel sore ✅, pulang ✅ → Potongan telat + apel pagi
     */
    public function test_full_week_dengan_potongan_export_csv()
    {
        $tpp = 1000000;
        $jmlHariKerja = 22;
        $tunjangan = $tpp / $jmlHariKerja; // 45454.545...

        // ═══════════════════════════════════════
        // SENIN: Sempurna, tidak ada potongan
        // ═══════════════════════════════════════
        Carbon::setTestNow('2026-05-04 07:15:00');
        $r1 = $this->actingAs($this->user, 'sanctum')->postJson('/api/checkinnew');
        $r1->assertStatus(200);
        $id1 = $r1->json('id_absen');
        $this->uploadFoto($id1, 'masuk', 'Senin');

        Carbon::setTestNow('2026-05-04 08:05:00');
        $this->actingAs($this->user, 'sanctum')->postJson('/api/apelpagi')->assertStatus(200);
        $this->uploadFoto($id1, 'apel_pagi', 'Senin');

        Carbon::setTestNow('2026-05-04 15:15:00');
        $this->actingAs($this->user, 'sanctum')->postJson('/api/checkoutnew')->assertStatus(200);
        $this->uploadFoto($id1, 'pulang', 'Senin');

        // ═══════════════════════════════════════
        // SELASA: Telat 20 menit (masuk jam 08:20, jam masuk = 08:00)
        // Telat 1 (1-30 menit) = 15% → potongan = 40% × tunjangan × 15%
        // ═══════════════════════════════════════
        Carbon::setTestNow('2026-05-05 08:20:00'); // telat 20 menit
        $r2 = $this->actingAs($this->user, 'sanctum')->postJson('/api/checkinnew');
        $r2->assertStatus(200);
        $id2 = $r2->json('id_absen');
        $this->uploadFoto($id2, 'masuk', 'Selasa');

        Carbon::setTestNow('2026-05-05 08:20:01'); // masih dalam window apel (max 08:15 sudah lewat)
        // Apel pagi TIDAK dilakukan (lewat jendela)

        Carbon::setTestNow('2026-05-05 15:15:00');
        $this->actingAs($this->user, 'sanctum')->postJson('/api/checkoutnew')->assertStatus(200);
        $this->uploadFoto($id2, 'pulang', 'Selasa');

        // ═══════════════════════════════════════
        // RABU: Masuk tepat waktu, TIDAK apel pagi, pulang normal
        // Potongan apel pagi = 40% × tunjangan × 20%
        // ═══════════════════════════════════════
        Carbon::setTestNow('2026-05-06 07:50:00');
        $r3 = $this->actingAs($this->user, 'sanctum')->postJson('/api/checkinnew');
        $r3->assertStatus(200);
        $id3 = $r3->json('id_absen');
        $this->uploadFoto($id3, 'masuk', 'Rabu');

        // Sengaja TIDAK apel pagi

        Carbon::setTestNow('2026-05-06 15:15:00');
        $this->actingAs($this->user, 'sanctum')->postJson('/api/checkoutnew')->assertStatus(200);
        $this->uploadFoto($id3, 'pulang', 'Rabu');

        // ═══════════════════════════════════════
        // KAMIS: Masuk tepat, apel pagi ✅, TIDAK pulang
        // Potongan pulang = 40% × tunjangan × 20%
        // ═══════════════════════════════════════
        Carbon::setTestNow('2026-05-07 07:45:00');
        $r4 = $this->actingAs($this->user, 'sanctum')->postJson('/api/checkinnew');
        $r4->assertStatus(200);
        $id4 = $r4->json('id_absen');
        $this->uploadFoto($id4, 'masuk', 'Kamis');

        Carbon::setTestNow('2026-05-07 08:05:00');
        $this->actingAs($this->user, 'sanctum')->postJson('/api/apelpagi')->assertStatus(200);
        $this->uploadFoto($id4, 'apel_pagi', 'Kamis');

        // Sengaja TIDAK pulang

        // ═══════════════════════════════════════
        // JUMAT: Telat 45 menit (Telat 2 = 30%), TIDAK apel pagi, apel sore ✅
        // Potongan telat + apel pagi (Jumat: apel persen dibagi 2)
        // ═══════════════════════════════════════
        Carbon::setTestNow('2026-05-08 08:45:00'); // telat 45 menit
        $r5 = $this->actingAs($this->user, 'sanctum')->postJson('/api/checkinnew');
        $r5->assertStatus(200);
        $id5 = $r5->json('id_absen');
        $this->uploadFoto($id5, 'masuk', 'Jumat');

        // Sengaja TIDAK apel pagi (sudah lewat window 08:15)

        Carbon::setTestNow('2026-05-08 16:05:00');
        $this->actingAs($this->user, 'sanctum')->postJson('/api/apelsore')->assertStatus(200);
        $this->uploadFoto($id5, 'apel_sore', 'Jumat');

        Carbon::setTestNow('2026-05-08 16:15:00');
        $this->actingAs($this->user, 'sanctum')->postJson('/api/checkoutnew')->assertStatus(200);
        $this->uploadFoto($id5, 'pulang', 'Jumat');

        // ═══════════════════════════════════════
        // JALANKAN CRON → menghitung potongan
        // ═══════════════════════════════════════
        foreach (['2026-05-04', '2026-05-05', '2026-05-06', '2026-05-07', '2026-05-08'] as $date) {
            Carbon::setTestNow("{$date} 23:00:00");
            $this->runCron();
        }

        // ═══════════════════════════════════════
        // VERIFIKASI & EXPORT CSV
        // ═══════════════════════════════════════
        $records = AttendancesPegawai::where('pegawai_id', $this->user->id)
            ->orderBy('date_attendance')
            ->get();

        $this->assertCount(5, $records, 'Harus ada 5 record');

        // --- SENIN: Potongan = 0 ---
        $senin = $records[0];
        $this->assertEquals(0, (int) $senin->total_potongan_tpp, 'Senin: total potongan harus 0');
        $this->assertEquals('Hadir', $senin->status_apel_pagi, 'Senin: apel pagi hadir');
        $this->assertEquals('Pulang', $senin->status_pulang, 'Senin: sudah pulang');

        // --- SELASA: Telat 20 menit = Telat 1 (15%) ---
        $selasa = $records[1];
        $expectedTelatSelasa = (int)($tunjangan * 0.4 * 0.15);
        $this->assertGreaterThan(0, (int) $selasa->potongan_absen_masuk, 'Selasa: harus ada potongan telat');
        $this->assertEqualsWithDelta($expectedTelatSelasa, (int) $selasa->potongan_absen_masuk, 2,
            'Selasa: potongan telat = 40% × tunjangan × 15%');

        // --- RABU: Tidak apel pagi (20%) ---
        $rabu = $records[2];
        $expectedApelRabu = (int)($tunjangan * 0.4 * 0.20);
        $this->assertGreaterThan(0, (int) $rabu->potongan_tidak_apel_pagi, 'Rabu: harus ada potongan apel pagi');
        $this->assertEqualsWithDelta($expectedApelRabu, (int) $rabu->potongan_tidak_apel_pagi, 2,
            'Rabu: potongan apel = 40% × tunjangan × 20%');

        // --- KAMIS: Tidak pulang (20%) ---
        $kamis = $records[3];
        $expectedPulangKamis = (int)($tunjangan * 0.4 * 0.20);
        $this->assertGreaterThan(0, (int) $kamis->potongan_absen_pulang, 'Kamis: harus ada potongan pulang');
        $this->assertEqualsWithDelta($expectedPulangKamis, (int) $kamis->potongan_absen_pulang, 2,
            'Kamis: potongan pulang = 40% × tunjangan × 20%');

        // --- JUMAT: Telat 45 menit (Telat 2 = 30%) + Tidak apel pagi (20%/2=10% Jumat) ---
        $jumat = $records[4];
        $expectedTelatJumat = (int)($tunjangan * 0.4 * 0.30);
        $expectedApelJumat = (int)($tunjangan * 0.4 * 0.10); // Jumat: persen apel / 2
        $this->assertGreaterThan(0, (int) $jumat->potongan_absen_masuk, 'Jumat: harus ada potongan telat');
        $this->assertGreaterThan(0, (int) $jumat->potongan_tidak_apel_pagi, 'Jumat: harus ada potongan apel pagi');
        $this->assertEquals(0, (int) $jumat->potongan_tidak_apel_sore, 'Jumat: apel sore hadir, potongan = 0');

        // ═══════════════════════════════════════
        // EXPORT CSV
        // ═══════════════════════════════════════
        $csvPath = base_path('tests/hasil_presensi_dengan_potongan.csv');
        $file = fopen($csvPath, 'w');
        fwrite($file, "\xEF\xBB\xBF");

        $headers = [
            'No', 'Hari', 'Tanggal',
            'Jam Masuk', 'Status Masuk', 'Menit Telat', 'Pot. Telat',
            'Foto Masuk',
            'Apel Pagi', 'Apel Pagi At', 'Foto Apel Pagi', 'Pot. Apel Pagi',
            'Apel Sore', 'Apel Sore At', 'Foto Apel Sore', 'Pot. Apel Sore',
            'Jam Pulang', 'Status Pulang', 'Foto Pulang', 'Pot. Pulang',
            'Total Potongan', 'TPP Harian', 'TPP Diterima',
            'Skenario'
        ];
        fputcsv($file, $headers);

        $dayLabels = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat'];
        $scenarios = [
            'Sempurna (no deduction)',
            'Telat 20 menit + tidak apel (lewat window)',
            'Tidak apel pagi',
            'Tidak pulang',
            'Telat 45 menit + tidak apel pagi (Jumat)'
        ];

        foreach ($records as $i => $r) {
            $cd = \Carbon\Carbon::parse($r->date_attendance);
            fputcsv($file, [
                $i + 1,
                $dayLabels[$cd->dayOfWeekIso] ?? '-',
                $cd->format('Y-m-d'),
                $r->incoming_time,
                $r->status_masuk,
                $r->menit_telat_masuk ?? 0,
                $r->potongan_absen_masuk ?? 0,
                $r->foto_absen_masuk_path ? '✅' : '❌',
                $r->status_apel_pagi ?? '-',
                $r->apel_pagi_at ?? '-',
                $r->foto_apel_pagi_path ? '✅' : '❌',
                $r->potongan_tidak_apel_pagi ?? 0,
                $r->status_apel_sore ?? '-',
                $r->apel_sore_at ?? '-',
                $r->foto_apel_sore_path ? '✅' : '❌',
                $r->potongan_tidak_apel_sore ?? 0,
                $r->outgoing_time ?? '-',
                $r->status_pulang ?? '-',
                $r->foto_absen_pulang_path ? '✅' : '❌',
                $r->potongan_absen_pulang ?? 0,
                $r->total_potongan_tpp ?? 0,
                number_format($tunjangan, 0, ',', '.'),
                $r->tpp_diterima ?? 0,
                $scenarios[$i] ?? '',
            ]);
        }
        fclose($file);
        $this->assertFileExists($csvPath);

        // ═══════════════════════════════════════
        // PRINT TABEL KE CONSOLE
        // ═══════════════════════════════════════
        echo "\n\n📄 CSV: {$csvPath}\n";
        echo "\n╔════╦═════════╦════════════╦══════════════╦════════════════╦══════════════╦══════════════╦══════════════╦═══════════════╦═══════════════╗\n";
        echo "║ No ║ Hari    ║ Tanggal    ║ Status Masuk ║ Pot. Telat (Rp)║ Apel Pagi    ║ Pot.AP (Rp)  ║ Status Plg   ║ Pot.Plg (Rp)  ║ Total Pot(Rp) ║\n";
        echo "╠════╬═════════╬════════════╬══════════════╬════════════════╬══════════════╬══════════════╬══════════════╬═══════════════╬═══════════════╣\n";

        foreach ($records as $i => $r) {
            $cd = \Carbon\Carbon::parse($r->date_attendance);
            $no = str_pad($i + 1, 2);
            $hari = str_pad($dayLabels[$cd->dayOfWeekIso] ?? '-', 7);
            $tgl = $cd->format('Y-m-d');
            $stMasuk = str_pad($r->status_masuk ?? '-', 12);
            $potTelat = str_pad(number_format($r->potongan_absen_masuk ?? 0, 0, ',', '.'), 14);
            $apPagi = str_pad($r->status_apel_pagi ?? '-', 12);
            $potAP = str_pad(number_format($r->potongan_tidak_apel_pagi ?? 0, 0, ',', '.'), 12);
            $stPulang = str_pad($r->status_pulang ?? '-', 12);
            $potPlg = str_pad(number_format($r->potongan_absen_pulang ?? 0, 0, ',', '.'), 13);
            $totalPot = str_pad(number_format($r->total_potongan_tpp ?? 0, 0, ',', '.'), 13);

            echo "║ {$no} ║ {$hari} ║ {$tgl} ║ {$stMasuk} ║ {$potTelat} ║ {$apPagi} ║ {$potAP} ║ {$stPulang} ║ {$potPlg} ║ {$totalPot} ║\n";
        }
        echo "╚════╩═════════╩════════════╩══════════════╩════════════════╩══════════════╩══════════════╩══════════════╩═══════════════╩═══════════════╝\n";

        $totalPotSemua = $records->sum('total_potongan_tpp');
        $totalDiterima = $records->sum('tpp_diterima');
        echo "\n💰 Total Potongan Seminggu : Rp " . number_format($totalPotSemua, 0, ',', '.') . "\n";
        echo "💰 Total TPP Diterima     : Rp " . number_format($totalDiterima, 0, ',', '.') . "\n";
        echo "💰 TPP Harian             : Rp " . number_format($tunjangan, 0, ',', '.') . "\n";
    }
}
