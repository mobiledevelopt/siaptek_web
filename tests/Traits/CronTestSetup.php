<?php

namespace Tests\Traits;

use App\Models\ConfigPotTpp;
use App\Models\Dinas;
use App\Models\JadwalApel;
use App\Models\JamAbsen;
use App\Models\Jml_hari_kerja;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

trait CronTestSetup
{
    protected function setUpCron()
    {
        Cache::flush();

        JamAbsen::insert(
            [
                [
                    'id' => 1,
                    'title' => 'Senin - Kamis',
                    'min_masuk' => '07:00',
                    'jam_masuk' => '08:00',
                    'max_masuk' => '10:00',
                    'min_pulang' => '15:00',
                    'jam_pulang' => '15:00',
                    'max_pulang' => '18:00'
                ],
                [
                    'id' => 2,
                    'title' => 'Jumat',
                    'min_masuk' => '07:00',
                    'jam_masuk' => '08:00',
                    'max_masuk' => '10:00',
                    'min_pulang' => '15:30',
                    'jam_pulang' => '15:30',
                    'max_pulang' => '18:00'
                ]
            ]
        );

        ConfigPotTpp::insert([
            [
                'id' => 1,
                'title' => 'Telat 1',
                'dari_meni' => 1,
                'sampai_menit' => 30,
                'persentase_potongan' => 15,
                'group' => 'masuk'
            ],
            [
                'id' => 2,
                'title' => 'Telat 2',
                'dari_meni' => 31,
                'sampai_menit' => 60,
                'persentase_potongan' => 30,
                'group' => 'masuk'
            ],
            [
                'id' => 3,
                'title' => 'Telat 3',
                'dari_meni' => 61,
                'sampai_menit' => 90,
                'persentase_potongan' => 45,
                'group' => 'masuk'
            ],
            [
                'id' => 4,
                'title' => 'Telat 4',
                'dari_meni' => 91,
                'sampai_menit' => 120,
                'persentase_potongan' => 60,
                'group' => 'masuk'
            ],
            [
                'id' => 5,
                'title' => 'Tidak Absen Pulang (PSW)',
                'dari_meni' => 0,
                'sampai_menit' => 0,
                'persentase_potongan' => 20,
                'group' => 'pulang'
            ],
            [
                'id' => 6,
                'title' => 'Izin Dengan Keterangan',
                'dari_meni' => 0,
                'sampai_menit' => 0,
                'persentase_potongan' => 50,
                'group' => 'izin'
            ],
            [
                'id' => 7,
                'title' => 'Dinas Luar',
                'dari_meni' => 0,
                'sampai_menit' => 0,
                'persentase_potongan' => 0,
                'group' => 'izin'
            ],
            [
                'id' => 8,
                'title' => 'Tidak Apel',
                'dari_meni' => 0,
                'sampai_menit' => 0,
                'persentase_potongan' => 20,
                'group' => 'apel'
            ],
            [
                'id' => 9,
                'title' => 'Tanpa Keterangan',
                'dari_meni' => 0,
                'sampai_menit' => 0,
                'persentase_potongan' => 100,
                'group' => 'alfa'
            ]
        ]);

        for ($i = 1; $i <= 12; $i++) {
            Jml_hari_kerja::insert([
                'bulan' => str_pad($i, 2, '0', STR_PAD_LEFT),
                'tahun' => date('Y'),
                'jml_hari_kerja' => 22
            ]);
        }

        Dinas::create([
            'id' => 1,
            'nama_dinas' => 'Dinas A',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'latitude_2' => -6.2088,
            'longitude_2' => 106.8456
        ]);

        foreach ([1, 2, 3, 4, 5] as $hari) {
            JadwalApel::create([
                'dinas_id' => 1,
                'hari' => $hari,
                'apel_pagi' => '1',
                'apel_sore' => $hari == 5 ? '1' : '0',
                'jam_apel_pagi' => '08:00',
                'max_apel_pagi' => '08:15',
                'jam_apel_sore' => $hari == 5 ? '16:00' : null,
                'max_apel_sore' => $hari == 5 ? '16:15' : null,
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'latitude_2' => -6.2088,
                'longitude_2' => 106.8456,
            ]);
        }
    }

    protected function setHariKerja($datetime = '2026-03-04 08:00:00')
    {
        Carbon::setTestNow($datetime);
    }

    protected function createUser($overrides = [])
    {
        return \App\Models\Pegawai::factory()->create(array_merge([
            'tpp' => 1000000,
            'dinas_id' => 1,
            'active' => 1
        ], $overrides));
    }

    protected function runCron()
    {
        $this->artisan('absen:cron-new');
    }
}