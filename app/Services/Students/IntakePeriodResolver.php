<?php

namespace App\Services\Students;

use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Models\Institution\IntakePeriod;
use App\Models\Students\StudentApplication;

class IntakePeriodResolver
{
    /**
     * @return array<int, int>
     */
    public function activeIntakePeriodIds(): array
    {
        return IntakePeriod::query()
            ->where('is_active', true)
            ->where('status', IntakePeriodStatusEnum::Open)
            ->pluck('id')
            ->all();
    }

    public function isApplicationInActiveIntake(StudentApplication $application): bool
    {
        return in_array($application->intake_period_id, $this->activeIntakePeriodIds(), true);
    }
}
