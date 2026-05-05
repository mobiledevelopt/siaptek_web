<?php

namespace App\Services\Attendance\Rules;

interface AttendanceRule
{
    public function applies($user, $absen, $config): bool;

    public function process($user, $absen, $config): array;
}