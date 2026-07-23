<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\Institution\LevelEnum;
use App\Enums\Students\ApplicationTrackEnum;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RegistrationLevelOptionsService
{
    public function __construct(
        protected ApplicationEligibilityService $eligibility,
        protected ApplicationFeeService $applicationFeeService,
        protected RegistrationAvailabilityService $registrationAvailability,
    ) {}

    /**
     * @return array{
     *     levels: Collection<int, Level>,
     *     intakePeriods: Collection<int, IntakePeriod>,
     *     requiresIntakeSelection: bool,
     *     openLevelCount: int,
     *     hasActiveIntakes: bool,
     *     availabilityIssue: 'no_open_levels'|'no_active_intakes'|null
     * }
     */
    public function optionsForTrack(ApplicationTrackEnum $track): array
    {
        $levels = Level::query()
            ->where('show_on_current_application_period', 1)
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        if ($track === ApplicationTrackEnum::Continuous) {
            $levels = $this->eligibility->filterLevelsForContinuousTrack($levels);
        }

        $openIntakes = $this->openIntakesForTrack($track);
        $openLevelCount = $levels->count();
        $hasActiveIntakes = $openIntakes->isNotEmpty();

        $availabilityIssue = match (true) {
            $openLevelCount === 0 => 'no_open_levels',
            ! $hasActiveIntakes => 'no_active_intakes',
            default => null,
        };

        return [
            'levels' => $levels,
            'intakePeriods' => $openIntakes,
            'requiresIntakeSelection' => $track !== ApplicationTrackEnum::Continuous && $openIntakes->count() > 1,
            'openLevelCount' => $openLevelCount,
            'hasActiveIntakes' => $hasActiveIntakes,
            'availabilityIssue' => $availabilityIssue,
        ];
    }

    /**
     * @return Collection<int, IntakePeriod>
     */
    public function openIntakesForTrack(ApplicationTrackEnum $track): Collection
    {
        if ($track === ApplicationTrackEnum::Continuous) {
            return collect(array_filter([$this->applicationFeeService->continuousIntakePeriod()]));
        }

        return $this->applicationFeeService->openIntakePeriodsForPortal();
    }

    /**
     * @throws ValidationException
     */
    public function resolveAndValidateSelection(
        ApplicationTrackEnum $track,
        int $levelId,
        ?int $intakePeriodId,
    ): array {
        if (! $this->registrationAvailability->isTrackOpen($track)) {
            throw ValidationException::withMessages([
                'track' => __('trans.application_track_not_open'),
            ]);
        }

        $openIntakes = $this->openIntakesForTrack($track);

        if ($track !== ApplicationTrackEnum::Continuous && $openIntakes->count() > 1 && $intakePeriodId === null) {
            throw ValidationException::withMessages([
                'intake_period_id' => __('validation.required', ['attribute' => 'intake period']),
            ]);
        }

        $level = Level::query()->findOrFail($levelId);

        if ($track === ApplicationTrackEnum::Continuous && ! $this->eligibility->isLevelEligibleForContinuous($level)) {
            throw ValidationException::withMessages([
                'level_id' => __('trans.application_continuous_sdp_or_ojet_required'),
            ]);
        }

        if (! $level->show_on_current_application_period) {
            throw ValidationException::withMessages([
                'level_id' => __('trans.portal_no_levels_available_description'),
            ]);
        }

        if ($intakePeriodId !== null && $openIntakes->firstWhere('id', $intakePeriodId) === null) {
            throw ValidationException::withMessages([
                'intake_period_id' => __('trans.application_track_not_open'),
            ]);
        }

        $intakePeriod = $this->eligibility->resolveIntakeForTrack($track, $intakePeriodId);

        return [
            'level' => $level,
            'intakePeriod' => $intakePeriod,
        ];
    }

    /**
     * @return list<array{value: string, label: string, description: string}>
     */
    public function availableTrackOptions(): array
    {
        return collect($this->eligibility->availableTracks())
            ->map(fn (ApplicationTrackEnum $track) => [
                'value' => $track->value,
                'label' => $track->label(),
                'description' => $track->description(),
            ])
            ->values()
            ->all();
    }

    public function continuousHasSdp(): bool
    {
        $levels = Level::query()
            ->where('show_on_current_application_period', 1)
            ->get();

        return $this->eligibility->filterLevelsForContinuousTrack($levels)
            ->contains(fn (Level $level) => $this->eligibility->isSdpLevel($level));
    }

    public function continuousHasOjet(): bool
    {
        $eligibleIds = $this->eligibility->continuousEligibleLevelIds();
        $sdpLevel = Level::query()->where('name', LevelEnum::SDP->value)->first();
        $sdpId = $sdpLevel?->id;

        foreach ($eligibleIds as $id) {
            if ($sdpId !== null && $id === $sdpId) {
                continue;
            }

            return true;
        }

        return false;
    }
}
