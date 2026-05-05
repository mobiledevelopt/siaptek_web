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

        // Jika tidak ada absen → alpha
        if (!$absen || empty($absen->incoming_time)) {
            return $this->handleAlpha($user, $config);
        }

        // Skip non-working
        if ($this->isNonWorkingStatus($absen)) {
            return true;
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

        return in_array($status, ['izin', 'cuti']);
    }

    protected function handleIncomplete($absen, $user, $config)
    {
        $calculator = new PayrollCalculator();

        // 🔥 SINGLE SOURCE OF TRUTH
        $calc = $calculator->calculate($absen, $user, $config);

        // 🔥 AUDIT LOG (IDEMPOTENT)
        PotonganLogger::logFromCalculator($absen, $user, $calc);

        $isFriday = now()->dayOfWeekIso === 5;

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
            'potongan_tidak_apel_pagi' => $calc['potongan']['apel_pagi']['final'],
            'potongan_tidak_apel_sore' => $calc['potongan']['apel_sore']['final'],

            // ======================
            // STATUS (DERIVED, NOT SOURCE)
            // ======================
            'status_apel_pagi' => empty($absen->apel_pagi_at)
                ? $config['configTpp']['apel']->title
                : $absen->status_apel_pagi,

            'status_apel_sore' => ($isFriday && empty($absen->apel_sore_at))
                ? $config['configTpp']['apel']->title
                : $absen->status_apel_sore,

            'status_apel' => (
                empty($absen->apel_pagi_at) ||
                ($isFriday && empty($absen->apel_sore_at))
            ) ? $config['configTpp']['apel']->title : $absen->status_apel,

            'status_pulang' => (
                empty($absen->outgoing_time) ||
                $absen->outgoing_time === '00:00:00'
            ) ? $config['configTpp']['pulang']->title : $absen->status_pulang,
        ];

        return AttendancesPegawai::where('id', $absen->id)->update($data);
    }
}
