<?php

declare(strict_types=1);

namespace App\Http\Controllers\Students;

use App\Enums\Institution\LevelEnum;
use App\Enums\Students\ApplicationTrackEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Institution\IntakePeriodResource;
use App\Http\Resources\Institution\LevelResource;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Services\Students\ApplicationEligibilityService;
use App\Services\Students\ApplicationFeeService;
use App\Services\Students\IntakePeriodOrderingService;
use App\Services\Students\RegistrationAvailabilityService;
use App\Services\Students\RegistrationIntentSession;
use App\Services\Students\RegistrationLevelOptionsService;
use App\Services\Students\RegistrationProgrammeAvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class GuestRegistrationController extends Controller
{
    public function __construct(
        protected RegistrationIntentSession $intentSession,
        protected RegistrationLevelOptionsService $levelOptionsService,
        protected RegistrationProgrammeAvailabilityService $programmeAvailability,
        protected RegistrationAvailabilityService $registrationAvailability,
        protected ApplicationEligibilityService $eligibility,
        protected ApplicationFeeService $applicationFeeService,
        protected IntakePeriodOrderingService $intakeOrdering,
    ) {}

    public function chooseTrack(): Response|RedirectResponse
    {
        if (! $this->registrationAvailability->isAnyRegistrationOpen()) {
            return to_route('portal.registration.maintenance');
        }

        $tracks = $this->levelOptionsService->availableTrackOptions();

        // Auto-advance only on first visit (no path chosen yet). Never skip Continuous
        // (needs SDP/OJET focus), and never skip when reviewing a prior selection.
        if (
            count($tracks) === 1
            && $this->intentSession->getTrack() === null
        ) {
            $only = ApplicationTrackEnum::from($tracks[0]['value']);

            if ($only !== ApplicationTrackEnum::Continuous) {
                $this->intentSession->setTrack($only);
                $this->intentSession->setContinuousFocus(null);
                $this->bindIntakeForTrack($only);

                return $this->redirectAfterTrack($only, null);
            }
        }

        return Inertia::render('portal/guest/SelectRegistrationTrack', [
            'tracks' => $tracks,
            'currentTrack' => $this->intentSession->getTrack()?->value,
            'currentContinuousFocus' => $this->intentSession->continuousFocus(),
            'continuousHasSdp' => $this->levelOptionsService->continuousHasSdp(),
            'continuousHasOjet' => $this->levelOptionsService->continuousHasOjet(),
            'intentSummary' => $this->intentSummaryWithLabels(),
            'stepperVariant' => $this->intentSession->stepperVariant(),
            'requiresFee' => $this->intentSession->requiresFee(),
            'applicationStep' => 'track',
        ]);
    }

    public function selectTrack(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'track' => ['required', 'string', 'in:'.implode(',', array_column(ApplicationTrackEnum::cases(), 'value'))],
            'continuous_focus' => ['nullable', 'string', 'in:sdp,ojet'],
        ]);

        $track = ApplicationTrackEnum::from($data['track']);

        if (! $this->registrationAvailability->isTrackOpen($track)) {
            throw ValidationException::withMessages([
                'track' => __('trans.application_track_not_open'),
            ]);
        }

        $this->intentSession->setTrack($track);
        $this->intentSession->clearLevelAndBelow();

        $focus = $track === ApplicationTrackEnum::Continuous
            ? ($data['continuous_focus'] ?? null)
            : null;

        if ($track === ApplicationTrackEnum::Continuous && $focus === null) {
            throw ValidationException::withMessages([
                'continuous_focus' => __('validation.required', ['attribute' => 'programme']),
            ]);
        }

        $this->intentSession->setContinuousFocus($focus);
        $this->bindIntakeForTrack($track);

        return $this->redirectAfterTrack($track, $focus);
    }

    public function levelOptions(): Response|RedirectResponse
    {
        $track = $this->intentSession->getTrack();

        if ($track === null) {
            return to_route('portal.register.track');
        }

        // SDP express already auto-bound level — skip to programme.
        if ($track === ApplicationTrackEnum::Continuous
            && $this->intentSession->continuousFocus() === 'sdp'
            && $this->intentSession->levelId() !== null
        ) {
            return to_route('portal.register.programme');
        }

        $options = $this->levelOptionsService->optionsForTrack($track);

        // OJET: only show continuous-eligible levels (non-SDP focus still uses continuous filter).
        if ($track === ApplicationTrackEnum::Continuous && $this->intentSession->continuousFocus() === 'ojet') {
            $options['levels'] = $options['levels']
                ->reject(fn (Level $level) => $this->eligibility->isSdpLevel($level))
                ->values();
            $options['openLevelCount'] = $options['levels']->count();
            $options['availabilityIssue'] = $options['openLevelCount'] === 0
                ? 'no_open_levels'
                : ($options['hasActiveIntakes'] ? null : 'no_active_intakes');
        }

        return Inertia::render('portal/guest/SelectRegistrationLevel', [
            'levels' => LevelResource::collection($options['levels']),
            'intakePeriods' => IntakePeriodResource::collection($options['intakePeriods']),
            'requiresIntakeSelection' => $options['requiresIntakeSelection'],
            'openLevelCount' => $options['openLevelCount'],
            'hasActiveIntakes' => $options['hasActiveIntakes'],
            'availabilityIssue' => $options['availabilityIssue'],
            'applicationTrack' => $track->value,
            'applicationTrackLabel' => $track->label(),
            'continuousFocus' => $this->intentSession->continuousFocus(),
            'intentSummary' => $this->intentSummaryWithLabels(),
            'stepperVariant' => $this->intentSession->stepperVariant(),
            'requiresFee' => $this->intentSession->requiresFee(),
            'applicationStep' => 'level',
            'selectLevelRoute' => 'portal.register.select-level',
        ]);
    }

    public function selectLevel(Request $request): RedirectResponse
    {
        $track = $this->intentSession->getTrack();

        if ($track === null) {
            return to_route('portal.register.track');
        }

        $openIntakes = $this->levelOptionsService->openIntakesForTrack($track);

        $rules = [
            'level_id' => ['required', 'exists:levels,id'],
            'intake_period_id' => ['nullable', 'integer', 'exists:intake_periods,id'],
        ];

        if ($track !== ApplicationTrackEnum::Continuous && $openIntakes->count() > 1) {
            $rules['intake_period_id'] = ['required', 'integer', 'exists:intake_periods,id'];
        }

        $data = $request->validate($rules);

        $resolved = $this->levelOptionsService->resolveAndValidateSelection(
            $track,
            (int) $data['level_id'],
            isset($data['intake_period_id']) ? (int) $data['intake_period_id'] : null,
        );

        $this->intentSession->setLevel($resolved['level']->id);
        $this->intentSession->setIntakePeriodId($resolved['intakePeriod']->id);
        $this->intentSession->clearProgramme();
        $this->syncRequiresFee($track, $resolved['level']);

        return to_route('portal.register.programme');
    }

    public function programmeOptions(): Response|RedirectResponse
    {
        $track = $this->intentSession->getTrack();

        if ($track === null) {
            return to_route('portal.register.track');
        }

        $levelId = $this->intentSession->levelId();

        if ($levelId === null) {
            if ($track === ApplicationTrackEnum::Continuous && $this->intentSession->continuousFocus() === 'sdp') {
                return to_route('portal.register.track');
            }

            return to_route('portal.register.level');
        }

        $tree = $this->programmeAvailability->programmeTree(
            $track,
            $levelId,
            $this->intentSession->continuousFocus(),
        );

        $level = Level::query()->find($levelId);

        return Inertia::render('portal/guest/RegistrationProgrammeFinder', [
            'programmes' => $tree,
            'applicationTrack' => $track->value,
            'applicationTrackLabel' => $track->label(),
            'continuousFocus' => $this->intentSession->continuousFocus(),
            'selectedLevelId' => $levelId,
            'selectedLevelName' => $level?->name,
            'intentSummary' => $this->intentSummaryWithLabels(),
            'stepperVariant' => $this->intentSession->stepperVariant(),
            'requiresFee' => $this->intentSession->requiresFee(),
            'applicationStep' => 'programme',
            'currentSelection' => [
                'departmentId' => $this->intentSession->departmentId(),
                'departmentLevelId' => $this->intentSession->departmentLevelId(),
                'courseId' => $this->intentSession->courseId(),
                'modeOfStudyId' => $this->intentSession->modeOfStudyId(),
            ],
        ]);
    }

    public function selectProgramme(Request $request): RedirectResponse
    {
        $track = $this->intentSession->getTrack();

        if ($track === null) {
            return to_route('portal.register.track');
        }

        $levelId = $this->intentSession->levelId();

        if ($levelId === null) {
            return to_route('portal.register.level');
        }

        $data = $request->validate([
            'department_id' => ['required', 'integer'],
            'department_level_id' => ['required', 'integer'],
            'course_id' => ['required', 'integer'],
            'mode_of_study_id' => ['required', 'integer'],
        ]);

        $this->programmeAvailability->assertProgrammeSelection(
            $track,
            $levelId,
            (int) $data['department_id'],
            (int) $data['department_level_id'],
            (int) $data['course_id'],
            (int) $data['mode_of_study_id'],
            $this->intentSession->continuousFocus(),
        );

        $this->intentSession->setProgramme(
            (int) $data['department_id'],
            (int) $data['department_level_id'],
            (int) $data['course_id'],
            (int) $data['mode_of_study_id'],
        );
        $this->intentSession->markReadyForAccount();

        return to_route('portal.register.account');
    }

    public function account(): Response|RedirectResponse
    {
        if (! $this->intentSession->isCompleteForAccountCreation()) {
            if (! $this->intentSession->hasTrack()) {
                return to_route('portal.register.track');
            }

            if (! $this->intentSession->hasLevelSelection()) {
                $focus = $this->intentSession->continuousFocus();
                if ($this->intentSession->getTrack() === ApplicationTrackEnum::Continuous && $focus === 'sdp') {
                    return to_route('portal.register.track');
                }

                return to_route('portal.register.level');
            }

            if (! $this->intentSession->hasProgrammeSelection()) {
                return to_route('portal.register.programme');
            }
        }

        $openIntakes = $this->applicationFeeService->openIntakePeriodsForPortal();

        return Inertia::render('portal/guest/RegistrationUserForm', [
            'openIntakePeriods' => IntakePeriodResource::collection($openIntakes),
            'singleIntakeName' => $openIntakes->count() === 1 ? $openIntakes->first()->name : null,
            'openIntakeNames' => $openIntakes->count() > 1
                ? $openIntakes->pluck('name')->join(', ')
                : null,
            'intentSummary' => $this->intentSummaryWithLabels(),
            'stepperVariant' => $this->intentSession->stepperVariant(),
            'requiresFee' => $this->intentSession->requiresFee(),
            'eligibilityComplete' => true,
            'startAtIdentity' => true,
            'requireEligibilityFirst' => false,
        ]);
    }

    private function bindIntakeForTrack(ApplicationTrackEnum $track): void
    {
        try {
            $intake = $this->eligibility->resolveIntakeForTrack($track);
            $this->intentSession->setIntakePeriodId($intake->id);
        } catch (\Throwable) {
            // Intake may be resolved later on level selection for regular track.
        }
    }

    private function redirectAfterTrack(ApplicationTrackEnum $track, ?string $focus): RedirectResponse
    {
        if ($track === ApplicationTrackEnum::Apprentice) {
            return to_route('portal.register.level');
        }

        if ($track === ApplicationTrackEnum::Continuous && $focus === 'sdp') {
            $sdpLevel = Level::query()->where('name', LevelEnum::SDP->value)->first();

            if ($sdpLevel === null || ! $sdpLevel->show_on_current_application_period) {
                throw ValidationException::withMessages([
                    'track' => __('trans.portal_no_levels_available_description'),
                ]);
            }

            $this->intentSession->setLevel($sdpLevel->id);
            $this->bindIntakeForTrack($track);
            $this->syncRequiresFee($track, $sdpLevel);

            return to_route('portal.register.programme');
        }

        // OJET + Regular → level selection
        return to_route('portal.register.level');
    }

    private function syncRequiresFee(ApplicationTrackEnum $track, Level $level): void
    {
        $this->intentSession->setRequiresFee(
            $this->eligibility->trackRequiresApplicationFee($track, $level)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function intentSummaryWithLabels(): array
    {
        $summary = $this->intentSession->summary();

        if ($summary['levelId'] !== null) {
            $summary['levelName'] = Level::query()->whereKey($summary['levelId'])->value('name');
        } else {
            $summary['levelName'] = null;
        }

        if ($summary['intakePeriodId'] !== null) {
            $intake = IntakePeriod::query()->find($summary['intakePeriodId']);
            $summary['intakeName'] = $intake !== null
                ? $this->intakeOrdering->displayName($intake)
                : null;
        } else {
            $summary['intakeName'] = null;
        }

        return $summary;
    }
}
