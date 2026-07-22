<?php

namespace App\Services\Students;

use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Models\Institution\IntakePeriod;
use App\Models\Students\StudentApplication;

class IntakePeriodResolver
{
    /**
     * Active open non-continuous intakes used for regular / apprentice applications.
     *
     * @return array<int, int>
     */
    public function activeIntakePeriodIds(): array
    {
        return IntakePeriod::query()
            ->where('is_continuous', false)
            ->where('is_active', true)
            ->where('status', IntakePeriodStatusEnum::Open)
            ->pluck('id')
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function activeContinuousIntakePeriodIds(): array
    {
        return IntakePeriod::query()
            ->where('is_continuous', true)
            ->where('is_active', true)
            ->where('status', IntakePeriodStatusEnum::Open)
            ->pluck('id')
            ->all();
    }

    public function continuousIntakePeriod(): ?IntakePeriod
    {
        return IntakePeriod::query()
            ->where('is_continuous', true)
            ->where('is_active', true)
            ->where('status', IntakePeriodStatusEnum::Open)
            ->orderByDesc('end_date')
            ->first();
    }

    public function isApplicationInActiveIntake(StudentApplication $application): bool
    {
        $ids = array_merge(
            $this->activeIntakePeriodIds(),
            $this->activeContinuousIntakePeriodIds(),
        );

        return in_array($application->intake_period_id, $ids, true);
    }

    public function latestIntakePeriodId(): ?int
    {
        $id = IntakePeriod::query()
            ->where('is_continuous', false)
            ->orderByDesc('end_date')
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * Open active intakes (regular + continuous) plus the latest regular intake (any status),
     * so offer letters remain downloadable after the current intake is closed or suspended.
     *
     * @return array<int, int>
     */
    public function offerLetterIntakePeriodIds(): array
    {
        $ids = array_merge(
            $this->activeIntakePeriodIds(),
            $this->activeContinuousIntakePeriodIds(),
        );
        $latestId = $this->latestIntakePeriodId();

        if ($latestId !== null) {
            $ids[] = $latestId;
        }

        return array_values(array_unique($ids));
    }

    public function isApplicationEligibleForOfferLetter(StudentApplication $application): bool
    {
        if (in_array(
            $application->intake_period_id,
            $this->offerLetterIntakePeriodIds(),
            true
        )) {
            return true;
        }

        $application->loadMissing('modeOfStudy');

        if ($application->modeOfStudy === null) {
            return false;
        }

        return app(ApplicationEligibilityService::class)->isOjetMode($application->modeOfStudy);
    }
}
