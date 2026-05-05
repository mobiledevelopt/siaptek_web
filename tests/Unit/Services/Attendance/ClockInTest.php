<?php

namespace Tests\Unit\Services\Attendance;

use Tests\TestCase;
use App\Models\Pegawai;
use App\Models\AttendancesPegawai;
use App\Services\Attendance\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Constants\ErrorCode;
use App\Exceptions\ApiException;
use App\Models\Jml_hari_kerja;
use Carbon\Carbon;
use Tests\Traits\CronTestSetup;

class ClockInTest extends TestCase
{
    use RefreshDatabase, CronTestSetup;

    protected AttendanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCron();
        Carbon::setTestNow('2026-03-27 08:00:00');
        $this->service = new AttendanceService();
    }

    /** @test */
    public function test_clock_in_telat_levels()
    {
        $service = new AttendanceService();
        $user = $this->createUser();

        $telatTests = [
            '2026-03-04 08:10:00' => ['Telat 1', 2727],
            '2026-03-04 08:45:00' => ['Telat 2', 5454],
            '2026-03-04 09:20:00' => ['Telat 3', 8181],
            '2026-03-04 09:50:00' => ['Telat 4', 10909],
        ];

        foreach ($telatTests as $time => [$expectedStatus, $expectedPotongan]) {
            Carbon::setTestNow($time);
            $user->fresh(); // pastikan state bersih

            // Hapus data absen sebelumnya
            AttendancesPegawai::where('pegawai_id', $user->id)->delete();

            $result = $service->clockIn($user);

            $this->assertDatabaseHas('attendances_pegawai', [
                'pegawai_id' => $user->id,
                'status_masuk' => $expectedStatus
            ]);

            $this->assertGreaterThan(0, $result->menit_telat_masuk);
            // cek potongan
            $this->assertEquals($expectedPotongan, $result->potongan_absen_masuk);
        }
    }

    public function test_double_clock_in_throws_exception()
    {
        $this->setHariKerja();

        $user = $this->createUser();
        $service = new AttendanceService();
        
        $service->clockIn($user);

        try {
            $service->clockIn($user);
            $this->fail('Seharusnya gagal karena sudah presensi masuk');
        } catch (ApiException $e) {
            $this->assertEquals(ErrorCode::ALREADY_CLOCKED_IN, $e->getErrorCode());
        }

    }

    public function test_clock_in_before_jam_masuk_throws_exception()
    {
        $this->setSebelumJamMasuk();

        $user = $this->createUser();

        try {
            (new AttendanceService())->clockIn($user);
            $this->fail('Seharusnya gagal karena sebelum jam masuk');
        } catch (ApiException $e) {
            $this->assertStringContainsString('Minimal Jam Presensi Masuk', $e->getMessage());
        }
    }

    public function test_clock_in_after_batas_jam_masuk_throws_exception()
    {
        $this->setSetelahBatasMasuk();

        $user = $this->createUser();

        try {
            (new AttendanceService())->clockIn($user);
            $this->fail('Seharusnya gagal karena melebihi batas jam masuk');
        } catch (ApiException $e) {
            $this->assertEquals('Melebihi batas jam masuk', $e->getMessage());
        }
    }

    public function test_clock_in_hari_libur_throws_exception()
    {
        $this->setHariLibur();

        $user = $this->createUser();

        try {
            (new AttendanceService())->clockIn($user);
            $this->fail('Seharusnya gagal karena hari libur');
        } catch (ApiException $e) {
            $this->assertEquals(ErrorCode::WEEKEND, $e->getErrorCode());
        }

    }

    public function test_clock_in_without_jml_hari_kerja()
    {
        $this->setHariKerja();

        $user = $this->createUser();

        Jml_hari_kerja::truncate();

        $this->assertApiException(
            fn() => (new AttendanceService())->clockIn($user),
            ErrorCode::WORKDAY_NOT_CONFIGURED
        );
    }

}
