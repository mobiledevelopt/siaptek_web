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

class ApelTest extends TestCase
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

    public function test_apel_pagi_success()
    {

        $this->setHariKerja();

        $user = $this->createUser();

        $service = new AttendanceService();

        // wajib clock in dulu
        $service->clockIn($user);

        $result = $service->apel($user, 'pagi');

        $this->assertNotNull($result->status_apel_pagi);
        $this->assertEquals('Hadir', $result->status_apel_pagi);
    }

    public function test_apel_sore_success()
    {
        // Jumat pagi
        Carbon::setTestNow('2026-03-06 08:05:00');

        $user = $this->createUser();

        $service = new AttendanceService();

        $service->clockIn($user);

        // Jumat sore
        Carbon::setTestNow('2026-03-06 16:05:00');

        $result = $service->apel($user, 'sore');

        $this->assertEquals('Hadir', $result->status_apel_sore);
    }

    public function test_apel_without_clock_in_should_fail()
    {
        $this->setHariKerja('08:05:00');

        $user = $this->createUser();

        $this->assertApiException(
            fn() => (new AttendanceService())->apel($user, 'pagi'),
            ErrorCode::NOT_CLOCKED_IN
        );
    }

    public function test_double_apel_pagi_should_fail()
    {
        $this->setHariKerja('08:05:00');

        $user = $this->createUser();

        $service = new AttendanceService();

        $service->clockIn($user);
        $service->apel($user, 'pagi');

        $this->assertApiException(
            fn() => $service->apel($user, 'pagi'),
            ErrorCode::APEL_ALREADY
        );
    }

    public function test_apel_too_early_should_fail()
    {
        $this->setHariKerja('07:00:00');

        $user = $this->createUser();

        $service = new AttendanceService();
        $service->clockIn($user);

        $this->assertApiException(
            fn() => $service->apel($user, 'pagi'),
            ErrorCode::APEL_TOO_EARLY
        );
    }

    public function test_apel_too_late_should_fail()
    {
        $this->setHariKerja('09:00:00');

        $user = $this->createUser();

        $service = new AttendanceService();
        $service->clockIn($user);

        $this->assertApiException(
            fn() => $service->apel($user, 'pagi'),
            ErrorCode::APEL_TOO_LATE
        );
    }

    public function test_apel_not_scheduled_should_fail()
    {
        $this->setHariKerja('08:05:00');

        \App\Models\JadwalApel::truncate();

        $user = $this->createUser();

        $service = new AttendanceService();
        $service->clockIn($user);

        $this->assertApiException(
            fn() => $service->apel($user, 'pagi'),
            ErrorCode::APEL_NOT_SCHEDULED
        );
    }

    public function test_apel_hari_libur_should_fail()
    {
        $this->setHariLibur();

        $user = $this->createUser();

        $this->assertApiException(
            fn() => (new AttendanceService())->apel($user, 'pagi'),
            ErrorCode::WEEKEND
        );
    }

    public function test_apel_sore_not_active_should_fail()
    {
        Carbon::setTestNow('2026-03-06 08:05:00');

        // matikan apel sore
        \App\Models\JadwalApel::query()->update([
            'apel_sore' => 0
        ]);

        $user = $this->createUser();

        $service = new AttendanceService();
        $service->clockIn($user);
        
        Carbon::setTestNow('2026-03-06 16:05:00');

        $this->assertApiException(
            fn() => $service->apel($user, 'sore'),
            ErrorCode::APEL_NOT_SCHEDULED
        );
    }

    public function test_apel_full_day()
    {
        Carbon::setTestNow('2026-03-06 08:05:00');

        $user = $this->createUser();

        $service = new AttendanceService();

        // pagi
        $service->clockIn($user);
        $service->apel($user, 'pagi');

        // sore
        Carbon::setTestNow('2026-03-06 16:05:00');
        $result = $service->apel($user, 'sore');

        $this->assertEquals('Hadir', $result->status_apel_pagi);
        $this->assertEquals('Hadir', $result->status_apel_sore);
    }

    public function test_apel_sore_not_friday_should_fail()
    {
        // set hari SELASA
        Carbon::setTestNow('2026-03-03 08:05:00');

        $user = $this->createUser();

        $service = new AttendanceService();

        $service->clockIn($user);

        Carbon::setTestNow('2026-03-03 16:05:00');

        $this->assertApiException(
            fn() => $service->apel($user, 'sore'),
            ErrorCode::APEL_NOT_SCHEDULED
        );
    }

    public function test_apel_sore_friday_success()
    {
        Carbon::setTestNow('2026-03-06 08:05:00');

        $user = $this->createUser();

        $service = new AttendanceService();

        $service->clockIn($user);

        Carbon::setTestNow('2026-03-06 16:05:00');

        $result = $service->apel($user, 'sore');

        $this->assertEquals('Hadir', $result->status_apel_sore);
    }

}
