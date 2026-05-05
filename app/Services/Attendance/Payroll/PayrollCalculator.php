<?php

namespace App\Services\Attendance\Payroll;

class PayrollCalculator
{
    /**
     * Hitung potongan TPP berdasarkan absen.
     */
    public function calculate($absen, $user, array $config): array
    {
        // Cek apakah hari ini Jumat
        $isFriday = now()->dayOfWeekIso == 5;
        $tunjangan = $user->tpp / $config['jmlHariKerja'];


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
        if (empty($absen->incoming_time)) {
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
        if (empty($absen->outgoing_time) || $absen->outgoing_time === '00:00:00') {
            $cfg = $config['configTpp']['pulang'];
            $pulang = $this->potong($tunjangan, 0.4, $cfg->persentase_potongan);
        }

        // ======================
        // POTONGAN APEL
        // ======================
        $cfgApel = $config['configTpp']['apel'];
        $persen = $isFriday ? $cfgApel->persentase_potongan / 2 : $cfgApel->persentase_potongan;

        if (empty($absen->apel_pagi_at)) {
            $apelPagi = $this->potong($tunjangan, 0.4, $persen);
        }

        if ($isFriday && empty($absen->apel_sore_at)) {
            $apelSore = $this->potong($tunjangan, 0.4, $persen);
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
                    'persen' => $cfg->persentase_potongan ?? null,
                    'ket' => 'Tidak Absen Pulang'
                ],
                'apel_pagi' => [
                    'raw' => $apelPagi,
                    'final' => (int) round($apelPagi),
                    'persen' => $persen,
                    'ket' => 'Tidak Apel Pagi'
                ],
                'apel_sore' => [
                    'raw' => $apelSore,
                    'final' => (int) round($apelSore),
                    'persen' => $persen,
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
