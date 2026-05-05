<?php

namespace App\Services\Attendance;

use App\Services\Attendance\Rules\{
    HolidayRule,
    LeaveRule,
    PermissionRule
};

class AttendanceRuleEngine
{
    protected array $rules = [];

    public function __construct()
    {
        $this->rules = [
            new HolidayRule(),
            new LeaveRule(),
            new PermissionRule(),
        ];
    }

    public function handle($user, $absen, $config)
    {
        foreach ($this->rules as $rule) {
            if ($rule->applies($user, $absen, $config)) {
                return $rule->process($user, $absen, $config);
            }
        }

        return null;
    }
}