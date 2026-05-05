<?php

namespace App\Console\Commands;

use App\Models\AttendancesPegawai;
use App\Models\Pegawai;
use App\Services\Attendance\AttendanceCache;
use App\Services\Attendance\AttendanceRepository;
use App\Services\Attendance\Payroll\PayrollCalculator;
use Illuminate\Console\Command;

class TestAbsenCronDryRun extends Command
{
    protected $signature = 'absen:dry-run 
                            {--pegawai= : ID/NIP pegawai tertentu (opsional, jika kosong ambil 5 pegawai acak)}
                            {--all : Tampilkan semua pegawai aktif}';

    protected $description = 'Simulasi Cron Job tanpa menyimpan ke database (dry-run)';

    public function handle()
    {
        $this->info('============================================');
        $this->info('   🧪 SIMULASI CRON JOB (DRY-RUN MODE)');
        $this->info('   Tanggal: ' . now()->format('l, d F Y'));
        $this->info('   Jam: ' . now()->format('H:i:s'));
        $this->info('============================================');
        $this->newLine();

        // Cek hari kerja
        if (now()->isWeekend()) {
            $this->warn('⚠️  Hari ini WEEKEND — Cron tidak akan berjalan di produksi.');
            $this->newLine();
        }

        if (AttendanceCache::isLibur()) {
            $this->warn('⚠️  Hari ini LIBUR NASIONAL — Cron tidak akan berjalan di produksi.');
            $this->newLine();
        }

        // Ambil config
        $jmlHariKerja = AttendanceCache::jmlHariKerja();
        $configTpp = AttendanceCache::potonganTpp()->keyBy('group');

        if (!$jmlHariKerja || $jmlHariKerja <= 0) {
            $this->error('❌ Jumlah hari kerja bulan ini belum diinput!');
            return 1;
        }

        $this->info("📅 Jumlah Hari Kerja Bulan Ini: {$jmlHariKerja} hari");
        $this->info("📊 Config Potongan TPP:");

        $configRows = [];
        foreach ($configTpp as $group => $cfg) {
            $configRows[] = [$group, $cfg->title, $cfg->persentase_potongan . '%'];
        }
        $this->table(['Group', 'Title', 'Persentase'], $configRows);
        $this->newLine();

        // Ambil pegawai
        $pegawaiId = $this->option('pegawai');

        if ($pegawaiId) {
            $users = Pegawai::where('id', $pegawaiId)
                ->orWhere('nip', $pegawaiId)
                ->where('active', 1)
                ->get();

            if ($users->isEmpty()) {
                $this->error("❌ Pegawai dengan ID/NIP '{$pegawaiId}' tidak ditemukan atau tidak aktif.");
                return 1;
            }
        } elseif ($this->option('all')) {
            $users = Pegawai::where('active', 1)->get();
        } else {
            $users = Pegawai::where('active', 1)->inRandomOrder()->limit(5)->get();
            $this->info("🎲 Menampilkan 5 pegawai acak (gunakan --pegawai=ID untuk spesifik)");
        }

        $this->newLine();
        $this->info("👥 Total Pegawai: " . $users->count());
        $this->newLine();

        $config = [
            'jmlHariKerja' => $jmlHariKerja,
            'configTpp' => $configTpp,
        ];

        $calculator = new PayrollCalculator();
        $results = [];

        foreach ($users as $user) {
            $absen = AttendanceRepository::today($user->id);
            $tunjanganHarian = $user->tpp / $jmlHariKerja;

            // ===== CASE 1: Belum absen sama sekali (ALFA) =====
            if (!$absen || empty($absen->incoming_time) || $absen->incoming_time === '00:00:00') {
                $results[] = [
                    'nama' => $user->name,
                    'nip' => $user->nip ?? '-',
                    'tpp' => 'Rp ' . number_format($user->tpp, 0, ',', '.'),
                    'tpp_harian' => 'Rp ' . number_format($tunjanganHarian, 0, ',', '.'),
                    'masuk' => '❌ ALFA',
                    'pulang' => '-',
                    'apel_pagi' => '-',
                    'apel_sore' => '-',
                    'pot_telat' => '-',
                    'pot_pulang' => '-',
                    'pot_apel_pagi' => '-',
                    'pot_apel_sore' => '-',
                    'total_pot' => 'Rp ' . number_format($tunjanganHarian, 0, ',', '.'),
                    'diterima' => 'Rp 0',
                    'status' => '🔴 ALFA (100%)',
                ];
                continue;
            }

            // ===== CASE 2: Izin/Cuti =====
            $status = strtolower(trim($absen->status ?? ''));
            if (in_array($status, ['izin', 'cuti', 'dinas luar'])) {
                $results[] = [
                    'nama' => $user->name,
                    'nip' => $user->nip ?? '-',
                    'tpp' => 'Rp ' . number_format($user->tpp, 0, ',', '.'),
                    'tpp_harian' => 'Rp ' . number_format($tunjanganHarian, 0, ',', '.'),
                    'masuk' => ucfirst($status),
                    'pulang' => '-',
                    'apel_pagi' => '-',
                    'apel_sore' => '-',
                    'pot_telat' => 'Rp 0',
                    'pot_pulang' => 'Rp 0',
                    'pot_apel_pagi' => 'Rp 0',
                    'pot_apel_sore' => 'Rp 0',
                    'total_pot' => 'Rp 0',
                    'diterima' => 'Rp ' . number_format($tunjanganHarian, 0, ',', '.'),
                    'status' => '🟡 ' . strtoupper($status),
                ];
                continue;
            }

            // ===== CASE 3: Masuk (kalkulasi penuh) =====
            $calc = $calculator->calculate($absen, $user, $config);

            $isFriday = now()->dayOfWeekIso === 5;

            $statusMasuk = $absen->status_masuk ?? 'Masuk';
            $statusPulang = (!empty($absen->outgoing_time) && $absen->outgoing_time !== '00:00:00') ? '✅ Pulang' : '❌ Tidak';
            $statusApelPagi = !empty($absen->apel_pagi_at) ? '✅ Hadir' : '❌ Tidak';
            $statusApelSore = $isFriday ? (!empty($absen->apel_sore_at) ? '✅ Hadir' : '❌ Tidak') : 'N/A';

            $emoji = '🟢';
            if ($calc['total'] > 0) $emoji = '🟡';
            if ($calc['total'] > $tunjanganHarian * 0.3) $emoji = '🔴';

            $results[] = [
                'nama' => $user->name,
                'nip' => $user->nip ?? '-',
                'tpp' => 'Rp ' . number_format($user->tpp, 0, ',', '.'),
                'tpp_harian' => 'Rp ' . number_format($tunjanganHarian, 0, ',', '.'),
                'masuk' => $statusMasuk . ' (' . substr($absen->incoming_time, 0, 5) . ')',
                'pulang' => $statusPulang,
                'apel_pagi' => $statusApelPagi,
                'apel_sore' => $statusApelSore,
                'pot_telat' => 'Rp ' . number_format($calc['potongan']['telat']['final'] ?? 0, 0, ',', '.'),
                'pot_pulang' => 'Rp ' . number_format($calc['potongan']['pulang']['final'] ?? 0, 0, ',', '.'),
                'pot_apel_pagi' => 'Rp ' . number_format($calc['potongan']['apel_pagi']['final'] ?? 0, 0, ',', '.'),
                'pot_apel_sore' => 'Rp ' . number_format($calc['potongan']['apel_sore']['final'] ?? 0, 0, ',', '.'),
                'total_pot' => 'Rp ' . number_format($calc['total'], 0, ',', '.'),
                'diterima' => 'Rp ' . number_format($calc['diterima'], 0, ',', '.'),
                'status' => $emoji . ' ' . ($calc['total'] == 0 ? 'SEMPURNA' : 'POTONGAN'),
            ];
        }

        // Tampilkan hasil
        foreach ($results as $i => $row) {
            $no = $i + 1;
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("  #{$no} {$row['nama']} (NIP: {$row['nip']})");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->table(
                ['Item', 'Nilai'],
                [
                    ['TPP Bulanan', $row['tpp']],
                    ['TPP Harian', $row['tpp_harian']],
                    ['Basis 40%', 'Rp ' . number_format(str_replace(['Rp ', '.'], '', $row['tpp_harian']) * 0.4, 0, ',', '.')],
                    ['', ''],
                    ['Status Masuk', $row['masuk']],
                    ['Status Pulang', $row['pulang']],
                    ['Status Apel Pagi', $row['apel_pagi']],
                    ['Status Apel Sore', $row['apel_sore']],
                    ['', ''],
                    ['Pot. Telat', $row['pot_telat']],
                    ['Pot. Pulang', $row['pot_pulang']],
                    ['Pot. Apel Pagi', $row['pot_apel_pagi']],
                    ['Pot. Apel Sore', $row['pot_apel_sore']],
                    ['', ''],
                    ['TOTAL POTONGAN', $row['total_pot']],
                    ['TPP DITERIMA', $row['diterima']],
                    ['VERDICT', $row['status']],
                ]
            );
            $this->newLine();
        }

        $this->warn('⚠️  Ini hanya SIMULASI. Tidak ada data yang disimpan ke database.');
        $this->newLine();

        return 0;
    }
}
