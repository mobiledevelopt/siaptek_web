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

class ClockOutTest extends TestCase
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

    public function test_clock_out_without_clock_in_should_fail()
    {
        $this->setJamPulang();

        $user = $this->createUser();

        $this->assertApiException(
            fn() => (new AttendanceService())->clockOut($user),
            ErrorCode::NOT_CLOCKED_IN
        );
    }
    
    public function test_double_clock_out_should_fail()
    {
        $this->setHariKerja();

        $user = $this->createUser();

        $service = new AttendanceService();

        $service->clockIn($user);
        $this->setJamPulang();
        $service->clockOut($user);

        // kedua kali
        $this->assertApiException(
            fn() => $service->clockOut($user),
            ErrorCode::ALREADY_CLOCKED_OUT
        );
    }

    public function test_clock_out_too_early_should_fail()
    {
        $this->setHariKerja();

        $user = $this->createUser();

        $service = new AttendanceService();

        $service->clockIn($user);

        $this->assertApiException(
            fn() => $service->clockOut($user),
            ErrorCode::NOT_TIME_YET
        );
    }

    public function test_clock_out_hari_libur_should_fail()
    {
        $this->setHariLibur();

        $user = $this->createUser();

        $this->assertApiException(
            fn() => (new AttendanceService())->clockOut($user),
            ErrorCode::NOT_CLOCKED_IN
        );
    }

    public function test_clock_out_without_jml_hari_kerja()
    {
        $this->setHariKerja();

        $user = $this->createUser();

        $service = new AttendanceService();
        $service->clockIn($user);

        Jml_hari_kerja::truncate();

        $this->setJamPulang();

        $this->assertApiException(
            fn() => (new AttendanceService())->clockOut($user),
            ErrorCode::WORKDAY_NOT_CONFIGURED
        );
    }

    public function test_clock_out_after_max_jam_pulang_should_fail()
    {
        $this->setHariKerja();

        $user = $this->createUser();

        $service = new AttendanceService();

        // clock in dulu
        $service->clockIn($user);

        $this->setMaxJamPulang();

        $this->assertApiException(
            fn() => $service->clockOut($user),
            ErrorCode::TOO_LATE
        );
    }

    public function test_clock_out_success()
    {
        $this->setHariKerja();

        $user = $this->createUser();

        $service = new AttendanceService();

        // clock in dulu
        $service->clockIn($user);

        $this->setJamPulang();

        $result = $service->clockOut($user);

        $this->assertNotNull($result->outgoing_time);
    }

}
