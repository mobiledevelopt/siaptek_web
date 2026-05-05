<?php

namespace Tests\Feature\Attendance\Potongan;

use Tests\TestCase;
use App\Models\Pegawai;
use App\Models\AttendancesPegawai;
use App\Models\AttendancePotonganLog;
use App\Services\Attendance\Payroll\PayrollCalculator;
use App\Helpers\PotonganLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Datasets\AttendanceMatrixDataset;
use Tests\Traits\CronTestSetup;

class AttendanceMatrixTest extends TestCase
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
    public function attendance_matrix_dynamic_test()
    {
        $dataset = new AttendanceMatrixDataset();
        $matrix = $dataset->getMatrix();

        $config = [
            'jmlHariKerja' => 5,
            'configTpp' => \App\Services\Attendance\AttendanceCache::potonganTpp()->keyBy('group'),
        ];

        $count = 0;

        foreach ($matrix as $data) {
            $count++;

            // 1. Pegawai
            $pegawai = Pegawai::factory()->create([
                'tpp' => $data['tpp'] ?? 18181,
            ]);

            // 2. Attendance (langsung dari dataset)
            $absen = AttendancesPegawai::create([
                'pegawai_id' => $pegawai->id,
                'date_attendance' => today(),
                'incoming_time' => $data['incoming_time'],
                'outgoing_time' => $data['outgoing_time'],
                'apel_pagi_at' => $data['apel_pagi_at'],
                'apel_sore_at' => $data['apel_sore_at'],
                'status' => $data['status'],
            ]);

            // 3. Hitung payroll
            $calc = $this->calculator->calculate($absen, $pegawai, $config);

            // 4. Simulasi cron update
            $absen->update([
                'total_potongan_tpp' => (int) round($calc['total']),
                'tpp_diterima' => (int) round($calc['diterima']),
            ]);

            // 5. Audit log (WAJIB untuk production simulation)
            PotonganLogger::logFromCalculator($absen, $pegawai, $calc);

            // 6. Assert payroll
            $fresh = AttendancesPegawai::find($absen->id);

            $this->assertEquals((int) round($calc['total']), $fresh->total_potongan_tpp);
            $this->assertEquals((int) round($calc['diterima']), $fresh->tpp_diterima);

            // 7. Assert audit log
            $logs = AttendancePotonganLog::where('attendance_id', $absen->id)->get();

            $this->assertCount(4, $logs, "Harus ada 4 jenis potongan");

            $types = $logs->pluck('type')->toArray();
            foreach (['telat', 'pulang', 'apel_pagi', 'apel_sore'] as $type) {
                $this->assertContains($type, $types);
            }

            foreach ($logs as $log) {
                $this->assertEquals($pegawai->id, $log->pegawai_id);
                $this->assertEquals(round($log->nilai_raw), $log->nilai_final);
            }

            // 8. 🔁 Idempotent test (CRITICAL)
            PotonganLogger::logFromCalculator($absen, $pegawai, $calc);

            $logs2 = AttendancePotonganLog::where('attendance_id', $absen->id)->get();
            $this->assertCount(4, $logs2, "Log tidak boleh double");
        }

        echo "🔥 Production Matrix executed {$count} cases\n";
    }
}