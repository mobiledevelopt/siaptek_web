<?php

namespace Tests\Feature\Attendance\Integration;

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

class AttendanceProductionSimulationTest extends TestCase
{
    use RefreshDatabase, CronTestSetup;

    protected $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpCron();
        $this->calculator = new PayrollCalculator();

        Carbon::setTestNow('2026-03-23 08:00:00'); // Senin
    }

    /** @test */
    public function full_attendance_matrix_with_audit_logs()
    {
        $results = [];
        $dataset = new AttendanceMatrixDataset();

        $matrix = $dataset->getMatrix();

        $config = [
            'jmlHariKerja' => 5,
            'configTpp' => \App\Services\Attendance\AttendanceCache::potonganTpp()->keyBy('group'),
        ];

        // 1. Generate pegawai
        $pegawaiList = Pegawai::factory()->count(5)->create([
            'tpp' => 18181,
        ]);

        $totalProcessed = 0;

        // 2. Simulasi 5 hari kerja
        for ($day = 0; $day < 5; $day++) {

            Carbon::setTestNow(Carbon::now()->startOfWeek()->addDays($day)->setTime(8, 0));

            foreach ($pegawaiList as $pegawai) {

                // ambil random kombinasi dari matrix
                $data = $matrix[array_rand($matrix)];

                // 3. create attendance
                $absen = AttendancesPegawai::create([
                    'pegawai_id' => $pegawai->id,
                    'date_attendance' => today(),
                    'incoming_time' => $data['incoming_time'],
                    'outgoing_time' => $data['outgoing_time'],
                    'apel_pagi_at' => $data['apel_pagi_at'],
                    'apel_sore_at' => $data['apel_sore_at'],
                    'status' => $data['status'],
                ]);

                // 4. hitung payroll
                $isFriday = Carbon::now()->dayOfWeekIso === 5;

                $calc = $this->calculator->calculate($absen, $pegawai, $config, $isFriday);

                // view result
                $results[] = [
                    'pegawai' => substr($pegawai->id, 0, 6),
                    'tgl' => today()->format('Y-m-d'),
                    'status' => $data['status'],
                    'in' => $data['incoming_time'] ? '✔' : '-',
                    'out' => $data['outgoing_time'] ? '✔' : '-',
                    'ap' => $data['apel_pagi_at'] ? '✔' : '-',
                    'as' => $data['apel_sore_at'] ? '✔' : '-',
                    'tun' => round($calc['tunjangan']),
                    'pot' => round($calc['total']),
                    'net' => round($calc['diterima']),
                ];

                // 5. update (simulate cron)
                $absen->update([
                    'total_potongan_tpp' => (int) round($calc['total']),
                    'tpp_diterima' => (int) round($calc['diterima']),
                ]);

                // 6. audit log
                PotonganLogger::logFromCalculator($absen, $pegawai, $calc);

                // 7. assert payroll
                $fresh = AttendancesPegawai::find($absen->id);

                $this->assertEquals((int) round($calc['total']), $fresh->total_potongan_tpp);
                $this->assertEquals((int) round($calc['diterima']), $fresh->tpp_diterima);

                // 8. assert log
                $logs = AttendancePotonganLog::where('attendance_id', $absen->id)->get();
                $this->assertCount(4, $logs);

                // 9. idempotent check
                PotonganLogger::logFromCalculator($absen, $pegawai, $calc);

                $logs2 = AttendancePotonganLog::where('attendance_id', $absen->id)->get();
                $this->assertCount(4, $logs2);

                $totalProcessed++;
            }
        }

        echo "🔥 Production Simulation processed {$totalProcessed} records\n";
        echo "\n";
echo "================= PAYROLL SIMULATION =================\n";
echo "PGW   | TGL        | STS          | IN | OUT | AP | AS | TUN   | POT   | NET   \n";
echo "-----------------------------------------------------------------------\n";

foreach ($results as $row) {
    echo sprintf(
        "%-5s | %-10s | %-12s | %-2s | %-3s | %-2s | %-2s | %-2s | %-5d | %-5d\n",
        $row['pegawai'],
        $row['tgl'],
        substr($row['status'], 0, 12),
        $row['in'],
        $row['out'],
        $row['ap'],
        $row['as'],
        $row['tun'],
        $row['pot'],
        $row['net']
    );
}

echo "-----------------------------------------------------------------------\n";
echo "Total Rows: " . count($results) . "\n\n";
    }
}
