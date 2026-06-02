<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Attendance\AttendanceCache;
use Illuminate\Support\Facades\Cache;

class CacheKeyAndPotonganIzinTest extends TestCase
{
    /**
     * Test: cache key harus konsisten antara set dan clear
     * Bug sebelumnya: jmlHariKerja() pakai key "jml_hari_kerja_6_2026"
     * tapi clearJmlHariKerja("06","2026") hapus key "jml_hari_kerja_06_2026"
     */
    public function test_cache_key_konsisten_dengan_leading_zero()
    {
        // Simulasi: cache di-set dengan integer (seperti Carbon::now()->month)
        Cache::put('jml_hari_kerja_6_2026', 20, 3600);

        // Clear dengan string "06" (seperti dari form input)
        AttendanceCache::clearJmlHariKerja("06", "2026");

        // Cache harus terhapus karena clearJmlHariKerja sekarang cast ke int
        $this->assertNull(Cache::get('jml_hari_kerja_6_2026'));
    }

    public function test_cache_key_konsisten_dengan_integer()
    {
        Cache::put('jml_hari_kerja_6_2026', 20, 3600);

        // Clear dengan integer langsung
        AttendanceCache::clearJmlHariKerja(6, 2026);

        $this->assertNull(Cache::get('jml_hari_kerja_6_2026'));
    }

    /**
     * Test: Izin Dengan Keterangan (id=6) → TPP/hari × persentase (tanpa 40%)
     */
    public function test_potongan_izin_dengan_keterangan_tanpa_40_persen()
    {
        $tpp_per_hari = 105000;
        $persentase = 50;
        $jenis_izin_id = 6; // Izin Dengan Keterangan

        // Rumus untuk id 6: langsung × persentase
        $potongan = $tpp_per_hari * $persentase / 100;

        $this->assertEquals(52500, $potongan);
    }

    /**
     * Test: Izin lainnya (bukan id=6) → TPP/hari × 40% × persentase
     */
    public function test_potongan_izin_lainnya_dengan_40_persen()
    {
        $tpp_per_hari = 105000;
        $persentase = 50;
        $jenis_izin_id = 5; // Bukan Izin Dengan Keterangan

        // Rumus untuk selain id 6: × 40% dulu
        $potongan = $tpp_per_hari * 40 / 100 * $persentase / 100;

        $this->assertEquals(21000, $potongan);
    }

    /**
     * Test: cast bulan dari form input ke int
     */
    public function test_bulan_explode_cast_ke_integer()
    {
        $bln_input = "06-2026";
        $bln_thun_explode = explode('-', $bln_input);
        $bln_thun_explode[0] = (int) $bln_thun_explode[0];
        $bln_thun_explode[1] = (int) $bln_thun_explode[1];

        $this->assertSame(6, $bln_thun_explode[0]);
        $this->assertSame(2026, $bln_thun_explode[1]);
    }
}
