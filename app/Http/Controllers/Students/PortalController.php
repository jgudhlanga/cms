<?php

namespace App\Http\Controllers\Students;

use App\DTO\Shared\AddressDto;
use App\DTO\Shared\ContactDto;
use App\DTO\Shared\NextOfKinDto;
use App\DTO\Students\CreateApplicationDto;
use App\DTO\Students\ProgramDto;
use App\DTO\Students\StudentProgramDto;
use App\DTO\Users\UserDto;
use App\Enums\Acl\RoleEnum;
use App\Enums\Institution\LevelEnum;
use App\Enums\Shared\AcademicLevelEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Enums\Shared\StatusEnum;
use App\Enums\Shared\TenantEnum;
use App\Helpers\Helper;
use App\Helpers\PaymentHelper;
use App\Helpers\WorkflowHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\AddressRequest;
use App\Http\Requests\Shared\ContactRequest;
use App\Http\Requests\Shared\NextOfKinRequest;
use App\Http\Requests\Students\CreateApplicationRequest;
use App\Http\Requests\Students\ProgramRequest;
use App\Http\Requests\Users\UserRequest;
use App\Http\Resources\AuditTrail\AuditTrailResource;
use App\Http\Resources\Enrolments\EnrolmentResource;
use App\Http\Resources\Institution\LevelResource;
use App\Http\Resources\Students\AcademicLevelResource;
use App\Http\Resources\Students\AcademicRecordResource;
use App\Http\Resources\Students\StudentProgramResource;
use App\Http\Resources\Students\StudentResource;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Shared\AcademicLevel;
use App\Models\Shared\Status;
use App\Models\Students\Student;
use App\Models\Students\StudentProgram;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Repositories\Shared\interface\IAddressRepository;
use App\Repositories\Shared\interface\IContactRepository;
use App\Repositories\Shared\interface\INextOfKinRepository;
use App\Repositories\Students\interface\IStudentProgramRepository;
use App\Repositories\Students\interface\IStudentRepository;
use App\Repositories\Users\interface\IUserRepository;
use App\Services\Enrollment\EnrollmentLookupService;
use App\Services\HMS\StudentAccommodationFeeService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
        protected IStudentProgramRepository $studentProgramRepository,
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
            'latestEnrolment.institutionDepartment.department',
            'latestEnrolment.departmentLevel.level',
            'latestEnrolment.departmentCourse.course',
            'latestEnrolment.modeOfStudy',
            'latestEnrolment.academicCalendar',
            'latestEnrolment.academicYearOption',
            'latestEnrolment.studentEnrolmentStatus',
        ]);

        return Inertia::render('portal/student/Index', [
            'student' => StudentResource::make($student),
        ]);
    }

    public function create(): Response
    {
        $this->logoutIfAuthenticated();

        return Inertia::render('portal/guest/RegistrationUserForm');
    }

    public function store(UserRequest $request, EnrollmentLookupService $enrollmentLookup): RedirectResponse
    {
        $path = $request->string('registration_path')->toString();

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
        $user->email_verified_at = now();
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

        Auth::login($user);

        return to_route('portal.application.level-options');
    }

    public function registrationConfirmation(User $user): Response
    {
        return Inertia::render('portal/guest/RegistrationConfirmation', [
            'email' => $user->email,
        ]);
    }

    public function registrationFeePaymentOptions(): Response
    {
        $registrationFee = PaymentHelper::getFeeStructureResourceBySlug(FeeTypeEnum::APPLICATION_FEE->slug());

        return Inertia::render('portal/application/RegistrationFeePaymentOptions', compact('registrationFee'));
    }

    // ========= Application Workflow =========

    /**
     * @throws AuthorizationException
     */
    public function createApplication(): Response
    {
        $this->authorize('manageStudentPersonalDetails');
        session()->forget('application');
        // get levels which requires application fee payment
        $levelsWithPayment = PaymentHelper::levelsWithApplicationFee();
        // check if user / student has paid application fee
        $intakePeriod = Helper::resolveIntakePeriod();
        $hasPaidApplicationFeeRecord = PaymentHelper::getLatestLedgerRecord(FeeTypeEnum::APPLICATION_FEE->slug(), 'receipt', request()->user(), $intakePeriod);

        return Inertia::render('portal/application/CreateApplication', [
            'hasPaidApplicationFee' => (bool) $hasPaidApplicationFeeRecord,
            'levelsWithPayment' => LevelResource::collection($levelsWithPayment),
            'registrationPrefill' => [
                'id_number' => session('registration.id_number'),
                'passport_number' => session('registration.passport_number'),
                'id_type_id' => session('registration.id_type_id'),
                'path' => session('registration.path'),
            ],
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function levelOptions(): Response
    {
        $this->authorize('manageStudentPersonalDetails');
        // the levels on the offer
        $levels = Level::where('show_on_current_application_period', 1)->orderBy('position')->orderBy('name')->get();

        return Inertia::render('portal/application/SelectLevelOption', [
            'levels' => LevelResource::collection($levels),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function selectLevel(Request $request): RedirectResponse
    {
        $this->authorize('manageStudentPersonalDetails');
        $data = $request->validate(['level_id' => ['required', 'exists:levels,id']]);
        // Store in session
        session(['application.level_id' => $data['level_id']]);
        $level = Level::find($data['level_id']);
        // Decide where to go
        if ($level->has_application_fee_payment) {
            return to_route('portal.application.fee-payment');
        }

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

            // get the current intake period
            $intakePeriodId = $request->has('intake_period_id') && $request->intake_period_id > 0 ? $request->intake_period_id : null;
            $intakePeriod = $intakePeriodId ? IntakePeriod::find($intakePeriodId) : IntakePeriod::orderBy('end_date', 'DESC')->first();
            $student = $this->studentRepository->create(
                CreateApplicationDto::fromCreateApplicationRequest($request, $user, $intakePeriod)
            );
            $application = $student->programs()->latest()->first();
            $stepOne = WorkflowHelper::getDepartmentApplicationStepByPosition($application->institution_department_id, 1);
            $stepTwo = WorkflowHelper::getDepartmentApplicationStepByPosition($application->institution_department_id, 2);
            $application->update(['department_application_step_id' => $stepOne?->id ?? null]);
            // update payment status of registration fee to 'paid'
            PaymentHelper::updateRegistrationFeeLedgerEntries($application);
            // generate student number
            // $studentNumber = Helper::generateStudentNumber($student, $application->institutionDepartment);
            // $student->update(['student_number' => $studentNumber]);
            DB::commit();
            if ($stepTwo) {
                $application->update([
                    'department_application_step_id' => $stepTwo->id,
                ]);
            }

            return to_route('portal.applications');
        } catch (Throwable $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => 'An error occurred while submitting your application. Please try again.',
            ]);
        }
    }

    /**
     * @throws AuthorizationException
     */
    public function viewApplication(StudentProgram $studentProgram): Response
    {
        $this->authorize('manageStudentPersonalDetails');
        $application = StudentProgramResource::make($studentProgram);
        $student = StudentResource::make($this->getStudent(request()));
        $audit = AuditTrailResource::collection($studentProgram->activities);

        return Inertia::render('portal/student/ApplicationTrack', compact('application', 'student', 'audit'));
    }

    /**
     * @throws AuthorizationException
     */
    public function editApplication(StudentProgram $studentProgram): Response
    {
        $this->authorize('manageStudentPersonalDetails');
        $application = EnrolmentResource::make($studentProgram);

        return Inertia::render('portal/application/EditProgram', compact('application'));
    }

    /**
     * @throws AuthorizationException
     */
    public function createProgram(Student $student): Response
    {
        $this->authorize('manageStudentPersonalDetails');
        $oLevelResults = AcademicLevelResource::collection($student?->oLevelResults);
        $allowedLevels = []; // Level::where('allowed_applications_per_level', '>', '1')->pluck('id')->toArray();
        $currentLevels = $student->programs()->get()->map(fn ($program) => $program?->department_level_id)->filter()->toArray();
        $currentCourses = $student->programs()->get()->map(fn ($program) => $program?->department_course_id)->filter()->toArray();
        $currentDepartments = $student->programs()->get()->map(fn ($program) => $program?->institution_department_id)->filter()->toArray();

        return Inertia::render('portal/application/AddProgram',
            compact('student', 'oLevelResults', 'allowedLevels', 'currentLevels', 'currentDepartments', 'currentCourses'));
    }

    /**
     * @throws Throwable
     */
    public function storeProgram(Student $student, ProgramRequest $request): RedirectResponse
    {
        $this->authorize('manageStudentPersonalDetails');

        DB::beginTransaction();
        [$mainSubjects, $examSittings, $examYears, $otherSubjects, $otherGrades, $otherExamYears, $otherSittings, $modeOfStudyId, $intakePeriodId] = $this->extractRequestFilters();
        $intakePeriod = $this->resolveIntakePeriod($intakePeriodId);
        $programMax = Str::lower($student->currentLevel()) === Str::lower(LevelEnum::NC->name()) ? 3 : 1;
        $programCount = $student->programs()->where('intake_period_id', $intakePeriod->id)->count();

        if ($programCount == $programMax) {
            return redirect()->route('portal.applications.errors', __("You have reached the maximum number of applications allowed {$programMax}."));
        }
        try {
            $programDto = new StudentProgramDto(
                student_id: $student->id,
                mode_of_study_id: $request->mode_of_study_id,
                institution_department_id: $request->department_id,
                department_level_id: $request->level_id,
                department_course_id: $request->course_id,
                intake_period_id: $intakePeriod->id,
                required_level_completed: $request->has('required_level_completed') ? $request->required_level_completed : null,
                read_write_acknowledged: $request->has('read_write_acknowledged') ? $request->read_write_acknowledged : null,
            );
            $program = $this->studentProgramRepository->create($programDto);
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
    public function updateApplication(StudentProgram $studentProgram, ProgramRequest $request): RedirectResponse
    {
        $this->authorize('manageStudentPersonalDetails');

        DB::beginTransaction();
        try {
            $this->studentProgramRepository->update($studentProgram, ProgramDto::fromProgramRequest($request));
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
    public function profilePrograms(): Response
    {
        return $this->renderProfileSection('programs', 'manageStudentProgramDetails');
    }

    /**
     * @throws AuthorizationException
     */
    public function profileApplications(): Response
    {
        return $this->renderProfileSection('applications', 'manageStudentPersonalDetails');
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
    public function accommodationFeePaymentOptions(StudentAccommodationFeeService $feeService): Response
    {
        $this->authorize('manageStudentAccommodationDetails');

        $student = $this->getStudent(request());
        $fees = $feeService->summaryForStudent($student);
        $accommodationFee = PaymentHelper::getFeeStructureResourceBySlug(FeeTypeEnum::STUDENT_ACCOMMODATION_FEE->slug());
        return Inertia::render('portal/student/profile/AccommodationFeePaymentOptions', [
            'fees' => $fees,
            'accommodationFee' => $accommodationFee,
        ]);
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
        $user->fill($request->only(['first_name', 'middle_name', 'last_name']));

        if ($user->isDirty(['first_name', 'middle_name', 'last_name'])) {
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

    public function errors(string $message): Response
    {
        return Inertia::render('portal/student/ApplicationError', compact('message'));
    }
}
