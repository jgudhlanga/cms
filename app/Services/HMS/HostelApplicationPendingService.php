<?php

namespace App\Services\HMS;

use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Models\HMS\HostelApplication;

class HostelApplicationPendingService
{
    public const BLOCKER_PENDING_APPLICATION = 'pending_application_exists';

    public function studentHasInFlightApplication(int $studentId, ?int $exceptApplicationId = null): bool
    {
        return HostelApplication::query()
            ->where('student_id', $studentId)
            ->whereIn('status', [
                HostelApplicationStatusEnum::PENDING,
                HostelApplicationStatusEnum::AWAITING_PAYMENT,
            ])
            ->when($exceptApplicationId !== null, fn ($query) => $query->where('id', '!=', $exceptApplicationId))
            ->exists();
    }

    public function studentHasPendingApplication(int $studentId, ?int $exceptApplicationId = null): bool
    {
        return $this->studentHasInFlightApplication($studentId, $exceptApplicationId);
    }
}
