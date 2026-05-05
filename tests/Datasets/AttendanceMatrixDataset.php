<?php

namespace Datasets;

use App\Services\Attendance\AttendanceCache;
use Carbon\Carbon;

class AttendanceMatrixDataset
{
    /**
     * Contoh generate matrix sederhana
     */
    public function getMatrix(): array
    {
        $statuses = [
            'Masuk',
            'Tidak Masuk',   // alfa / tidak hadir
            'izin',
            'cuti',
            'Dinas Luar'
        ];

        $clockInOptions = [true, false];   // hanya berlaku untuk Masuk / Tidak Masuk
        $clockOutOptions = [true, false];  // hanya berlaku jika masuk
        $apelPagiOptions = [true, false];  // hanya berlaku jika masuk
        $apelSoreOptions = [true, false];  // hanya berlaku jika masuk

        $matrix = [];

        foreach ($statuses as $status) {
            // Izin/Cuti/Dinas Luar → tidak ada absen masuk/pulang/apel
            if (in_array($status, ['izin', 'cuti', 'Dinas Luar','Tidak Masuk'])) {
                $matrix[] = [
                    'status' => $status,
                    'incoming_time' => null,
                    'outgoing_time' => null,
                    'apel_pagi_at' => null,
                    'apel_sore_at' => null,
                    'tpp' => 18181,
                ];
                continue;
            }

            foreach ($clockInOptions as $in) {
                foreach ($clockOutOptions as $out) {
                    foreach ($apelPagiOptions as $pagi) {
                        foreach ($apelSoreOptions as $sore) {

                            // Jika tidak absen masuk → pulang/apel otomatis null
                            $matrix[] = [
                                'status' => $status,
                                'incoming_time' => $in ? '08:00:00' : null,
                                'outgoing_time' => $in && $out ? '17:00:00' : null,
                                'apel_pagi_at' => $in && $pagi ? now() : null,
                                'apel_sore_at' => $in && $sore ? now() : null,
                                'tpp' => 18181,
                            ];
                        }
                    }
                }
            }
        }

        return $matrix;
    }

    public function getConfig(): array
    {
        return [
            'jmlHariKerja' => 5,
            'configTpp' => AttendanceCache::potonganTpp()->keyBy('group'),
        ];
    }
}
