<?php

namespace App\Services\Students;

use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Students\ApplicationTrackEnum;
use App\Models\Institution\IntakePeriod;
use Illuminate\Database\Eloquent\Collection;

class RegistrationAvailabilityService
{
    public function currentRegularIntakePeriod(): ?IntakePeriod
    {
        return IntakePeriod::query()
            ->where('is_continuous', false)
            ->orderByDesc('end_date')
            ->first();
    }

    /**
     * @deprecated Use currentRegularIntakePeriod() — kept for callers that mean "latest regular intake".
     */
    public function currentIntakePeriod(): ?IntakePeriod
    {
        return $this->currentRegularIntakePeriod();
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

    public function activeContinuousIntakePeriod(): ?IntakePeriod
    {
        return $this->continuousIntakePeriod();
    }

    /**
     * @return Collection<int, IntakePeriod>
     */
    public function openRegularIntakePeriods()
    {
        return IntakePeriod::query()
            ->where('is_continuous', false)
            ->where('is_active', true)
            ->where('status', IntakePeriodStatusEnum::Open)
            ->orderByDesc('end_date')
            ->get();
    }

    public function isRegularRegistrationOpen(): bool
    {
        return $this->openRegularIntakePeriods()->isNotEmpty();
    }

    public function isContinuousRegistrationOpen(): bool
    {
        return $this->continuousIntakePeriod() !== null;
    }

    public function isApprenticeRegistrationOpen(): bool
    {
        return $this->isRegularRegistrationOpen();
    }

    /**
     * True when at least one apply track is available (regular or continuous).
     */
    public function isAnyRegistrationOpen(): bool
    {
        return $this->isRegularRegistrationOpen() || $this->isContinuousRegistrationOpen();
    }

    /**
     * Backward-compatible alias: regular registration open (non-continuous intakes).
     */
    public function isRegistrationOpen(): bool
    {
        return $this->isRegularRegistrationOpen();
    }

    public function isTrackOpen(ApplicationTrackEnum $track): bool
    {
        return match ($track) {
            ApplicationTrackEnum::Regular => $this->isRegularRegistrationOpen(),
            ApplicationTrackEnum::Continuous => $this->isContinuousRegistrationOpen(),
            ApplicationTrackEnum::Apprentice => $this->isApprenticeRegistrationOpen(),
        };
    }

    public function blockReason(): ?IntakePeriodStatusEnum
    {
        if ($this->isRegularRegistrationOpen()) {
            return null;
        }

        $status = $this->currentRegularIntakePeriod()?->status;

        if ($status === null || $status === IntakePeriodStatusEnum::Open) {
            return null;
        }

        return $status;
    }

    public function maintenanceMessage(): string
    {
        $intakePeriod = $this->currentRegularIntakePeriod();
        $reason = $this->blockReason();

        if ($intakePeriod === null || $reason === null) {
            return '';
        }

        return $reason->maintenanceMessage($intakePeriod->name);
    }

    /**
     * @return array{regularOpen: bool, continuousOpen: bool, apprenticeOpen: bool, isOpen: bool, status: string|null}
     */
    public function sharedProps(): array
    {
        return [
            'regularOpen' => $this->isRegularRegistrationOpen(),
            'continuousOpen' => $this->isContinuousRegistrationOpen(),
            'apprenticeOpen' => $this->isApprenticeRegistrationOpen(),
            'isOpen' => $this->isAnyRegistrationOpen(),
            'status' => $this->blockReason()?->value,
            'maintenanceUrl' => route('portal.registration.maintenance'),
        ];
    }
}
