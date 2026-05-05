<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Models\Pegawai;
use App\Models\AttendancesPegawai;
use App\Models\AttendancePotonganLog;
use App\Services\Attendance\Payroll\PayrollCalculator;
use App\Helpers\PotonganLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\Traits\CronTestSetup;

class PotonganLoggerTest extends TestCase
{
    use RefreshDatabase, CronTestSetup;

    protected $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCron();
        $this->calculator = new PayrollCalculator();
        Carbon::setTestNow('2026-03-28 08:00:00');
    }

    /** @test */
    public function it_logs_all_potongan_correctly()
    {
        $pegawai = Pegawai::factory()->create(['tpp' => 18181]);
        $absen = AttendancesPegawai::create([
            'pegawai_id' => $pegawai->id,
            'date_attendance' => today(),
            'incoming_time' => '08:00:00',
            'outgoing_time' => null, // pulang tidak absen
            'apel_pagi_at' => null, // tidak apel pagi
            'apel_sore_at' => null, // tidak apel sore
            'status' => 'Masuk',
        ]);

        $config = [
            'jmlHariKerja' => 5,
            'configTpp' => \App\Services\Attendance\AttendanceCache::potonganTpp()->keyBy('group'),
        ];

        $calc = $this->calculator->calculate($absen, $pegawai, $config);

        // log potongan
        PotonganLogger::logFromCalculator($absen, $pegawai, $calc);

        $logs = AttendancePotonganLog::where('attendance_id', $absen->id)->get();
        $this->assertCount(4, $logs);

        $types = $logs->pluck('type')->toArray();
        $this->assertContains('telat', $types);
        $this->assertContains('pulang', $types);
        $this->assertContains('apel_pagi', $types);
        $this->assertContains('apel_sore', $types);

        foreach ($logs as $log) {
            $this->assertEquals($pegawai->id, $log->pegawai_id);
            $this->assertEquals(round($log->nilai_raw), $log->nilai_final);
        }
    }

    /** @test */
    public function it_is_idempotent_when_logging_multiple_times()
    {
        $pegawai = Pegawai::factory()->create(['tpp' => 18181]);
        $absen = AttendancesPegawai::create([
            'pegawai_id' => $pegawai->id,
            'date_attendance' => today(),
            'incoming_time' => '08:00:00',
            'outgoing_time' => null,
            'apel_pagi_at' => null,
            'apel_sore_at' => null,
            'status' => 'Masuk',
        ]);

        $config = [
            'jmlHariKerja' => 5,
            'configTpp' => \App\Services\Attendance\AttendanceCache::potonganTpp()->keyBy('group'),
        ];

        $calc = $this->calculator->calculate($absen, $pegawai, $config);

        // log pertama
        PotonganLogger::logFromCalculator($absen, $pegawai, $calc);

        // log kedua (harus idempotent)
        PotonganLogger::logFromCalculator($absen, $pegawai, $calc);

        $logs = AttendancePotonganLog::where('attendance_id', $absen->id)->get();
        $this->assertCount(4, $logs, "Seharusnya tidak ada duplikasi log");
    }
}