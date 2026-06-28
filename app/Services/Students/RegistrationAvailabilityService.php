<?php

namespace App\Services\Students;

use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Models\Institution\IntakePeriod;

class RegistrationAvailabilityService
{
    public function currentIntakePeriod(): ?IntakePeriod
    {
        return IntakePeriod::query()
            ->orderByDesc('end_date')
            ->first();
    }

    public function isRegistrationOpen(): bool
    {
        return $this->currentIntakePeriod()?->status === IntakePeriodStatusEnum::Open;
    }

    public function blockReason(): ?IntakePeriodStatusEnum
    {
        $status = $this->currentIntakePeriod()?->status;

        if ($status === null || $status === IntakePeriodStatusEnum::Open) {
            return null;
        }

        return $status;
    }

    public function maintenanceMessage(): string
    {
        $intakePeriod = $this->currentIntakePeriod();
        $reason = $this->blockReason();

        if ($intakePeriod === null || $reason === null) {
            return '';
        }

        return $reason->maintenanceMessage($intakePeriod->name);
    }
}
