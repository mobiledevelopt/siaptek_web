<?php

namespace Tests\Feature\Attendance\Audit;

use Tests\TestCase;
use App\Models\AttendancesPegawai;
use App\Models\Pegawai;
use App\Services\Attendance\AttendanceCronHandler;
use App\Services\Attendance\Payroll\PayrollCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Traits\CronTestSetup;

class AuditTest extends TestCase
{
    use RefreshDatabase, CronTestSetup;

    protected $cron;
    protected $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCron();

        $this->cron = new AttendanceCronHandler();
        $this->calculator = new PayrollCalculator();

        Carbon::setTestNow('2026-03-28 18:00:00'); // sore → semua rule aktif
    }
    /** @test */
    public function audit_log_should_not_duplicate()
    {
        $pegawai = Pegawai::factory()->create([
            'tpp' => 18181,
        ]);

        AttendancesPegawai::create([
            'pegawai_id' => $pegawai->id,
            'date_attendance' => today(),
            'incoming_time' => null,
            'outgoing_time' => null,
            'apel_pagi_at' => null,
            'apel_sore_at' => null,
            'status' => 'Masuk',
        ]);

        $this->runCron();

        $count1 = DB::table('attendance_potongan_logs')->count();

        $this->runCron();

        $count2 = DB::table('attendance_potongan_logs')->count();

        $this->assertEquals($count1, $count2);
    }
}
