<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\Institution\LevelEnum;
use App\Enums\Institution\ModeOfStudyEnum;
use App\Enums\Students\ApplicationTrackEnum;
use App\Helpers\PaymentHelper;
use App\Models\Institution\CourseLevelMode;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Models\Users\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ApplicationEligibilityService
{
    public function __construct(
        protected RegistrationAvailabilityService $registrationAvailability,
        protected ApplicationFeeService $applicationFeeService,
        protected ApplicationTrackSession $trackSession,
    ) {}

    public function isContinuousEligible(?Level $level, ?ModeOfStudy $mode): bool
    {
        if ($level !== null && $this->isSdpLevel($level)) {
            return true;
        }

        return $mode !== null && $this->isOjetMode($mode);
    }

    public function isSdpLevel(Level $level): bool
    {
        return $level->name === LevelEnum::SDP->value
            || strcasecmp((string) $level->name, LevelEnum::SDP->value) === 0;
    }

    public function isOjetMode(ModeOfStudy $mode): bool
    {
        return $mode->name === ModeOfStudyEnum::OJET->value
            || strcasecmp((string) $mode->name, ModeOfStudyEnum::OJET->label()) === 0;
    }

    /**
     * @return list<int>
     */
    public function continuousEligibleLevelIds(): array
    {
        $ids = [];

        $sdpLevel = Level::query()->where('name', LevelEnum::SDP->value)->first();
        if ($sdpLevel !== null) {
            $ids[] = $sdpLevel->id;
        }

        $ojetModeId = ModeOfStudy::query()->where('name', ModeOfStudyEnum::OJET->value)->value('id');
        if ($ojetModeId === null) {
            return $ids;
        }

        $departmentLevelIds = CourseLevelMode::query()
            ->get()
            ->filter(fn (CourseLevelMode $courseLevelMode) => in_array((int) $ojetModeId, array_map('intval', $courseLevelMode->modes ?? []), true))
            ->pluck('department_level_id')
            ->unique()
            ->values();

        if ($departmentLevelIds->isEmpty()) {
            return $ids;
        }

        $ojetLevelIds = DepartmentLevel::query()
            ->whereIn('id', $departmentLevelIds)
            ->pluck('level_id')
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique([...$ids, ...$ojetLevelIds]));
    }

    /**
     * @param  Collection<int, Level>  $levels
     * @return Collection<int, Level>
     */
    public function filterLevelsForContinuousTrack(Collection $levels): Collection
    {
        $eligibleIds = $this->continuousEligibleLevelIds();

        return $levels->filter(fn (Level $level) => in_array($level->id, $eligibleIds, true))->values();
    }

    public function isLevelEligibleForContinuous(Level $level): bool
    {
        return in_array($level->id, $this->continuousEligibleLevelIds(), true);
    }

    public function trackRequiresApplicationFee(ApplicationTrackEnum $track, Level $level, ?User $user = null): bool
    {
        if ($track->skipsApplicationFee()) {
            return false;
        }

        return PaymentHelper::levelRequiresApplicationFeePayment($level, $user);
    }

    public function resolveIntakeForTrack(ApplicationTrackEnum $track, ?int $requestedIntakePeriodId = null): IntakePeriod
    {
        if ($track->usesContinuousIntake()) {
            return $this->applicationFeeService->resolveContinuousIntakePeriod();
        }

        return $this->applicationFeeService->resolvePortalIntakePeriod($requestedIntakePeriodId);
    }

    /**
     * @throws ValidationException
     */
    public function assertTrackAllowsSubmit(
        ApplicationTrackEnum $track,
        Level $institutionLevel,
        ModeOfStudy $mode,
        IntakePeriod $intakePeriod,
    ): void {
        if (! $this->registrationAvailability->isTrackOpen($track)) {
            throw ValidationException::withMessages([
                'level_id' => __('trans.application_track_not_open'),
            ]);
        }

        if ($track === ApplicationTrackEnum::Continuous) {
            if (! $intakePeriod->is_continuous) {
                throw ValidationException::withMessages([
                    'intake_period_id' => __('trans.application_continuous_intake_required'),
                ]);
            }

            if (! $this->isContinuousEligible($institutionLevel, $mode)) {
                throw ValidationException::withMessages([
                    'mode_of_study_id' => __('trans.application_continuous_sdp_or_ojet_required'),
                ]);
            }

            return;
        }

        if ($intakePeriod->is_continuous) {
            throw ValidationException::withMessages([
                'intake_period_id' => __('trans.application_regular_intake_required'),
            ]);
        }
    }

    public function availableTracks(): array
    {
        $tracks = [];

        if ($this->registrationAvailability->isRegularRegistrationOpen()) {
            $tracks[] = ApplicationTrackEnum::Regular;
        }

        if ($this->registrationAvailability->isContinuousRegistrationOpen()) {
            $tracks[] = ApplicationTrackEnum::Continuous;
        }

        if ($this->registrationAvailability->isApprenticeRegistrationOpen()) {
            $tracks[] = ApplicationTrackEnum::Apprentice;
        }

        return $tracks;
    }
}
