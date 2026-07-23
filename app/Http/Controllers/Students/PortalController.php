<?php

namespace App\Http\Controllers\Students;

use App\Actions\Students\CreateApprenticeApplicantAction;
use App\DTO\Shared\ContactDto;
use App\DTO\Shared\NextOfKinDto;
use App\DTO\Students\CreateApplicationDto;
use App\DTO\Students\ProgramDto;
use App\DTO\Students\StudentApplicationDto;
use App\DTO\Students\UpdateStudentDto;
use App\DTO\Users\UserDto;
use App\Enums\Acl\RoleEnum;
use App\Enums\Institution\LevelEnum;
use App\Enums\Shared\AcademicLevelEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Enums\Shared\StatusEnum;
use App\Enums\Shared\TenantEnum;
use App\Enums\Students\ApplicationTrackEnum;
use App\Helpers\Helper;
use App\Helpers\PaymentHelper;
use App\Helpers\PermissionHelper;
use App\Helpers\WorkflowHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\AddressRequest;
use App\Http\Requests\Shared\ContactRequest;
use App\Http\Requests\Shared\NextOfKinRequest;
use App\Http\Requests\Students\CreateApplicationRequest;
use App\Http\Requests\Students\ProgramRequest;
use App\Http\Requests\Students\StoreApprenticeApplicationRequest;
use App\Http\Requests\Students\UpdateReturningApplicationRequest;
use App\Http\Requests\Students\UpdateStudentRequest;
use App\Http\Requests\Users\UserRequest;
use App\Http\Resources\AuditTrail\AuditTrailResource;
use App\Http\Resources\Enrolments\EnrolmentResource;
use App\Http\Resources\Institution\IntakePeriodResource;
use App\Http\Resources\Institution\LevelResource;
use App\Http\Resources\Students\AcademicLevelResource;
use App\Http\Resources\Students\AcademicRecordResource;
use App\Http\Resources\Students\StudentApplicationResource;
use App\Http\Resources\Students\StudentResource;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Models\Shared\AcademicLevel;
use App\Models\Shared\Status;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Repositories\Shared\interface\IAddressRepository;
use App\Repositories\Shared\interface\IContactRepository;
use App\Repositories\Shared\interface\INextOfKinRepository;
use App\Repositories\Students\interface\IStudentApplicationRepository;
use App\Repositories\Students\interface\IStudentRepository;
use App\Repositories\Users\interface\IUserRepository;
use App\Services\Enrollment\EnrollmentLookupService;
use App\Services\Students\ApplicationEligibilityService;
use App\Services\Students\ApplicationFeeService;
use App\Services\Students\ApplicationTrackSession;
use App\Services\Students\IntakePeriodResolver;
use App\Services\Students\RegistrationAvailabilityService;
use App\Services\Students\RegistrationIntentSession;
use App\Services\Students\RegistrationLevelOptionsService;
use App\Services\Students\RegistrationProgrammeAvailabilityService;
use App\Services\Students\ReturningStudentApplicationPrefillService;
use App\Services\Students\ReturningStudentContextService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PortalController extends Controller
{
    public function __construct(
        protected IUserRepository $userRepository,
        protected IStudentRepository $studentRepository,
        protected IContactRepository $contactRepository,
        protected IAddressRepository $addressRepository,
        protected INextOfKinRepository $nextOfKinRepository,
        protected IStudentApplicationRepository $studentApplicationRepository,
        protected ApplicationFeeService $applicationFeeService,
        protected RegistrationAvailabilityService $registrationAvailability,
        protected ReturningStudentContextService $returningStudentContext,
        protected ReturningStudentApplicationPrefillService $returningApplicationPrefillService,
        protected ApplicationTrackSession $trackSession,
        protected ApplicationEligibilityService $eligibility,
        protected RegistrationIntentSession $intentSession,
        protected RegistrationLevelOptionsService $levelOptionsService,
        protected RegistrationProgrammeAvailabilityService $programmeAvailability,
    ) {}

    // ========= Dashboard and Registration =========

    /**
     * @throws AuthorizationException
     */
    public function dashboard(): Response
    {
        $this->authorize('viewStudentDashboard');
        $student = $this->getStudent(request());
        $student->load([
            'user',
            'idType',
            'latestEnrolment.institutionDepartment.department',
            'latestEnrolment.departmentLevel.level',
            'latestEnrolment.departmentCourse.course',
            'latestEnrolment.modeOfStudy',
            'latestEnrolment.academicCalendar',
            'latestEnrolment.academicYearOption',
            'latestEnrolment.studentEnrolmentStatus',
            'latestApplication.institutionDepartment.department',
            'latestApplication.departmentLevel.level',
            'latestApplication.departmentCourse.course',
            'latestApplication.modeOfStudy',
            'latestApplication.intakePeriod',
            'latestApplication.departmentWorkflowStep.workflowStep',
        ]);

        return Inertia::render('portal/student/Index', [
            'student' => StudentResource::make($student),
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        $this->logoutIfAuthenticated();

        // After guest eligibility, send applicants to the dedicated account step.
        if ($this->intentSession->isReadyForAccount()) {
            return to_route('portal.register.account');
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
            'eligibilityComplete' => false,
            'startAtIdentity' => false,
            'requireEligibilityFirst' => true,
        ]);
    }

    public function store(UserRequest $request, EnrollmentLookupService $enrollmentLookup): RedirectResponse
    {
        $path = $request->string('registration_path')->toString();

        if (! $this->intentSession->isCompleteForAccountCreation()) {
            return to_route('portal.register.track')
                ->withErrors(['track' => __('trans.registration_intent_required')]);
        }

        $track = $this->intentSession->getTrack();

        if ($track === null || ! $this->registrationAvailability->isTrackOpen($track)) {
            return to_route('portal.register.track')
                ->withErrors(['track' => __('trans.application_track_not_open')]);
        }

        // Re-validate level/intake are still open (prevent stale session abuse).
        $levelId = $this->intentSession->levelId();
        $intakePeriodId = $this->intentSession->intakePeriodId();

        if ($levelId === null) {
            return to_route('portal.register.level');
        }

        try {
            $this->levelOptionsService->resolveAndValidateSelection($track, $levelId, $intakePeriodId);
        } catch (ValidationException $e) {
            $this->intentSession->clearLevelAndBelow();

            return to_route('portal.register.level')->withErrors($e->errors());
        }

        if (! $this->programmeAvailability->hasAvailableProgrammes(
            $track,
            $levelId,
            $this->intentSession->continuousFocus(),
        )) {
            return to_route('portal.register.programme')
                ->withErrors(['department_id' => __('trans.registration_programme_none_available', [
                    'level' => Level::query()->whereKey($levelId)->value('name') ?? '',
                ])]);
        }

        if ($path === 'zimbabwean' && $enrollmentLookup->nationalIdExists($request->string('id_number')->toString())) {
            return back()->withErrors([
                'id_number' => __('trans.id_number_already_taken'),
            ])->withInput();
        }

        if ($path === 'international' && $enrollmentLookup->passportExists($request->string('passport_number')->toString())) {
            return back()->withErrors([
                'passport_number' => __('trans.passport_number_already_taken'),
            ])->withInput();
        }

        $tenant = Tenant::where('name', TenantEnum::HARARE_POLY->value)->first();
        $status = Status::where('title', StatusEnum::ACTIVE->value)->first();

        $user = $this->userRepository->create(
            UserDto::fromUserRequest($request, $tenant->id, $status->id)
        );

        $user->assignRole(RoleEnum::STUDENT);
        $user->givePermissionTo(PermissionHelper::portalPermissions());
        $user->email_verified_at = now();
        $user->registration_instructions_acknowledged_at = now();
        $user->save();

        $registrationSession = [
            'registration.path' => $path,
            'registration.id_type_id' => $path === 'international'
                ? IdTypeEnum::FOREIGN_PASSPORT_NUMBER->id()
                : IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id(),
        ];

        if ($path === 'zimbabwean') {
            $registrationSession['registration.id_number'] = EnrollmentLookupService::normalizeNationalId(
                $request->string('id_number')->toString()
            );
        } else {
            $registrationSession['registration.passport_number'] = EnrollmentLookupService::normalizePassportNumber(
                $request->string('passport_number')->toString()
            );
        }

        session($registrationSession);

        $this->intentSession->promoteToApplicationSession($this->trackSession);

        Auth::login($user);

        return $this->redirectAfterAccountCreation($track, $user);
    }

    public function registrationConfirmation(User $user): Response
    {
        return Inertia::render('portal/guest/RegistrationConfirmation', [
            'email' => $user->email,
        ]);
    }

    // ========= Application Workflow =========

    /**
     * @throws AuthorizationException
     */
    public function createApplication(): Response
    {
        $this->authorize('manageStudentPersonalDetails');
        $user = request()->user();
        $applicationFee = $this->applicationFeeService->activeApplicationFee($user);
        $intakePeriod = $applicationFee?->intakePeriod
            ?? ($this->trackSession->intakePeriodId()
                ? IntakePeriod::query()->find($this->trackSession->intakePeriodId())
                : null)
            ?? Helper::resolveIntakePeriod();
        $levelsWithPayment = PaymentHelper::levelsWithApplicationFee();

        return Inertia::render('portal/application/CreateApplication', [
            'hasPaidApplicationFee' => PaymentHelper::hasPaidApplicationFeeAndNotApplied($user, $intakePeriod),
            'levelsWithPayment' => LevelResource::collection($levelsWithPayment),
            'applicationStep' => $applicationFee !== null && $applicationFee->isPaid() ? 'apply' : 'account',
            'intakeName' => $intakePeriod->name,
            'selectedLevelId' => session('application.level_id'),
            'selectedLevelName' => $this->resolveSelectedLevelName(),
            'applicationTrack' => $this->trackSession->get()?->value,
            'applicationTrackLabel' => $this->trackSession->get()?->label(),
            'registrationPrefill' => [
                'id_number' => session('registration.id_number'),
                'passport_number' => session('registration.passport_number'),
                'id_type_id' => session('registration.id_type_id'),
                'path' => session('registration.path'),
            ],
            'programmePrefill' => $this->resolveProgrammePrefill(),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function chooseTrack(): Response|RedirectResponse
    {
        $this->authorize('manageStudentPersonalDetails');

        if (! $this->registrationAvailability->isAnyRegistrationOpen()) {
            return to_route('portal.registration.maintenance');
        }

        $tracks = collect($this->eligibility->availableTracks())->map(fn (ApplicationTrackEnum $track) => [
            'value' => $track->value,
            'label' => $track->label(),
            'description' => $track->description(),
        ]);

        if ($tracks->count() === 1) {
            $only = ApplicationTrackEnum::from($tracks->first()['value']);
            $this->trackSession->set($only);
            $this->bindIntakeForTrack($only);

            return $this->redirectForTrack($only);
        }

        return Inertia::render('portal/application/SelectApplicationTrack', [
            'tracks' => $tracks,
            'currentTrack' => $this->trackSession->get()?->value,
            'currentContinuousFocus' => session('application.continuous_focus'),
            'continuousHasSdp' => $this->levelOptionsService->continuousHasSdp(),
            'continuousHasOjet' => $this->levelOptionsService->continuousHasOjet(),
            'applicationStep' => 'track',
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function selectTrack(Request $request): RedirectResponse
    {
        $this->authorize('manageStudentPersonalDetails');

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

        $this->trackSession->set($track);
        $this->bindIntakeForTrack($track);

        if ($track === ApplicationTrackEnum::Continuous) {
            session(['application.continuous_focus' => $data['continuous_focus'] ?? null]);
        } else {
            session()->forget('application.continuous_focus');
        }

        return $this->redirectForTrack($track);
    }

    /**
     * @throws AuthorizationException
     */
    public function apprenticeApplication(): Response|RedirectResponse
    {
        $this->authorize('manageStudentPersonalDetails');

        $track = $this->trackSession->get();
        if ($track !== ApplicationTrackEnum::Apprentice) {
            return to_route('portal.application.track');
        }

        if (! $this->registrationAvailability->isApprenticeRegistrationOpen()) {
            return to_route('portal.application.track');
        }

        return Inertia::render('portal/application/ApprenticeExpressApplication', [
            'applicationStep' => 'apprentice',
            'applicationTrack' => $track->value,
            'applicationTrackLabel' => $track->label(),
        ]);
    }

    /**
     * @throws AuthorizationException|Throwable
     */
    public function storeApprenticeApplication(
        StoreApprenticeApplicationRequest $request,
        CreateApprenticeApplicantAction $action,
    ): RedirectResponse {
        $this->authorize('manageStudentPersonalDetails');

        DB::beginTransaction();
        try {
            $action->execute(
                $request->user(),
                $request->string('employer')->toString(),
                $request->string('apprentice_number')->toString(),
            );
            DB::commit();
            $this->trackSession->clear();

            return to_route('portal.applications')
                ->with('success', __('trans.application_apprentice_submitted_success'));
        } catch (Throwable $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => 'An error occurred while submitting your apprentice details. Please try again.',
            ]);
        }
    }

    /**
     * @throws AuthorizationException
     */
    public function levelOptions(): Response|RedirectResponse
    {
        $this->authorizeApplicationLevelSelection();
        $track = $this->trackSession->require();

        if ($track === ApplicationTrackEnum::Apprentice) {
            return to_route('portal.application.apprentice');
        }

        $options = $this->levelOptionsService->optionsForTrack($track);

        return Inertia::render('portal/application/SelectLevelOption', [
            'levels' => LevelResource::collection($options['levels']),
            'intakePeriods' => IntakePeriodResource::collection($options['intakePeriods']),
            'requiresIntakeSelection' => $options['requiresIntakeSelection'],
            'applicationStep' => 'level',
            'openLevelCount' => $options['openLevelCount'],
            'hasActiveIntakes' => $options['hasActiveIntakes'],
            'availabilityIssue' => $options['availabilityIssue'],
            'applicationTrack' => $track->value,
            'applicationTrackLabel' => $track->label(),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function selectLevel(Request $request): RedirectResponse
    {
        $this->authorize('manageStudentPersonalDetails');
        $track = $this->trackSession->require();

        $openIntakes = $track === ApplicationTrackEnum::Continuous
            ? collect(array_filter([$this->applicationFeeService->continuousIntakePeriod()]))
            : $this->applicationFeeService->openIntakePeriodsForPortal();

        $rules = [
            'level_id' => ['required', 'exists:levels,id'],
            'intake_period_id' => ['nullable', 'integer', 'exists:intake_periods,id'],
        ];

        if ($track !== ApplicationTrackEnum::Continuous && $openIntakes->count() > 1) {
            $rules['intake_period_id'] = ['required', 'integer', 'exists:intake_periods,id'];
        }

        $data = $request->validate($rules);
        $level = Level::findOrFail($data['level_id']);

        if ($track === ApplicationTrackEnum::Continuous && ! $this->eligibility->isLevelEligibleForContinuous($level)) {
            throw ValidationException::withMessages([
                'level_id' => __('trans.application_continuous_sdp_or_ojet_required'),
            ]);
        }

        $intakePeriod = $this->eligibility->resolveIntakeForTrack(
            $track,
            isset($data['intake_period_id']) ? (int) $data['intake_period_id'] : null
        );

        $this->trackSession->setLevel($level->id);
        $this->trackSession->setIntakePeriodId($intakePeriod->id);
        session(['application.level_id' => $level->id]);

        if ($this->eligibility->trackRequiresApplicationFee($track, $level, $request->user())) {
            $this->applicationFeeService->ensureForFeeRequiredLevel($request->user(), $level, $intakePeriod);

            return to_route('portal.application.fee-payment');
        }

        $this->applicationFeeService->abandonUnpaidApplicationFee($request->user());

        return to_route('portal.application.create');
    }

    public function confirmApplication(): Response
    {
        $this->authorize('manageStudentPersonalDetails');

        return Inertia::render('portal/application/ConfirmApplication');
    }

    /**
     * @throws Throwable
     */
    public function storeApplication(CreateApplicationRequest $request): RedirectResponse
    {
        $this->authorize('manageStudentPersonalDetails');

        $user = request()->user();

        DB::beginTransaction();
        try {
            $this->updateUserNamesIfChanged($user, $request);

            $track = $this->trackSession->require();
            $intakePeriod = $this->applicationFeeService->resolveIntakeForApplicationSubmit(
                $user,
                $track,
                $request->filled('intake_period_id') ? $request->integer('intake_period_id') : null
            );

            if ($intakePeriod->is_continuous && ! $track->usesContinuousIntake()) {
                $intakePeriod = $this->eligibility->resolveIntakeForTrack($track, null);
            }

            $student = $this->studentRepository->create(
                CreateApplicationDto::fromCreateApplicationRequest($request, $user, $intakePeriod)
            );
            $application = $student->applications()->latest()->first();
            $stepOne = WorkflowHelper::getDepartmentApplicationStepByPosition($application->institution_department_id, 1);
            $stepTwo = WorkflowHelper::getDepartmentApplicationStepByPosition($application->institution_department_id, 2);
            $application->update(['department_application_step_id' => $stepOne?->id ?? null]);
            // update payment status of registration fee to 'paid'
            PaymentHelper::updateRegistrationFeeLedgerEntries($application);

            DB::commit();
            $this->trackSession->clear();
            if ($stepTwo) {
                $application->update([
                    'department_application_step_id' => $stepTwo->id,
                ]);
            }

            return to_route('portal.applications');
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Portal application submit failed', [
                'user_id' => $user?->id,
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            return back()->withErrors([
                'error' => 'An error occurred while submitting your application. Please try again.',
            ]);
        }
    }

    /**
     * @throws AuthorizationException
     */
    public function viewApplication(StudentApplication $studentApplication): Response
    {
        $this->authorize('manageStudentPersonalDetails');
        $application = StudentApplicationResource::make($studentApplication);
        $student = StudentResource::make($this->getStudent(request()));
        $audit = AuditTrailResource::collection($studentApplication->activities);

        return Inertia::render('portal/student/ApplicationTrack', compact('application', 'student', 'audit'));
    }

    /**
     * @throws AuthorizationException
     */
    public function editApplication(StudentApplication $studentApplication): Response
    {
        $this->authorize('manageStudentPersonalDetails');
        $this->assertOwnsStudentApplication($studentApplication);
        $application = EnrolmentResource::make($studentApplication);

        return Inertia::render('portal/application/EditProgram', compact('application'));
    }

    /**
     * @throws AuthorizationException
     */
    public function createProgram(Student $student): Response
    {
        $this->authorize('manageStudentPersonalDetails');
        $this->assertOwnsStudent($student);
        $oLevelResults = AcademicLevelResource::collection($student?->oLevelResults);
        $allowedLevels = []; // Level::where('allowed_applications_per_level', '>', '1')->pluck('id')->toArray();
        $currentLevels = $student->applications()->get()->map(fn ($program) => $program?->department_level_id)->filter()->toArray();
        $currentCourses = $student->applications()->get()->map(fn ($program) => $program?->department_course_id)->filter()->toArray();
        $currentDepartments = $student->applications()->get()->map(fn ($program) => $program?->institution_department_id)->filter()->toArray();

        return Inertia::render('portal/application/AddProgram',
            compact('student', 'oLevelResults', 'allowedLevels', 'currentLevels', 'currentDepartments', 'currentCourses'));
    }

    /**
     * @throws Throwable
     */
    public function storeProgram(Student $student, ProgramRequest $request): RedirectResponse
    {
        $this->authorize('manageStudentPersonalDetails');
        $this->assertOwnsStudent($student);

        DB::beginTransaction();
        [$mainSubjects, $examSittings, $examYears, $otherSubjects, $otherGrades, $otherExamYears, $otherSittings, $modeOfStudyId, $intakePeriodId] = $this->extractRequestFilters();
        $intakePeriod = $this->resolveIntakePeriod($intakePeriodId);
        $programMax = Str::lower($student->currentLevel()) === Str::lower(LevelEnum::NC->name()) ? 3 : 1;
        $programCount = $student->applications()->where('intake_period_id', $intakePeriod->id)->count();

        if ($programCount == $programMax) {
            return redirect()->route('portal.applications.errors', __("You have reached the maximum number of applications allowed {$programMax}."));
        }

        $user = $request->user();
        $applicationFee = $this->applicationFeeService->activeApplicationFee($user);
        $feeLevel = $applicationFee?->level;

        if ($feeLevel !== null && PaymentHelper::levelRequiresApplicationFeePayment($feeLevel, $user)) {
            if ($applicationFee === null || ! PaymentHelper::hasPaidApplicationFeeAndNotApplied($user, $intakePeriod)) {
                return back()->withErrors([
                    'level_id' => __('trans.application_fee_payment_required'),
                ]);
            }
        }

        try {
            $programDto = new StudentApplicationDto(
                student_id: $student->id,
                mode_of_study_id: $request->mode_of_study_id,
                institution_department_id: $request->department_id,
                department_level_id: $request->level_id,
                department_course_id: $request->course_id,
                intake_period_id: $intakePeriod->id,
                required_level_completed: $request->has('required_level_completed') ? $request->required_level_completed : null,
                read_write_acknowledged: $request->has('read_write_acknowledged') ? $request->read_write_acknowledged : null,
            );
            $program = $this->studentApplicationRepository->create($programDto);
            $stepTwo = WorkflowHelper::getDepartmentApplicationStepByPosition($program->institution_department_id, 2);
            if ($stepTwo) {
                $program->update([
                    'department_application_step_id' => $stepTwo->id,
                ]);
            }
            $filters = $this->extractRequestFilters();

            if (collect($filters)->flatten()->filter()->isNotEmpty()) {
                $this->updateAcademicResults();
            }

            PaymentHelper::updateRegistrationFeeLedgerEntries($program, $user, $intakePeriod);

            DB::commit();

            return to_route('portal.applications');
        } catch (Throwable $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => 'An error occurred while creating program. Please try again.',
            ]);
        }
    }

    /**
     * @throws Throwable
     */
    public function updateApplication(StudentApplication $studentApplication, ProgramRequest $request): RedirectResponse
    {
        $this->authorize('manageStudentPersonalDetails');
        $this->assertOwnsStudentApplication($studentApplication);

        DB::beginTransaction();
        try {
            $this->studentApplicationRepository->update($studentApplication, ProgramDto::fromProgramRequest($request));
            $filters = $this->extractRequestFilters();

            if (collect($filters)->flatten()->filter()->isNotEmpty()) {
                $this->updateAcademicResults();
            }

            DB::commit();

            return to_route('portal.applications');
        } catch (Throwable $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => 'An error occurred while updating your application. Please try again.',
            ]);
        }
    }

    /**
     * @throws AuthorizationException
     */
    public function applications(): RedirectResponse
    {
        return redirect()->route('portal.profile.applications');
    }

    // ========= Student Profile =========

    /**
     * @throws AuthorizationException
     */
    public function profilePersonalInformation(): Response
    {
        return $this->renderProfileSection('basic_info', 'manageStudentPersonalDetails');
    }

    /**
     * @throws AuthorizationException
     */
    public function updatePersonalDetails(UpdateStudentRequest $request): void
    {
        $this->authorize('manageStudentPersonalDetails');

        $student = $this->getStudent($request);

        $this->studentRepository->update($student, UpdateStudentDto::fromUpdateStudentRequest($request));
    }

    /**
     * @throws AuthorizationException
     */
    public function profilePrograms(): Response
    {
        return $this->renderProfileSection('programs', 'manageStudentApplicationDetails');
    }

    /**
     * @throws AuthorizationException
     */
    public function profileApplications(): Response
    {
        $this->authorize('manageStudentPersonalDetails');
        $student = $this->profileStudent();
        $user = request()->user();
        $openIntakes = $this->returningStudentContext->openIntakes();
        $offerLetterIntakePeriodIds = app(IntakePeriodResolver::class)->offerLetterIntakePeriodIds();

        return Inertia::render('portal/student/profile/Section', [
            'student' => StudentResource::make($student),
            'activeTab' => 'applications',
            'activeIntakePeriodIds' => $openIntakes->pluck('id')->values()->all(),
            'offerLetterIntakePeriodIds' => $offerLetterIntakePeriodIds,
            'applicationHub' => $this->returningStudentContext->applicationHubFor($student, $user),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function profileFinancials(): Response
    {
        return $this->renderProfileSection('financials', 'manageStudentFinancialRecords');
    }

    /**
     * @throws AuthorizationException
     */
    public function profileAccommodations(): Response
    {
        return $this->renderProfileSection('accommodations', 'manageStudentAccommodationDetails');
    }

    /**
     * @throws AuthorizationException
     */
    public function profileDocuments(): Response
    {
        return $this->renderProfileSection('documents', 'manageStudentPersonalDetails');
    }

    /**
     * @throws AuthorizationException
     */
    public function profileAuthentication(): Response
    {
        return $this->renderProfileSection('authentication', 'manageStudentPersonalDetails');
    }

    /**
     * @throws AuthorizationException
     */
    public function personal(): RedirectResponse
    {
        return redirect()->route('portal.profile.personal-information');
    }

    /**
     * @throws AuthorizationException
     */
    public function programs(): RedirectResponse
    {
        return redirect()->route('portal.profile.programs');
    }

    /**
     * @throws AuthorizationException
     */
    public function academicRecord(): Response
    {
        $this->authorize('manageStudentAcademicRecords');
        $student = $this->getStudent(request());

        return Inertia::render('portal/student/AcademicRecord', [
            'academicRecord' => AcademicRecordResource::collection($student->academicRecord),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function financialRecord(): RedirectResponse
    {
        return redirect()->route('portal.profile.financials');
    }

    // ========= Contact Details =========

    /**
     * @throws AuthorizationException
     */
    public function storeContactDetails(ContactRequest $request): void
    {
        $this->authorize('manageStudentContacts');
        $this->contactRepository->create($this->getStudent(request()), ContactDto::fromContactRequest($request));
    }

    /**
     * @throws AuthorizationException
     */
    public function storeAddressDetails(AddressRequest $request): void
    {
        $this->authorize('manageStudentContacts');
        $this->addressRepository->create($this->getStudent(request()), AddressDto::fromAddressRequest($request));
    }

    /**
     * @throws AuthorizationException
     */
    public function storeNextOfKinDetails(NextOfKinRequest $request): void
    {
        $this->authorize('manageStudentContacts');
        $this->nextOfKinRepository->create($this->getStudent(request()), NextOfKinDto::fromNextOfKinRequest($request));
    }

    // ========= Helpers =========

    private function resolveSelectedLevelName(): ?string
    {
        $levelId = session('application.level_id');

        if (! $levelId) {
            $applicationFee = $this->applicationFeeService->activeApplicationFee(request()->user());
            $levelId = $applicationFee?->level_id;
        }

        return $levelId ? Level::query()->find($levelId)?->name : null;
    }

    /**
     * @throws AuthorizationException
     */
    private function renderProfileSection(string $activeTab, string $ability): Response
    {
        $this->authorize($ability);

        return Inertia::render('portal/student/profile/Section', [
            'student' => StudentResource::make($this->profileStudent()),
            'activeTab' => $activeTab,
        ]);
    }

    private function profileStudent(): Student
    {
        $student = $this->getStudent(request());
        $student->load([
            'user',
            'title',
            'gender',
            'maritalStatus',
            'race',
            'idType',
            'country',
            'religion',
            'latestEnrolment.institutionDepartment.department',
            'latestEnrolment.departmentLevel.level',
            'latestEnrolment.departmentCourse.course',
            'latestEnrolment.modeOfStudy',
            'latestEnrolment.academicCalendar',
            'latestEnrolment.academicYearOption',
            'latestEnrolment.studentEnrolmentStatus',
            'latestApplication.institutionDepartment.department',
            'latestApplication.departmentLevel.level',
            'latestApplication.departmentCourse.course',
            'latestApplication.modeOfStudy',
            'latestApplication.intakePeriod',
            'latestApplication.departmentWorkflowStep.workflowStep',
            'contacts',
            'addresses',
            'nextOfKins',
        ]);

        return $student;
    }

    private function getStudent(Request $request)
    {
        return $request->user()->studentProfile;
    }

    private function updateUserNamesIfChanged(User $user, Request $request): void
    {
        $attributes = [
            'first_name' => $request->input('first_name', $user->first_name),
            'last_name' => $request->input('last_name', $user->last_name),
        ];

        if ($request->filled('middle_name')) {
            $attributes['middle_name'] = $request->string('middle_name')->toString();
        }

        $user->fill($attributes);

        if ($user->isDirty(array_keys($attributes))) {
            $user->save();
            Auth::login($user);
        }
    }

    private function logoutIfAuthenticated(): void
    {
        if (Auth::check()) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }
    }

    private function updateAcademicResults(): void
    {
        $student = $this->getStudent(request());
        [
            $mainSubjects,
            $examSittings,
            $examYears,
            $otherSubjects,
            $otherGrades,
            $otherExamYears,
            $otherSittings,
        ] = $this->extractRequestFilters();

        $level = AcademicLevel::where('name', AcademicLevelEnum::SECONDARY_SCHOOL->value)->first();

        // 🔹 Handle main subjects
        if (! empty($mainSubjects) && is_array($mainSubjects)) {
            foreach ($mainSubjects as $subjectId => $gradeId) {
                $examSitting = $examSittings[$subjectId]['value'] ?? null;
                $examYear = $examYears[$subjectId] ?? null;

                $student->oLevelResults()->updateOrCreate(
                    [
                        'academic_level_id' => $level->id,
                        'subject_id' => $subjectId,
                    ],
                    [
                        'exam_year' => $examYear,
                        'exam_sitting' => $examSitting,
                        'grade_id' => $gradeId,
                    ]
                );
            }
        }

        // 🔹 Handle other subjects
        if (! empty($otherSubjects) && is_array($otherSubjects)) {
            foreach ($otherSubjects as $key => $subject) {
                $subjectId = $subject['value'] ?? null;
                $otherGrade = $otherGrades[$key] ?? null;
                $otherSitting = $otherSittings[$key]['value'] ?? null;
                $otherExamYear = $otherExamYears[$key] ?? null;

                if ($subjectId) {
                    $student->oLevelResults()->updateOrCreate(
                        [
                            'academic_level_id' => $level->id,
                            'subject_id' => $subjectId,
                        ],
                        [
                            'exam_year' => $otherExamYear,
                            'exam_sitting' => $otherSitting,
                            'grade_id' => $otherGrade,
                        ]
                    );
                }
            }
        }
    }

    private function extractRequestFilters(): array
    {
        $mainSubjects = request()->has('o_level_subject_ids') ? request('o_level_subject_ids') : null;
        $examSittings = request()->has('o_level_sittings') ? request('o_level_sittings') : null;
        $examYears = request()->has('o_level_years') ? request('o_level_years') : null;
        $otherSubjects = request()->has('o_level_other_subject_ids') ? request('o_level_other_subject_ids') : null;
        $otherGrades = request()->has('o_level_other_grade_ids') ? request('o_level_other_grade_ids') : null;
        $otherExamYears = request()->has('o_level_other_years') ? request('o_level_other_years') : null;
        $otherSittings = request()->has('o_level_other_sittings') ? request('o_level_other_sittings') : null;
        $modeOfStudyId = request('mode_of_study_id') > 0 ? (int) request('mode_of_study_id') : null;
        $intakePeriodId = request('intake_period_id') > 0 ? (int) request('intake_period_id') : null;

        return [
            $mainSubjects,
            $examSittings,
            $examYears,
            $otherSubjects,
            $otherGrades,
            $otherExamYears,
            $otherSittings,
            $modeOfStudyId,
            $intakePeriodId,
        ];
    }

    private function resolveIntakePeriod(?int $intakePeriodId)
    {
        return $intakePeriodId
            ? IntakePeriod::find($intakePeriodId)
            : IntakePeriod::orderByDesc('end_date')->first();
    }

    /**
     * @throws AuthorizationException
     */
    private function authorizeApplicationLevelSelection(): void
    {
        $user = request()->user();

        if ($user === null) {
            abort(403);
        }

        if ($user->can('manageOwnStudentPersonalDetails:students')) {
            return;
        }

        if (! $user->has_student_profile && $this->userHasStudentRole($user)) {
            return;
        }

        $this->authorize('manageStudentPersonalDetails');
    }

    private function userHasStudentRole(User $user): bool
    {
        return $user->hasRole(RoleEnum::STUDENT->value)
            || $user->hasRole(RoleEnum::STUDENT->name());
    }

    private function assertOwnsStudentApplication(StudentApplication $studentApplication): void
    {
        $studentProfile = auth()->user()?->studentProfile;

        if ($studentProfile === null || $studentApplication->student_id !== $studentProfile->id) {
            abort(403);
        }
    }

    private function assertOwnsStudent(Student $student): void
    {
        $studentProfile = auth()->user()?->studentProfile;

        if ($studentProfile === null || $student->id !== $studentProfile->id) {
            abort(403);
        }
    }

    public function registrationMaintenance(): Response|RedirectResponse
    {
        if ($this->registrationAvailability->isRegularRegistrationOpen()) {
            return to_route('login');
        }

        $intakePeriod = $this->registrationAvailability->currentRegularIntakePeriod();
        $reason = $this->registrationAvailability->blockReason();
        $continuousOpen = $this->registrationAvailability->isContinuousRegistrationOpen();

        if (($reason === null || $intakePeriod === null) && ! $continuousOpen) {
            return to_route('login');
        }

        return Inertia::render('portal/registration/RegistrationMaintenance', [
            'status' => $reason?->value,
            'message' => $reason && $intakePeriod
                ? $reason->maintenanceMessage($intakePeriod->name)
                : __('trans.registration_maintenance_closed', ['intake' => $intakePeriod?->name ?? '']),
            'intakeName' => $intakePeriod?->name,
            'continuousOpen' => $continuousOpen,
            'continuousApplyUrl' => $continuousOpen ? route('portal.register.track') : null,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveProgrammePrefill(): ?array
    {
        $departmentId = session('application.department_id');
        $departmentLevelId = session('application.department_level_id');
        $courseId = session('application.course_id');
        $modeOfStudyId = session('application.mode_of_study_id');

        if (! $departmentId || ! $departmentLevelId || ! $courseId || ! $modeOfStudyId) {
            return null;
        }

        $institutionDepartment = InstitutionDepartment::query()
            ->with('department')
            ->find($departmentId);
        $departmentLevel = DepartmentLevel::query()
            ->with('level')
            ->find($departmentLevelId);
        $departmentCourse = DepartmentCourse::query()
            ->with('course')
            ->find($courseId);
        $modeOfStudy = ModeOfStudy::query()->find($modeOfStudyId);

        return [
            'department_id' => (int) $departmentId,
            'department_label' => $institutionDepartment?->department?->name
                ?? $institutionDepartment?->department_code
                ?? null,
            'department_level_id' => (int) $departmentLevelId,
            'department_level_label' => $departmentLevel?->level?->name,
            'level_relationship_one_value' => $departmentLevel?->level_id,
            'course_id' => (int) $courseId,
            'course_label' => $departmentCourse?->course?->name,
            'mode_of_study_id' => (int) $modeOfStudyId,
            'mode_of_study_label' => $modeOfStudy?->name,
        ];
    }

    private function redirectAfterAccountCreation(ApplicationTrackEnum $track, User $user): RedirectResponse
    {
        if ($track === ApplicationTrackEnum::Apprentice) {
            $this->intentSession->clear();

            return to_route('portal.application.apprentice');
        }

        $levelId = $this->trackSession->levelId();
        $level = $levelId !== null ? Level::query()->find($levelId) : null;

        if ($level !== null && $this->eligibility->trackRequiresApplicationFee($track, $level, $user)) {
            $intakePeriodId = $this->trackSession->intakePeriodId();
            $intakePeriod = $intakePeriodId !== null
                ? IntakePeriod::query()->findOrFail($intakePeriodId)
                : $this->eligibility->resolveIntakeForTrack($track, null);

            $this->applicationFeeService->ensureForFeeRequiredLevel($user, $level, $intakePeriod);
            $this->intentSession->clear();

            return to_route('portal.application.fee-payment');
        }

        $this->applicationFeeService->abandonUnpaidApplicationFee($user);
        $this->intentSession->clear();

        return to_route('portal.application.create');
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
            $summary['intakeName'] = IntakePeriod::query()
                ->whereKey($summary['intakePeriodId'])
                ->value('name');
        } else {
            $summary['intakeName'] = null;
        }

        return $summary;
    }

    private function bindIntakeForTrack(ApplicationTrackEnum $track): void
    {
        $intake = $this->eligibility->resolveIntakeForTrack($track);
        $this->trackSession->setIntakePeriodId($intake->id);
    }

    private function redirectForTrack(ApplicationTrackEnum $track): RedirectResponse
    {
        if ($track === ApplicationTrackEnum::Apprentice) {
            return to_route('portal.application.apprentice');
        }

        return to_route('portal.application.level-options');
    }

    public function errors(string $message): Response
    {
        return Inertia::render('portal/student/ApplicationError', compact('message'));
    }

    /**
     * @throws AuthorizationException
     */
    public function acknowledgeApplicationHub(Request $request): RedirectResponse
    {
        $this->authorize('manageStudentPersonalDetails');
        $student = $this->getStudent($request);
        $openIntakes = $this->returningStudentContext->openIntakes();

        $data = $request->validate([
            'intake_period_id' => ['required', 'integer', 'exists:intake_periods,id'],
            'acknowledged' => ['accepted'],
        ]);

        $intakePeriod = $this->applicationFeeService->assertPortalIntakePeriod((int) $data['intake_period_id']);

        if (! $openIntakes->contains('id', $intakePeriod->id)) {
            throw ValidationException::withMessages([
                'intake_period_id' => [__('trans.portal_intake_period_invalid')],
            ]);
        }

        $this->returningStudentContext->persistAcknowledgement($student, 'reapply', $intakePeriod);

        return to_route('portal.profile.applications');
    }

    /**
     * @throws AuthorizationException
     */
    public function profileApplicationLevelOptions(): Response|RedirectResponse
    {
        $this->authorize('manageStudentPersonalDetails');
        $student = $this->getStudent(request());
        $openIntakes = $this->returningStudentContext->openIntakes();

        $hasAcknowledgement = $openIntakes->contains(
            fn (IntakePeriod $intake): bool => $this->returningStudentContext->hasReapplyAcknowledgementForIntake($student, $intake)
        );

        if (! $hasAcknowledgement) {
            return to_route('portal.profile.applications');
        }

        $levels = Level::query()
            ->where('show_on_current_application_period', 1)
            ->orderBy('position')
            ->orderBy('name')
            ->get();
        $openLevelCount = $levels->count();
        $hasActiveIntakes = $openIntakes->isNotEmpty();
        $availabilityIssue = match (true) {
            $openLevelCount === 0 => 'no_open_levels',
            ! $hasActiveIntakes => 'no_active_intakes',
            default => null,
        };

        return Inertia::render('portal/application/SelectLevelOption', [
            'levels' => LevelResource::collection($levels),
            'intakePeriods' => IntakePeriodResource::collection($openIntakes),
            'requiresIntakeSelection' => $openIntakes->count() > 1,
            'applicationStep' => 'level',
            'openLevelCount' => $openLevelCount,
            'hasActiveIntakes' => $hasActiveIntakes,
            'availabilityIssue' => $availabilityIssue,
            'selectLevelRoute' => 'portal.profile.applications.select-level',
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function selectApplicationLevel(Request $request): RedirectResponse
    {
        $this->authorize('manageStudentPersonalDetails');
        $student = $this->getStudent($request);
        $openIntakes = $this->returningStudentContext->openIntakes();

        $rules = [
            'level_id' => ['required', 'exists:levels,id'],
            'intake_period_id' => ['nullable', 'integer', 'exists:intake_periods,id'],
        ];

        if ($openIntakes->count() > 1) {
            $rules['intake_period_id'] = ['required', 'integer', 'exists:intake_periods,id'];
        }

        $data = $request->validate($rules);
        $level = Level::query()->findOrFail($data['level_id']);

        $intakePeriod = $openIntakes->count() > 1
            ? $this->applicationFeeService->resolvePortalIntakePeriod((int) $data['intake_period_id'])
            : ($openIntakes->first() ?? $this->applicationFeeService->resolvePortalIntakePeriod());

        if (! $this->returningStudentContext->hasReapplyAcknowledgementForIntake($student, $intakePeriod)) {
            return to_route('portal.profile.applications');
        }

        if (PaymentHelper::levelRequiresApplicationFeePayment($level, $request->user())) {
            $this->applicationFeeService->ensureForFeeRequiredLevel($request->user(), $level, $intakePeriod);

            return to_route('portal.application.fee-payment');
        }

        $this->applicationFeeService->abandonUnpaidApplicationFee($request->user());
        session(['application.level_id' => $level->id]);

        return to_route('portal.application.returning');
    }

    /**
     * @throws AuthorizationException
     */
    public function returningApplication(): Response|RedirectResponse
    {
        $this->authorize('manageStudentPersonalDetails');
        $student = $this->getStudent(request());
        $user = request()->user();
        $applicationFee = $this->applicationFeeService->activeApplicationFee($user);
        $intakePeriod = $applicationFee?->intakePeriod ?? $this->returningStudentContext->openIntakes()->first();

        if ($intakePeriod === null || ! $this->returningStudentContext->hasReapplyAcknowledgementForIntake($student, $intakePeriod)) {
            return to_route('portal.profile.applications');
        }

        $level = $applicationFee?->level ?? Level::query()->find(session('application.level_id'));
        if (
            $level !== null
            && PaymentHelper::levelRequiresApplicationFeePayment($level, $user)
            && ! PaymentHelper::hasPaidApplicationFeeAndNotApplied($user, $intakePeriod)
        ) {
            return to_route('portal.application.fee-payment');
        }

        return Inertia::render('portal/application/ReturningApplication', [
            'returningPrefill' => $this->returningApplicationPrefillService->build($student),
            'studentId' => $student->id,
            'targetIntake' => IntakePeriodResource::make($intakePeriod),
            'hasPaidApplicationFee' => PaymentHelper::hasPaidApplicationFeeAndNotApplied($user, $intakePeriod),
            'levelsWithPayment' => LevelResource::collection(PaymentHelper::levelsWithApplicationFee()),
            'selectedLevelId' => $applicationFee?->level_id ?? session('application.level_id'),
            'selectedLevelName' => $level?->name,
        ]);
    }

    /**
     * @throws AuthorizationException|Throwable
     */
    public function storeReturningApplication(UpdateReturningApplicationRequest $request): RedirectResponse
    {
        $this->authorize('manageStudentPersonalDetails');
        $student = $this->getStudent($request);
        $user = $request->user();

        $intakePeriod = $this->applicationFeeService->resolveIntakeForSubmit(
            $user,
            $request->filled('intake_period_id') ? $request->integer('intake_period_id') : null,
        );

        if (! $this->returningStudentContext->hasReapplyAcknowledgementForIntake($student, $intakePeriod)) {
            return to_route('portal.profile.applications');
        }

        DB::beginTransaction();
        try {
            $dto = CreateApplicationDto::fromReturningApplicationRequest($request, $user, $intakePeriod);
            $application = $this->studentRepository->applyReturningApplication($student, $dto);

            $stepOne = WorkflowHelper::getDepartmentApplicationStepByPosition($application->institution_department_id, 1);
            $stepTwo = WorkflowHelper::getDepartmentApplicationStepByPosition($application->institution_department_id, 2);
            $application->update(['department_application_step_id' => $stepOne?->id ?? null]);

            PaymentHelper::updateRegistrationFeeLedgerEntries($application);

            DB::commit();

            if ($stepTwo) {
                $application->update(['department_application_step_id' => $stepTwo->id]);
            }

            return to_route('portal.profile.applications');
        } catch (Throwable $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => __('trans.returning_student_application_submit_failed'),
            ]);
        }
    }

    /**
     * @throws AuthorizationException
     */
    public function confirmReturningApplication(): Response|RedirectResponse
    {
        $this->authorize('manageStudentPersonalDetails');
        $student = $this->getStudent(request());
        $user = request()->user();
        $applicationFee = $this->applicationFeeService->activeApplicationFee($user);
        $intakePeriod = $applicationFee?->intakePeriod ?? $this->returningStudentContext->openIntakes()->first();

        if ($intakePeriod === null || ! $this->returningStudentContext->hasReapplyAcknowledgementForIntake($student, $intakePeriod)) {
            return to_route('portal.profile.applications');
        }

        return Inertia::render('portal/application/ConfirmReturningApplication', [
            'targetIntake' => IntakePeriodResource::make($intakePeriod),
        ]);
    }
}
