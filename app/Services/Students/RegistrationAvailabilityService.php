<?php

namespace App\Services\Students;

use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Students\ApplicationTrackEnum;
use App\Models\Institution\IntakePeriod;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class RegistrationAvailabilityService
{
    private const string SHARED_PROPS_CACHE_PREFIX = 'registration_availability:';

    private const string SHARED_PROPS_VERSION_KEY = 'registration_availability_version';

    private const int SHARED_PROPS_TTL_SECONDS = 60;

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
            ApplicationTrackEnum::Transfer => $this->isTransferRegistrationOpen(),
        };
    }

    public function isTransferRegistrationOpen(): bool
    {
        return IntakePeriod::query()
            ->where('is_active', true)
            ->where('status', IntakePeriodStatusEnum::Open)
            ->where('show_transfer_path', true)
            ->exists();
    }

    public function blockReason(): ?IntakePeriodStatusEnum
    {
        return $this->blockReasonWhen($this->isRegularRegistrationOpen());
    }

    private function blockReasonWhen(bool $regularOpen): ?IntakePeriodStatusEnum
    {
        if ($regularOpen) {
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
     * Shared with every guest and student page, so the intake lookups run once and are cached
     * briefly. Any intake period change flushes the cache (see IntakePeriod::booted).
     *
     * @return array{regularOpen: bool, continuousOpen: bool, apprenticeOpen: bool, isOpen: bool, status: string|null, maintenanceUrl: string}
     */
    public function sharedProps(): array
    {
        /** @var array{regularOpen: bool, continuousOpen: bool, apprenticeOpen: bool, isOpen: bool, status: string|null} $availability */
        $availability = Cache::remember($this->sharedPropsCacheKey(), self::SHARED_PROPS_TTL_SECONDS, function (): array {
            $regularOpen = $this->isRegularRegistrationOpen();
            $continuousOpen = $this->isContinuousRegistrationOpen();

            return [
                'regularOpen' => $regularOpen,
                'continuousOpen' => $continuousOpen,
                'apprenticeOpen' => $regularOpen,
                'isOpen' => $regularOpen || $continuousOpen,
                'status' => $this->blockReasonWhen($regularOpen)?->value,
            ];
        });

        return [
            ...$availability,
            'maintenanceUrl' => route('portal.registration.maintenance'),
        ];
    }

    public function forgetSharedProps(): void
    {
        Cache::forever(self::SHARED_PROPS_VERSION_KEY, $this->sharedPropsVersion() + 1);
    }

    /**
     * Intake queries are tenant scoped for non-root users (TenantScope), so the key follows the same rule.
     */
    private function sharedPropsCacheKey(): string
    {
        $user = Auth::user();
        $scope = $user instanceof User && $user->tenant_id && ! $user->can('root:manage')
            ? 'tenant:'.$user->tenant_id
            : 'all';

        return self::SHARED_PROPS_CACHE_PREFIX.'v'.$this->sharedPropsVersion().':'.$scope;
    }

    private function sharedPropsVersion(): int
    {
        return (int) Cache::get(self::SHARED_PROPS_VERSION_KEY, 0);
    }
}
