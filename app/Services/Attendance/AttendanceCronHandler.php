<?php

namespace App\Services\Attendance;

use App\Helpers\PotonganLogger;
use App\Models\AttendancesPegawai;
use App\Services\Attendance\Payroll\PayrollCalculator;

class AttendanceCronHandler
{
    protected function config()
    {
        return [
            'jmlHariKerja' => AttendanceCache::jmlHariKerja(),
            'configTpp' => AttendanceCache::potonganTpp()->keyBy('group')
        ];
    }

    public function handle($user)
    {
        $config = $this->config();

        $absen = AttendanceRepository::today($user->id);
        // $absen = AttendanceRepository::fromDate($user->id, '2026-05-12');

        // Skip non-working (Cuti, Izin) DULUAN agar tidak tertimpa Alpha
        if ($absen && $this->isNonWorkingStatus($absen)) {
            return true;
        }

        // Jika tidak ada absen ATAU absen kosong tapi bukan cuti → alpha
        if (!$absen || empty($absen->incoming_time) || $absen->incoming_time === '00:00:00') {
            return $this->handleAlpha($user, $config);
        }

        return $this->handleIncomplete($absen, $user, $config);
    }

    protected function handleAlpha($user, $config)
    {
        $tunjangan = $user->tpp / $config['jmlHariKerja'];
        $alpha = $config['configTpp']['alfa'];

        return AttendancesPegawai::updateOrCreate(
            [
                'pegawai_id' => $user->id,
                'date_attendance' => today(),
            ],
            [
                'dinas_id' => $user->dinas_id,
                'incoming_time' => '00:00:00',
                'outgoing_time' => '00:00:00',
                'status' => 'Tidak Masuk',
                'tunjangan_per_hari' => $tunjangan,
                'config_potongan_tpp_id' => $alpha->id,
                'tpp_diterima' => 0,
                'total_potongan_tpp' => (int) round($tunjangan),
                'ket_tidak_masuk_kerja' => $alpha->title,
                'potongan_tidak_masuk_kerja_persen' => $alpha->persentase_potongan,
                'potongan_tidak_masuk_kerja' => (int) round($tunjangan),
            ]
        );
    }

    protected function isNonWorkingStatus($absen): bool
    {
        $status = strtolower(trim($absen->status ?? ''));

        return in_array($status, ['izin', 'cuti', 'dinas luar']);
    }

    protected function handleIncomplete($absen, $user, $config)
    {
        $calculator = new PayrollCalculator();

        // 🔥 SINGLE SOURCE OF TRUTH
        $calc = $calculator->calculate($absen, $user, $config);

        // 🔥 AUDIT LOG (IDEMPOTENT)
        PotonganLogger::logFromCalculator($absen, $user, $calc);

        $isFriday = now()->dayOfWeekIso === 5;
        $hadirApelPagi = !empty($absen->apel_pagi_at) || strtolower(trim($absen->status_apel_pagi ?? '')) === 'hadir';
        $hadirApelSore = !empty($absen->apel_sore_at) || strtolower(trim($absen->status_apel_sore ?? '')) === 'hadir';

        $data = [
            // ======================
            // CORE PAYROLL (NO ROUND AGAIN)
            // ======================
            'tunjangan_per_hari' => $calc['tunjangan'],
            'total_potongan_tpp' => $calc['total'],
            'tpp_diterima' => $calc['diterima'],

            // ======================
            // BREAKDOWN (FINAL VALUE)
            // ======================
            'potongan_absen_masuk' => $calc['potongan']['telat']['final'],
            'potongan_absen_pulang' => $calc['potongan']['pulang']['final'],
            'potongan_absen_pulang_persen' => $calc['potongan']['pulang']['persen'] ?? 0,
            'potongan_tidak_apel_pagi' => $calc['potongan']['apel_pagi']['final'],
            'potongan_tidak_apel_pagi_persen' => $calc['potongan']['apel_pagi']['persen'] ?? 0,
            'potongan_tidak_apel_sore' => $calc['potongan']['apel_sore']['final'],
            'potongan_tidak_apel_sore_persen' => $calc['potongan']['apel_sore']['persen'] ?? 0,
            
            // Kolom Legacy / Total Apel
            'potongan_tidak_apel' => $calc['potongan']['apel_pagi']['final'] + $calc['potongan']['apel_sore']['final'],
            'potongan_tidak_apel_persen' => ($calc['potongan']['apel_pagi']['persen'] ?? 0) + ($calc['potongan']['apel_sore']['persen'] ?? 0),

            // ======================
            // STATUS (DERIVED, NOT SOURCE)
            // ======================
            'status_apel_pagi' => $hadirApelPagi 
                ? 'Hadir' 
                : $config['configTpp']['apel']->title,

            'status_apel_sore' => $isFriday 
                ? ($hadirApelSore ? 'Hadir' : $config['configTpp']['apel']->title)
                : $absen->status_apel_sore,

            'status_apel' => ($hadirApelPagi && (!$isFriday || $hadirApelSore))
                ? 'Hadir' 
                : $config['configTpp']['apel']->title,

            'status_pulang' => (
                empty($absen->outgoing_time) ||
                $absen->outgoing_time === '00:00:00'
            ) ? $config['configTpp']['pulang']->title : $absen->status_pulang,
        ];

        return AttendancesPegawai::where('id', $absen->id)->update($data);
    }
}
