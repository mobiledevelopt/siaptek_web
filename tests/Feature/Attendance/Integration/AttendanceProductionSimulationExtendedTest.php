<?php

namespace Tests\Feature\Attendance\Integration;

use Tests\TestCase;
use App\Models\Pegawai;
use App\Models\AttendancesPegawai;
use App\Services\Attendance\Payroll\PayrollCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\Traits\CreatesAttendance;
use Tests\Traits\CronTestSetup;

class AttendanceProductionSimulationExtendedTest extends TestCase
{
    use RefreshDatabase, CreatesAttendance, CronTestSetup;

    protected $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCron();
        $this->calculator = new PayrollCalculator();
        Carbon::setTestNow('2026-03-28 08:00:00'); // fix datetime
    }

    /** @test */
    public function full_week_auto_generated_matrix()
    {
        $pegawaiList = Pegawai::factory()->count(3)->create([
            'tpp' => 18181,
        ]);

        $statuses = ['Masuk', 'Tidak Masuk', 'izin', 'cuti', 'Dinas Luar'];
        $clockInOptions = [true, false];
        $clockOutOptions = [true, false];
        $apelPagiOptions = [true, false];
        $apelSoreOptions = [true, false];

        $matrixCount = 0;

        foreach ($pegawaiList as $pegawai) {
            foreach ($statuses as $status) {
                foreach ($clockInOptions as $hasClockIn) {
                    foreach ($clockOutOptions as $hasClockOut) {
                        foreach ($apelPagiOptions as $apelPagi) {
                            foreach ($apelSoreOptions as $apelSore) {

                                $matrixCount++;

                                // Create attendance row
                                $absen = $this->createAttendance($pegawai, [
                                    'date_attendance' => today(),
                                    'incoming_time' => $hasClockIn ? '08:00:00' : null,
                                    'outgoing_time' => $hasClockOut ? '17:00:00' : null,
                                    'apel_pagi_at' => $apelPagi ? now() : null,
                                    'apel_sore_at' => $apelSore ? now() : null,
                                    'status' => $status,
                                ]);

                                $config = [
                                    'jmlHariKerja' => 5,
                                    'configTpp' => \App\Services\Attendance\AttendanceCache::potonganTpp()->keyBy('group'),
                                ];

                                // Hitung potongan
                                $calc = $this->calculator->calculate($absen, $pegawai, $config);

                                // Update attendance seperti cron
                                $absen->update([
                                    'total_potongan_tpp' => (int) round($calc['total']),
                                    'tpp_diterima' => (int) round($calc['diterima']),
                                    'status_apel_pagi' => $apelPagi ? 'Apel' : 'Tidak Apel',
                                    'status_apel_sore' => $apelSore ? 'Apel' : 'Tidak Apel',
                                    'status_pulang' => $hasClockOut ? 'Masuk' : 'Tidak Absen Pulang (PSW)',
                                ]);

                                $fresh = AttendancesPegawai::find($absen->id);

                                // Assertions
                                $this->assertEquals((int) round($calc['total']), $fresh->total_potongan_tpp);
                                $this->assertEquals((int) round($calc['diterima']), $fresh->tpp_diterima);

                                // Idempotent: update ulang
                                $fresh->update([
                                    'total_potongan_tpp' => (int) round($calc['total']),
                                    'tpp_diterima' => (int) round($calc['diterima']),
                                ]);

                                $fresh2 = AttendancesPegawai::find($absen->id);
                                $this->assertEquals($fresh->total_potongan_tpp, $fresh2->total_potongan_tpp);
                                $this->assertEquals($fresh->tpp_diterima, $fresh2->tpp_diterima);
                            }
                        }
                    }
                }
            }
        }

        echo "✅ Extended Attendance Matrix executed {$matrixCount} test cases\n";
    }
}