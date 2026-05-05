<?php

namespace Tests\Feature;

use App\Models\AttendancesPegawai;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CronTestSetup;

/**
 * ============================================================
 * SIMULASI LENGKAP CRON JOB AKHIR HARI
 * ============================================================
 * 
 * Test ini mensimulasikan apa yang terjadi ketika server
 * menjalankan `php artisan absen:cron` pada pukul 23:00
 * untuk berbagai skenario pegawai.
 * 
 * Skenario yang diuji:
 * 1. Pegawai ALFA (tidak absen sama sekali)
 * 2. Pegawai masuk tepat waktu, apel, pulang (sempurna)
 * 3. Pegawai masuk tapi TIDAK APEL & TIDAK PULANG
 * 4. Pegawai masuk TELAT, apel, pulang
 * 5. Pegawai sedang CUTI (harus di-skip)
 */
class CronJobSimulationTest extends TestCase
{
    use RefreshDatabase, CronTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCron();
    }

    // ============================================================
    // SKENARIO 1: ALFA (Tidak masuk sama sekali)
    // ============================================================
    public function test_skenario_alfa_tidak_masuk()
    {
        // Hari Jumat, 6 Maret 2026
        $this->setHariKerja('2026-03-06 23:00:00');
        $user = $this->createUser(['tpp' => 1000000]);

        // Pegawai TIDAK melakukan apa-apa sepanjang hari.
        // Cron berjalan jam 23:00...
        $this->runCron();

        $absen = AttendancesPegawai::where('pegawai_id', $user->id)->first();

        // ✅ Harus ada record
        $this->assertNotNull($absen);

        // ✅ Status = Tidak Masuk
        $this->assertEquals('Tidak Masuk', $absen->status);

        // ✅ Keterangan = Tanpa Keterangan (Alfa)
        $this->assertEquals('Tanpa Keterangan', $absen->ket_tidak_masuk_kerja);

        // ✅ Incoming & Outgoing = 00:00:00
        $this->assertEquals('00:00:00', $absen->incoming_time);
        $this->assertEquals('00:00:00', $absen->outgoing_time);

        // ✅ Potongan = 100% dari TPP harian
        $tunjanganHarian = 1000000 / 22; // ≈ Rp 45.454
        $this->assertEquals((int) round($tunjanganHarian), $absen->total_potongan_tpp);
        $this->assertEquals(0, $absen->tpp_diterima);
    }

    // ============================================================
    // SKENARIO 2: Pegawai Sempurna (Masuk, Apel, Pulang) di hari Jumat
    // ============================================================
    public function test_skenario_sempurna_masuk_apel_pulang()
    {
        // Hari Jumat, 6 Maret 2026
        // 07:50 - Pegawai absen masuk (tepat waktu, sebelum jam 08:00)
        $this->setHariKerja('2026-03-06 07:50:00');
        $user = $this->createUser(['tpp' => 1000000]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/checkinnew');

        // 08:05 - Apel pagi (dalam jendela 08:00-08:15)
        Carbon::setTestNow('2026-03-06 08:05:00');
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/apelpagi');

        // 16:00 - Apel sore (dalam jendela 16:00-16:15, hari Jumat)
        Carbon::setTestNow('2026-03-06 16:00:00');
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/apelsore');

        // 16:30 - Pulang (setelah jam pulang Jumat 15:30)
        Carbon::setTestNow('2026-03-06 16:30:00');
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/checkoutnew');

        // 23:00 - Cron berjalan
        Carbon::setTestNow('2026-03-06 23:00:00');
        $this->runCron();

        $absen = AttendancesPegawai::where('pegawai_id', $user->id)->first();

        // ✅ Semua status terisi sempurna
        $this->assertEquals('Masuk', $absen->status_masuk);
        $this->assertEquals('Pulang', $absen->status_pulang);
        $this->assertEquals('Hadir', $absen->status_apel_pagi);
        $this->assertEquals('Hadir', $absen->status_apel_sore);

        // ✅ Tidak ada potongan telat
        $this->assertEquals(0, $absen->menit_telat_masuk);

        // ✅ Total potongan = 0, TPP diterima = penuh
        $this->assertEquals(0, $absen->total_potongan_tpp);

        $tunjanganHarian = 1000000 / 22;
        $this->assertEquals((int) round($tunjanganHarian), $absen->tpp_diterima);
    }

    // ============================================================
    // SKENARIO 3: Masuk tapi TIDAK Apel & TIDAK Pulang (Jumat)
    // ============================================================
    public function test_skenario_masuk_tanpa_apel_tanpa_pulang()
    {
        // Hari Jumat, 6 Maret 2026
        // 07:50 - Pegawai absen masuk tepat waktu
        $this->setHariKerja('2026-03-06 07:50:00');
        $user = $this->createUser(['tpp' => 1000000]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/checkinnew');

        // Pegawai TIDAK apel pagi, TIDAK apel sore, TIDAK pulang.
        // 23:00 - Cron berjalan
        Carbon::setTestNow('2026-03-06 23:00:00');
        $this->runCron();

        $absen = AttendancesPegawai::where('pegawai_id', $user->id)->first();
        $tunjanganHarian = 1000000 / 22;
        $basis40Persen = $tunjanganHarian * 0.4; // ← INI KUNCI: basis potongan

        // ✅ Status masuk tetap aman
        $this->assertEquals('Masuk', $absen->status_masuk);
        $this->assertEquals(0, $absen->menit_telat_masuk);

        // ✅ Status pulang = dihukum
        $this->assertNotEquals('Pulang', $absen->status_pulang);

        // ✅ Status apel pagi = dihukum
        $this->assertEquals('Tidak Apel', $absen->status_apel_pagi);

        // ✅ Status apel sore = dihukum (Jumat)
        $this->assertEquals('Tidak Apel', $absen->status_apel_sore);

        // ✅ Potongan pulang harus ada (> 0)
        $this->assertGreaterThan(0, $absen->potongan_absen_pulang);

        // ✅ Potongan apel pagi harus ada
        $this->assertGreaterThan(0, $absen->potongan_tidak_apel_pagi);

        // ✅ Potongan apel sore harus ada (Jumat)
        $this->assertGreaterThan(0, $absen->potongan_tidak_apel_sore);

        // ==========================================================
        // 🔥 PEMBUKTIAN: Potongan dihitung dari 40% TPP Harian
        // ==========================================================
        // Rumus: potongan = (40% × TPP Harian) × (persentase_config / 100)
        // Config: pulang=20%, apel=20% (di Jumat dibagi 2 → 10% pagi, 10% sore)

        // Pulang: 40% × Rp 45.454 × 20% = Rp 3.636
        $expectedPulang = (int) round($basis40Persen * 20 / 100);
        $this->assertEqualsWithDelta($expectedPulang, $absen->potongan_absen_pulang, 1,
            "Potongan pulang harus = 40% × TPP Harian × 20%");

        // Apel Pagi (Jumat): 40% × Rp 45.454 × 10% = Rp 1.818
        $expectedApelPagi = (int) round($basis40Persen * 10 / 100);
        $this->assertEqualsWithDelta($expectedApelPagi, $absen->potongan_tidak_apel_pagi, 1,
            "Potongan apel pagi harus = 40% × TPP Harian × 10% (Jumat dibagi 2)");

        // Apel Sore (Jumat): 40% × Rp 45.454 × 10% = Rp 1.818
        $expectedApelSore = (int) round($basis40Persen * 10 / 100);
        $this->assertEqualsWithDelta($expectedApelSore, $absen->potongan_tidak_apel_sore, 1,
            "Potongan apel sore harus = 40% × TPP Harian × 10% (Jumat dibagi 2)");

        // 🔥 ANTI-REGRESSION: Pastikan potongan BUKAN dari 100% TPP harian
        $wrongPulang100Persen = (int) round($tunjanganHarian * 20 / 100); // Rp 9.090 (SALAH!)
        $this->assertNotEquals($wrongPulang100Persen, $absen->potongan_absen_pulang,
            "GAGAL: Potongan pulang dihitung dari 100% TPP, seharusnya dari 40%!");

        // ✅ Total potongan
        $expectedTotal = $expectedPulang + $expectedApelPagi + $expectedApelSore;
        $this->assertEqualsWithDelta($expectedTotal, $absen->total_potongan_tpp, 1);

        // ✅ TPP diterima = tunjangan - total potongan (TIDAK nol, karena masih masuk)
        $this->assertGreaterThan(0, $absen->tpp_diterima);
        $this->assertEqualsWithDelta((int) round($tunjanganHarian - $expectedTotal), $absen->tpp_diterima, 1);
    }

    // ============================================================
    // SKENARIO 4: Masuk TELAT, Apel, Pulang (Jumat)
    // ============================================================
    public function test_skenario_masuk_telat_apel_pulang()
    {
        // Hari Jumat, 6 Maret 2026
        // 08:20 - Masuk telat 20 menit → Telat 1 (15%)
        $this->setHariKerja('2026-03-06 08:20:00');
        $user = $this->createUser(['tpp' => 1000000]);

        // 08:20 - Absen masuk (telat 20 menit)
        $responseMasuk = $this->actingAs($user, 'sanctum')
            ->postJson('/api/checkinnew');
        $responseMasuk->assertStatus(200);

        // Verifikasi status telat langsung setelah clock-in
        $absenAwal = AttendancesPegawai::where('pegawai_id', $user->id)->first();
        $this->assertEquals('Telat 1', $absenAwal->status_masuk);
        $this->assertEquals(20, $absenAwal->menit_telat_masuk);

        // 08:05 tidak bisa karena sudah lewat, jadi kita pake waktu maju
        // Tapi apel pagi max 08:15, dan kita sudah di 08:20
        // Jadi pegawai ini tidak bisa apel pagi (sudah lewat jendela).
        // Kita berikan apel sore saja.

        // 16:00 - Apel sore (Jumat, dalam jendela 16:00-16:15)
        Carbon::setTestNow('2026-03-06 16:00:00');
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/apelsore');

        // 16:30 - Pulang
        Carbon::setTestNow('2026-03-06 16:30:00');
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/checkoutnew');

        // 23:00 - Cron berjalan
        Carbon::setTestNow('2026-03-06 23:00:00');
        $this->runCron();

        $absen = AttendancesPegawai::where('pegawai_id', $user->id)->first();
        $tunjanganHarian = 1000000 / 22;
        $basis40Persen = $tunjanganHarian * 0.4; // ← basis potongan 40%

        // ✅ Telat 1 (20 menit → range 1-30 menit, 15%)
        $this->assertEquals('Telat 1', $absen->status_masuk);
        $this->assertEquals(20, $absen->menit_telat_masuk);

        // ✅ Potongan telat harus ada
        $this->assertGreaterThan(0, $absen->potongan_absen_masuk);

        // ==========================================================
        // 🔥 PEMBUKTIAN: Potongan telat dari 40% TPP Harian
        // ==========================================================
        // Telat 1 = 15% → potongan = 40% × TPP Harian × 15%
        $expectedTelat = (int) round($basis40Persen * 15 / 100);
        $this->assertEqualsWithDelta($expectedTelat, $absen->potongan_absen_masuk, 1,
            "Potongan telat harus = 40% × TPP Harian × 15% (Telat 1)");

        // 🔥 ANTI-REGRESSION: Bukan dari 100% TPP
        $wrongTelat100Persen = (int) round($tunjanganHarian * 15 / 100);
        $this->assertNotEquals($wrongTelat100Persen, $absen->potongan_absen_masuk,
            "GAGAL: Potongan telat dihitung dari 100% TPP, seharusnya dari 40%!");

        // ✅ Apel sore hadir, tapi apel pagi dihukum (lewat jendela)
        $this->assertEquals('Tidak Apel', $absen->status_apel_pagi);
        $this->assertEquals('Hadir', $absen->status_apel_sore);
        $this->assertEquals('Pulang', $absen->status_pulang);

        // ✅ Potongan pulang & apel sore = 0 (sudah dilakukan)
        $this->assertEquals(0, $absen->potongan_absen_pulang);
        $this->assertEquals(0, $absen->potongan_tidak_apel_sore);

        // ✅ Potongan apel pagi ada (tidak apel) — juga dari 40%
        $expectedApelPagi = (int) round($basis40Persen * 10 / 100);
        $this->assertEqualsWithDelta($expectedApelPagi, $absen->potongan_tidak_apel_pagi, 1,
            "Potongan apel pagi harus = 40% × TPP Harian × 10%");

        // ✅ Total potongan = telat + apel pagi
        $expectedTotal = $expectedTelat + $expectedApelPagi;

        $this->assertEqualsWithDelta($expectedTotal, $absen->total_potongan_tpp, 1);

        // ✅ TPP diterima = tunjangan - total potongan
        $this->assertEqualsWithDelta((int) round($tunjanganHarian - $expectedTotal), $absen->tpp_diterima, 1);
    }

    // ============================================================
    // SKENARIO 5: Pegawai sedang CUTI (harus di-skip oleh cron)
    // ============================================================
    public function test_skenario_pegawai_cuti_di_skip()
    {
        // Hari Jumat, 6 Maret 2026
        $this->setHariKerja('2026-03-06 23:00:00');
        $user = $this->createUser(['tpp' => 1000000]);

        // Manual insert record cuti (biasanya dibuat oleh modul izin/cuti)
        // Enum status: 'Masuk', 'Dinas Luar', 'Tidak Masuk', 'izin', 'cuti'
        AttendancesPegawai::create([
            'pegawai_id' => $user->id,
            'dinas_id' => $user->dinas_id,
            'date_attendance' => '2026-03-06',
            'incoming_time' => '00:00:00',
            'outgoing_time' => '00:00:00',
            'status' => 'cuti',
            'ket_cuti' => 'Cuti Tahunan',
        ]);

        // 23:00 - Cron berjalan
        $this->runCron();

        $absen = AttendancesPegawai::where('pegawai_id', $user->id)->first();

        // ✅ Status masih cuti (tidak berubah jadi Alfa)
        $this->assertEquals('cuti', $absen->status);

        // ✅ Tidak ada potongan TPP (cuti di-skip, potongan tetap 0)
        $this->assertEquals(0, $absen->total_potongan_tpp);
    }
}
