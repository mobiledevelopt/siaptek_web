<?php

namespace Tests\Feature\Attendance\Integration;

use Tests\TestCase;
use App\Models\Pegawai;
use App\Models\AttendancesPegawai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use App\Services\Attendance\Payroll\PayrollCalculator;
use Tests\Traits\CronTestSetup;

class AttendanceOneMonthSimulationTest extends TestCase
{
    use RefreshDatabase, CronTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCron();
    }

    /** @test */
    public function simulasi_1_bulan_full()
    {
        Carbon::setTestNow('2026-03-01 08:00:00');

        $calculator = new PayrollCalculator();

        $pegawaiList = Pegawai::factory()->count(5)->create([
            'tpp' => 80000
        ]);

        $start = Carbon::parse('2026-03-01');
        $end = Carbon::parse('2026-03-31');

        $rows = 0;

        while ($start <= $end) {

            // skip weekend (opsional)
            if ($start->isWeekend()) {
                $start->addDay();
                continue;
            }

            foreach ($pegawaiList as $pegawai) {

                Carbon::setTestNow($start->copy()->setTime(8, rand(0, 59)));

                // RANDOM SCENARIO
                $rand = rand(1, 100);

                if ($rand <= 10) {
                    // IZIN / CUTI / DL
                    $status = collect(['izin', 'cuti', 'Dinas Luar'])->random();

                    $absen = AttendancesPegawai::create([
                        'pegawai_id' => $pegawai->id,
                        'date_attendance' => $start,
                        'status' => $status,
                    ]);
                } elseif ($rand <= 25) {
                    // TIDAK MASUK (cron)
                    $absen = AttendancesPegawai::create([
                        'pegawai_id' => $pegawai->id,
                        'date_attendance' => $start,
                        'status' => 'Tidak Masuk',
                    ]);
                } else {
                    // MASUK
                    $absen = AttendancesPegawai::create([
                        'pegawai_id' => $pegawai->id,
                        'date_attendance' => $start,
                        'incoming_time' => now(),
                        'status' => 'Masuk',
                    ]);

                    // 70% pulang
                    if (rand(1, 100) <= 70) {
                        $absen->update([
                            'outgoing_time' => now()->addHours(8)
                        ]);
                    }

                    // 70% apel pagi
                    if (rand(1, 100) <= 70) {
                        $absen->update([
                            'apel_pagi_at' => now()
                        ]);
                    }

                    // Jumat → apel sore
                    if ($start->isFriday() && rand(1, 100) <= 70) {
                        $absen->update([
                            'apel_sore_at' => now()
                        ]);
                    }
                }

                // ======================
                // HITUNG PAYROLL
                // ======================
                $config = [
                    'jmlHariKerja' => 22,
                    'configTpp' => \App\Services\Attendance\AttendanceCache::potonganTpp()->keyBy('group'),
                ];

                $calc = $calculator->calculate($absen, $pegawai, $config);

                $absen->update([
                    'total_potongan_tpp' => $calc['total'],
                    'tpp_diterima' => $calc['diterima'],
                ]);

                // VALIDASI
                $fresh = $absen->fresh();

                $this->assertEquals(
                    (int) round($calc['total']),
                    (int) round($fresh->total_potongan_tpp)
                );

                $this->assertEquals($calc['diterima'], $fresh->tpp_diterima);

                $rows++;
            }

            $start->addDay();
        }

        echo "\n✅ SIMULASI 1 BULAN: {$rows} rows\n";
    }
}
