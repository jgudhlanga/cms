<?php

namespace App\Enums\Institution;

enum IntakePeriodStatusEnum: string
{
    case Open = 'open';
    case Suspended = 'suspended';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => __('trans.intake_period_status_open'),
            self::Suspended => __('trans.intake_period_status_suspended'),
            self::Closed => __('trans.intake_period_status_closed'),
        };
    }

    public function maintenanceMessage(string $intakeName): string
    {
        return match ($this) {
            self::Suspended => __('trans.registration_maintenance_suspended', ['intake' => $intakeName]),
            self::Closed => __('trans.registration_maintenance_closed', ['intake' => $intakeName]),
            self::Open => '',
        };
    }
}
