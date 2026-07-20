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

    public function latestIntakePeriodId(): ?int
    {
        $id = IntakePeriod::query()
            ->orderByDesc('end_date')
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * Open active intakes plus the latest intake (any status), so offer letters
     * remain downloadable after the current intake is closed or suspended.
     *
     * @return array<int, int>
     */
    public function offerLetterIntakePeriodIds(): array
    {
        $ids = $this->activeIntakePeriodIds();
        $latestId = $this->latestIntakePeriodId();

        if ($latestId !== null) {
            $ids[] = $latestId;
        }

        return array_values(array_unique($ids));
    }

    public function isApplicationEligibleForOfferLetter(StudentApplication $application): bool
    {
        return in_array(
            $application->intake_period_id,
            $this->offerLetterIntakePeriodIds(),
            true
        );
    }
}
