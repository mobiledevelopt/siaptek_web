<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AuditSemuaPotongan extends Command
{
    protected $signature = 'absen:audit-potongan {--month= : Bulan (01-12)} {--year= : Tahun (misal 2026)} {--fix : Otomatis perbaiki data yang salah} {--export : Export data yang salah ke CSV}';
    protected $description = 'Validasi dan temukan data presensi yang semua potongan TPP-nya salah hitung (Telat, PSW, Apel, Alfa, Cuti)';

    public function handle()
    {
        $month = $this->option('month');
        $year = $this->option('year');
        $isFix = $this->option('fix');
        $isExport = $this->option('export');

        $query = \App\Models\AttendancesPegawai::query();

        if ($month && $year) {
            $query->whereYear('date_attendance', $year)
                  ->whereMonth('date_attendance', $month);
        }

        $this->info("Mengambil konfigurasi potongan telat masuk...");
        $levelTelat = \App\Models\ConfigPotTpp::where('group', 'masuk')->get();

        $this->info("Memulai validasi data untuk SEMUA jenis potongan. Ini mungkin membutuhkan waktu beberapa menit...");
        
        $salahCount = 0;
        $totalCheck = 0;
        $bar = $this->output->createProgressBar($query->count());
        $bar->start();

        $csvData = [];
        if ($isExport) {
            $csvData[] = ['ID Absen', 'Pegawai ID', 'Tanggal', 'Jenis Kesalahan', 'Aktual', 'Seharusnya'];
        }

        $query->chunk(1000, function ($absens) use ($levelTelat, $isFix, $isExport, &$salahCount, &$totalCheck, &$csvData, $bar) {
            foreach ($absens as $absen) {
                $tunjanganPerHari = (float) $absen->tunjangan_per_hari;
                $isSalah = false;
                $msgs = [];
                $csvRows = [];

                // 1. Cek Potongan Telat Masuk
                if ((float)$absen->menit_telat_masuk > 0 || (float)$absen->potongan_absen_masuk > 0) {
                    $menit = (int) $absen->menit_telat_masuk;
                    $match = $levelTelat->first(function ($row) use ($menit) {
                        return $menit >= (int)$row->dari_meni && $menit <= (int)$row->sampai_menit;
                    });

                    $persenSeharusnya = $absen->potongan_absen_masuk_persen;
                    $statusSeharusnya = $absen->status_masuk;
                    $configIdSeharusnya = $absen->config_potongan_tpp_id;

                    if ($menit > 0) {
                        if ($match) {
                            $persenSeharusnya = $match->persentase_potongan;
                            $statusSeharusnya = $match->title;
                            $configIdSeharusnya = $match->id;
                        } else {
                            $max = $levelTelat->sortByDesc(function ($row) { return (int)$row->sampai_menit; })->first();
                            if ($max && $menit > (int)$max->sampai_menit) {
                                $persenSeharusnya = $max->persentase_potongan;
                                $statusSeharusnya = $max->title;
                                $configIdSeharusnya = $max->id;
                            }
                        }
                    }

                    $potonganSeharusnya = (int) round(($tunjanganPerHari * 40 / 100) * ($persenSeharusnya / 100));
                    $potonganAktual = (int) $absen->potongan_absen_masuk;

                    if ($potonganSeharusnya !== $potonganAktual || $persenSeharusnya !== $absen->potongan_absen_masuk_persen) {
                        $isSalah = true;
                        $msgs[] = "Telat Masuk Aktual: Rp $potonganAktual ({$absen->potongan_absen_masuk_persen}%) -> Seharusnya: Rp $potonganSeharusnya ({$persenSeharusnya}%)";
                        if ($isExport) $csvRows[] = [$absen->id, $absen->pegawai_id, $absen->date_attendance, 'Telat Masuk', "Rp $potonganAktual ({$absen->potongan_absen_masuk_persen}%)", "Rp $potonganSeharusnya ({$persenSeharusnya}%)"];
                        if ($isFix) {
                            $absen->potongan_absen_masuk_persen = $persenSeharusnya;
                            $absen->potongan_absen_masuk = $potonganSeharusnya;
                            $absen->status_masuk = $statusSeharusnya;
                            $absen->config_potongan_tpp_id = $configIdSeharusnya;
                        }
                    }
                }

                // 2. Cek Potongan Pulang Cepat (PSW) / Tidak Absen Pulang
                $isPsw = empty($absen->outgoing_time) || $absen->outgoing_time === '00:00:00' || $absen->status_pulang === 'Tidak Absen Pulang (PSW)';
                
                if ($absen->status === 'Masuk' && ($isPsw || (float)$absen->potongan_absen_pulang > 0 || (float)$absen->potongan_absen_pulang_persen > 0)) {
                    // PSW SOP is 20% according to config, or 0% if not PSW but has weird DB values
                    $persenSop = $isPsw ? 20 : 0; 
                    
                    $persenAktual = (float) $absen->potongan_absen_pulang_persen;

                    if ($persenAktual !== (float)$persenSop && $isFix) {
                        $absen->potongan_absen_pulang_persen = $persenSop;
                        $persenAktual = $persenSop;
                    }

                    if ($isPsw && $absen->status_pulang !== 'Tidak Absen Pulang (PSW)' && $isFix) {
                        $absen->status_pulang = 'Tidak Absen Pulang (PSW)';
                    }

                    $seharusnya = (int) round(($tunjanganPerHari * 40 / 100) * ($persenSop / 100));
                    if ((int)$absen->potongan_absen_pulang !== $seharusnya || $persenAktual !== (float)$persenSop) {
                        $isSalah = true;
                        $msgs[] = "Pulang Cepat (PSW) Aktual: Rp {$absen->potongan_absen_pulang} ({$absen->potongan_absen_pulang_persen}%) -> Seharusnya: Rp $seharusnya ({$persenSop}%)";
                        if ($isExport) $csvRows[] = [$absen->id, $absen->pegawai_id, $absen->date_attendance, 'Pulang Cepat (PSW)', "Rp {$absen->potongan_absen_pulang} ({$absen->potongan_absen_pulang_persen}%)", "Rp $seharusnya ({$persenSop}%)"];
                        if ($isFix) $absen->potongan_absen_pulang = $seharusnya;
                    }
                }

                // 3. Cek Apel Pagi
                $hadirApelPagi = !empty($absen->apel_pagi_at) || strtolower(trim($absen->status_apel_pagi ?? '')) === 'hadir';
                $isTidakApelPagi = !$hadirApelPagi;

                if ($absen->status === 'Masuk' && ($isTidakApelPagi || (float)$absen->potongan_tidak_apel_pagi > 0 || (float)$absen->potongan_tidak_apel_pagi_persen > 0)) {
                    $isFriday = \Carbon\Carbon::parse($absen->date_attendance)->dayOfWeekIso == 5;
                    $persenSop = $isTidakApelPagi ? ($isFriday ? 10 : 20) : 0; // Sesuai SOP
                    $persenAktual = (float) $absen->potongan_tidak_apel_pagi_persen;
                    
                    if ($persenAktual !== (float)$persenSop && $isFix) {
                        $absen->potongan_tidak_apel_pagi_persen = $persenSop;
                        $persenAktual = $persenSop;
                    }

                    if ($isTidakApelPagi && $absen->status_apel_pagi !== 'Tidak Apel' && $isFix) {
                        $absen->status_apel_pagi = 'Tidak Apel';
                    }

                    $seharusnya = (int) round(($tunjanganPerHari * 40 / 100) * ($persenSop / 100));
                    if ((int)$absen->potongan_tidak_apel_pagi !== $seharusnya || $persenAktual !== (float)$persenSop) {
                        $isSalah = true;
                        $msgs[] = "Apel Pagi Aktual: Rp {$absen->potongan_tidak_apel_pagi} ({$absen->potongan_tidak_apel_pagi_persen}%) -> Seharusnya: Rp $seharusnya ({$persenSop}%)";
                        if ($isExport) $csvRows[] = [$absen->id, $absen->pegawai_id, $absen->date_attendance, 'Apel Pagi', "Rp {$absen->potongan_tidak_apel_pagi} ({$absen->potongan_tidak_apel_pagi_persen}%)", "Rp $seharusnya ({$persenSop}%)"];
                        if ($isFix) $absen->potongan_tidak_apel_pagi = $seharusnya;
                    }
                }

                // 4. Cek Apel Sore
                $isFriday = \Carbon\Carbon::parse($absen->date_attendance)->dayOfWeekIso == 5;
                $hadirApelSore = !empty($absen->apel_sore_at) || strtolower(trim($absen->status_apel_sore ?? '')) === 'hadir';
                $isTidakApelSore = !$hadirApelSore;

                $hasApelSoreData = (float)$absen->potongan_tidak_apel_sore > 0 || (float)$absen->potongan_tidak_apel_sore_persen > 0;
                
                // Audit jika: ini hari Jumat (lalu dia tidak hadir), ATAU ada data potongan Apel Sore yang terekam (padahal bukan Jumat)
                if ($absen->status === 'Masuk' && (($isFriday && $isTidakApelSore) || $hasApelSoreData)) {
                    $persenSop = ($isFriday && $isTidakApelSore) ? 10 : 0; // Hanya Jumat yang ada Apel Sore (10%)
                    
                    $persenAktual = (float) $absen->potongan_tidak_apel_sore_persen;

                    if ($persenAktual !== (float)$persenSop && $isFix) {
                        $absen->potongan_tidak_apel_sore_persen = $persenSop;
                        $persenAktual = $persenSop;
                    }

                    if ($isFriday && $isTidakApelSore && $absen->status_apel_sore !== 'Tidak Apel' && $isFix) {
                        $absen->status_apel_sore = 'Tidak Apel';
                    } else if (!$isFriday && $absen->status_apel_sore === 'Tidak Apel' && $isFix) {
                        $absen->status_apel_sore = null;
                    }

                    $seharusnya = (int) round(($tunjanganPerHari * 40 / 100) * ($persenSop / 100));
                    if ((int)$absen->potongan_tidak_apel_sore !== $seharusnya || $persenAktual !== (float)$persenSop) {
                        $isSalah = true;
                        $msgs[] = "Apel Sore Aktual: Rp {$absen->potongan_tidak_apel_sore} ({$absen->potongan_tidak_apel_sore_persen}%) -> Seharusnya: Rp $seharusnya ({$persenSop}%)";
                        if ($isExport) $csvRows[] = [$absen->id, $absen->pegawai_id, $absen->date_attendance, 'Apel Sore', "Rp {$absen->potongan_tidak_apel_sore} ({$absen->potongan_tidak_apel_sore_persen}%)", "Rp $seharusnya ({$persenSop}%)"];
                        if ($isFix) $absen->potongan_tidak_apel_sore = $seharusnya;
                    }
                }

                // 5. Cek Tidak Masuk / Alfa
                if ((float)$absen->potongan_tidak_masuk_kerja > 0 || (float)$absen->potongan_tidak_masuk_kerja_persen > 0) {
                    $persen = (float) $absen->potongan_tidak_masuk_kerja_persen;
                    $seharusnya = (int) round(($tunjanganPerHari * 100 / 100) * ($persen / 100));
                    if ((int)$absen->potongan_tidak_masuk_kerja !== $seharusnya) {
                        $isSalah = true;
                        $msgs[] = "Alfa Aktual: Rp {$absen->potongan_tidak_masuk_kerja} -> Seharusnya: Rp $seharusnya";
                        if ($isExport) $csvRows[] = [$absen->id, $absen->pegawai_id, $absen->date_attendance, 'Alfa / Tidak Masuk', "Rp {$absen->potongan_tidak_masuk_kerja}", "Rp $seharusnya"];
                        if ($isFix) $absen->potongan_tidak_masuk_kerja = $seharusnya;
                    }
                }

                // 6. Cek Cuti
                if ((float)$absen->potongan_cuti > 0 || (float)$absen->potongan_cuti_persen > 0) {
                    $persen = (float) $absen->potongan_cuti_persen;
                    $seharusnya = (int) round(($tunjanganPerHari * 100 / 100) * ($persen / 100));
                    if ((int)$absen->potongan_cuti !== $seharusnya) {
                        $isSalah = true;
                        $msgs[] = "Cuti Aktual: Rp {$absen->potongan_cuti} -> Seharusnya: Rp $seharusnya";
                        if ($isExport) $csvRows[] = [$absen->id, $absen->pegawai_id, $absen->date_attendance, 'Cuti', "Rp {$absen->potongan_cuti}", "Rp $seharusnya"];
                        if ($isFix) $absen->potongan_cuti = $seharusnya;
                    }
                }

                if ($isSalah) {
                    $salahCount++;
                    if ($isExport) {
                        foreach ($csvRows as $row) {
                            $csvData[] = $row;
                        }
                    }
                    if ($isFix) {
                        // Total potongan = telat + pulang + apel pagi + apel sore + alfa + cuti
                        $absen->total_potongan_tpp = 
                            (int)$absen->potongan_absen_masuk + 
                            (int)$absen->potongan_absen_pulang + 
                            (int)$absen->potongan_tidak_apel_pagi + 
                            (int)$absen->potongan_tidak_apel_sore + 
                            (int)$absen->potongan_tidak_masuk_kerja + 
                            (int)$absen->potongan_cuti;
                        
                        $absen->tpp_diterima = (int) round($tunjanganPerHari - $absen->total_potongan_tpp);
                        // Prevent negative TPP diterima
                        if ($absen->tpp_diterima < 0) {
                            $absen->tpp_diterima = 0;
                        }
                        
                        $absen->save();
                    } else if (!$isExport) {
                        // Tampilkan hanya beberapa contoh
                        if ($salahCount <= 20) {
                            $this->newLine();
                            $this->warn("ID {$absen->id} | Tgl: {$absen->date_attendance}");
                            foreach ($msgs as $msg) {
                                $this->line("  - " . $msg);
                            }
                        }
                    }
                }

                $totalCheck++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        if ($isExport && $salahCount > 0) {
            $path = storage_path('app/public/audit_potongan_salah.csv');
            $file = fopen($path, 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
            $this->info("File CSV berhasil disimpan di: " . $path);
            $this->info("Anda dapat mendownloadnya melalui browser di: " . url('storage/audit_potongan_salah.csv'));
        }

        $this->info("Selesai mengecek {$totalCheck} data presensi.");
        
        if ($salahCount > 0) {
            $this->error("Ditemukan {$salahCount} baris data dengan nominal potongan yang salah!");
            if (!$isFix) {
                $this->info("Jalankan command ini dengan flag --fix untuk memperbaiki data secara otomatis:");
                $this->comment("php artisan absen:audit-potongan --fix");
            } else {
                $this->info("Semua {$salahCount} baris data yang salah telah berhasil dikalkulasi ulang dan diperbaiki.");
            }
        } else {
            $this->info("Luar biasa! Semua potongan data (Telat, PSW, Apel, Alfa, Cuti) sudah sesuai dengan perhitungan matematika.");
        }
    }
}
