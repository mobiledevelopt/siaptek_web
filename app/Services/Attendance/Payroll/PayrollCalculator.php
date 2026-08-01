<?php

namespace App\Services\Attendance\Payroll;

class PayrollCalculator
{
    /**
     * Hitung potongan TPP berdasarkan absen.
     */
    public function calculate($absen, $user, array $config): array
    {
        // Cek apakah hari ini Jumat berdasarkan TANGGAL ABSEN, bukan now()
        $absenDate = $absen ? \Carbon\Carbon::parse($absen->date_attendance) : now();
        $isFriday = $absenDate->dayOfWeekIso == 5;
        $jmlHariKerja = max(1, $config['jmlHariKerja'] ?? 22);
        $tunjangan = ($user->tpp ?? 0) / $jmlHariKerja;


        // ✅ skip non-working
        if (in_array(strtolower($absen->status), ['izin', 'cuti', 'dinas luar'])) {
            return [
                'tunjangan' => $tunjangan,
                'potongan' => [],
                'total' => 0,
                'diterima' => (int) round($tunjangan),
            ];
        }

        // ======================
        // RULE 2: TIDAK MASUK = POTONG 100%
        // ======================
        if (empty($absen->incoming_time) || $absen->incoming_time === '00:00:00') {
            return [
                'tunjangan' => $tunjangan,
                'potongan' => [
                    'telat' => 0,
                    'pulang' => 0,
                    'apel_pagi' => 0,
                    'apel_sore' => 0,
                ],
                'total' => $tunjangan,
                'diterima' => 0,
            ];
        }

        $telat = (float) ($absen->potongan_absen_masuk ?? 0);
        $pulang = 0;
        $apelPagi = 0;
        $apelSore = 0;

        // ======================
        // POTONGAN PULANG
        // ======================
        $pulangPersen = 0;
        $isPsw = empty($absen->outgoing_time) || $absen->outgoing_time === '00:00:00' || $absen->status_pulang === 'Tidak Absen Pulang (PSW)';
        
        if ($isPsw) {
            $cfgPulang = $config['configTpp']['pulang'];
            $pulang = $this->potong($tunjangan, 0.4, $cfgPulang->persentase_potongan);
            $pulangPersen = $cfgPulang->persentase_potongan;
        }

        // ======================
        // POTONGAN APEL
        // ======================
        $cfgApel = $config['configTpp']['apel'];
        $persen = $isFriday ? $cfgApel->persentase_potongan / 2 : $cfgApel->persentase_potongan;

        $hadirApelPagi = !empty($absen->apel_pagi_at) || strtolower(trim($absen->status_apel_pagi ?? '')) === 'hadir';
        $apelPagiPersen = 0;
        if (!$hadirApelPagi) {
            $apelPagi = $this->potong($tunjangan, 0.4, $persen);
            $apelPagiPersen = $persen;
        }

        $hadirApelSore = !empty($absen->apel_sore_at) || strtolower(trim($absen->status_apel_sore ?? '')) === 'hadir';
        $apelSorePersen = 0;
        if ($isFriday && !$hadirApelSore) {
            $apelSore = $this->potong($tunjangan, 0.4, $persen);
            $apelSorePersen = $persen;
        }

        $totalRaw = $telat + $pulang + $apelPagi + $apelSore;

        return [
            'tunjangan' => $tunjangan,
            'potongan' => [
                'telat' => [
                    'raw' => $telat,
                    'final' => (int) round($telat),
                    'persen' => null,
                    'ket' => 'Telat'
                ],
                'pulang' => [
                    'raw' => $pulang,
                    'final' => (int) round($pulang),
                    'persen' => $pulangPersen,
                    'ket' => 'Tidak Absen Pulang'
                ],
                'apel_pagi' => [
                    'raw' => $apelPagi,
                    'final' => (int) round($apelPagi),
                    'persen' => $apelPagiPersen,
                    'ket' => 'Tidak Apel Pagi'
                ],
                'apel_sore' => [
                    'raw' => $apelSore,
                    'final' => (int) round($apelSore),
                    'persen' => $apelSorePersen,
                    'ket' => 'Tidak Apel Sore'
                ],
            ],
            'total' => (int) round($totalRaw),
            'diterima' => (int) round($tunjangan - $totalRaw),
        ];
    }

    private function potong(float $tunjangan, float $fraction, float $percent): float
    {
        return $tunjangan * $fraction * ($percent / 100);
    }
}
