<?php

namespace App\Http\Controllers\Students;

use App\DTO\Shared\{AddressDto, ContactDto, NextOfKinDto};
use App\DTO\Students\CreateApplicationDto;
use App\DTO\Students\ProgramDto;
use App\DTO\Students\StudentProgramDto;
use App\DTO\Users\UserDto;
use App\Enums\Acl\RoleEnum;
use App\Helpers\Helper;
use App\Helpers\PaymentHelper;
use App\Http\Requests\Students\ProgramRequest;
use App\Http\Resources\AuditTrail\AuditTrailResource;
use App\Http\Resources\Enrolments\EnrolmentResource;
use App\Http\Resources\Institution\FeeStructureResource;
use App\Models\Institution\FeeStructure;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Shared\AcademicLevel;
use App\Models\Students\StudentProgram;
use App\Repositories\Students\interface\IStudentProgramRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use App\Enums\Shared\{AcademicLevelEnum, FeeTypeEnum, StatusEnum, TenantEnum};
use App\Helpers\WorkflowHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\{AddressRequest, ContactRequest, NextOfKinRequest};
use App\Http\Requests\Students\CreateApplicationRequest;
use App\Http\Requests\Users\UserRequest;
use App\Http\Resources\Students\{AcademicLevelResource,
    AcademicRecordResource,
    StudentProgramResource,
    StudentResource
};
use App\Jobs\Users\SendVerificationEmailJob;
use App\Models\Shared\Status;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Repositories\Shared\interface\{IAddressRepository, IContactRepository, INextOfKinRepository};
use App\Repositories\Students\interface\IStudentRepository;
use App\Repositories\Users\interface\IUserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};
use Inertia\Inertia;
use Throwable;
use App\Models\Students\Student;

class PortalController extends Controller
{
    public function __construct(
        protected IUserRepository           $userRepository,
        protected IStudentRepository        $studentRepository,
        protected IContactRepository        $contactRepository,
        protected IAddressRepository        $addressRepository,
        protected INextOfKinRepository      $nextOfKinRepository,
        protected IStudentProgramRepository $studentProgramRepository,
    )
    {
    }

    // ========= Dashboard and Registration =========

    /**
     * @throws AuthorizationException
     */
    public function dashboard(): Response
    {
        $this->authorize('viewStudentDashboard');
        return Inertia::render('portal/student/Index', [
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => 0,
        ]);
    }

    public function create(): Response
    {
        $this->logoutIfAuthenticated();
        return Inertia::render('portal/guest/RegistrationUserForm');
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $tenant = Tenant::where('name', TenantEnum::HARARE_POLY->value)->first();
        $status = Status::where('title', StatusEnum::ACTIVE->value)->first();

        $user = $this->userRepository->create(
            UserDto::fromUserRequest($request, $tenant->id, $status->id)
        );

        $user->assignRole(RoleEnum::STUDENT);
        SendVerificationEmailJob::dispatch($user)->withoutDelay();

        Auth::login($user);
        return to_route('portal.confirmation', compact('user'));
    }

    public function registrationConfirmation(User $user): Response
    {
        return Inertia::render('portal/guest/RegistrationConfirmation', [
            'email' => $user->email,
        ]);
    }

    public function registrationFeePaymentOptions(): Response
    {
        $feeType = PaymentHelper::getFeeTypeBySlug(FeeTypeEnum::APPLICATION_FEE->slug());
        $registrationFee = FeeStructure::where('fee_type_id', $feeType->id)->first();
        $registrationFee = FeeStructureResource::make($registrationFee);
        return Inertia::render('portal/application/RegistrationFeePaymentOptions', compact('registrationFee'));
    }

    // ========= Application Workflow =========

    /**
     * @throws AuthorizationException
     */
    public function createApplication(): Response
    {
        $this->authorize('manageStudentPersonalDetails');
        return Inertia::render('portal/application/CreateApplication');
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
            $studentNumber = Helper::generateStudentNumber($student, $application->institutionDepartment);
            $student->update(['student_number' => $studentNumber]);
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
        $allowedLevels = Level::where('allowed_applications_per_level', '>', '1')->pluck('id')->toArray();
        $currentLevels = $student->programs()->get()->map(fn($program) => $program?->department_level_id)->filter()->toArray();
        $currentCourses = $student->programs()->get()->map(fn($program) => $program?->department_course_id)->filter()->toArray();
        $currentDepartments = $student->programs()->get()->map(fn($program) => $program?->institution_department_id)->filter()->toArray();
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

        try {
            [$mainSubjects, $examSittings, $examYears, $otherSubjects, $otherGrades, $otherExamYears, $otherSittings, $modeOfStudyId, $intakePeriodId] = $this->extractRequestFilters();
            $intakePeriod = $this->resolveIntakePeriod($intakePeriodId);
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
    public function applications(): Response
    {
        $this->authorize('manageStudentPersonalDetails');
        $student = $this->getStudent(request());
        $multipleApplicationsLevels = Level::where('allowed_applications_per_level', '>', '1')->pluck('id')->toArray();
        return Inertia::render('portal/student/Applications', [
            'student' => StudentResource::make($student),
            'applications' => EnrolmentResource::collection($student->programs),
            'multipleApplicationsLevelIds' => $multipleApplicationsLevels,
        ]);
    }

    // ========= Student Profile =========

    /**
     * @throws AuthorizationException
     */
    public function personal(): Response
    {
        $this->authorize('manageStudentPersonalDetails');
        return Inertia::render('portal/student/PersonalDetails', [
            'student' => StudentResource::make($this->getStudent(request())),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function programs(): Response
    {
        $this->authorize('manageStudentProgramDetails');
        $student = $this->getStudent(request());

        return Inertia::render('portal/student/Programs', [
            'programs' => StudentProgramResource::collection($student->programs),
        ]);
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
    public function financialRecord(): Response
    {
        $this->authorize('manageStudentFinancialRecords');
        return Inertia::render('portal/student/FinancialRecord');
    }

    // ========= Contact Details =========

    /**
     * @throws AuthorizationException
     */
    public function storeContactDetails(ContactRequest $request): void
    {
        $this->authorize('manageStudentContacts');
        $this->contactRepository->create(
            $this->getStudent(request()),
            ContactDto::fromContactRequest($request)
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function storeAddressDetails(AddressRequest $request): void
    {
        $this->authorize('manageStudentContacts');
        $this->addressRepository->create(
            $this->getStudent(request()),
            AddressDto::fromAddressRequest($request)
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function storeNextOfKinDetails(NextOfKinRequest $request): void
    {
        $this->authorize('manageStudentContacts');
        $this->nextOfKinRepository->create(
            $this->getStudent(request()),
            NextOfKinDto::fromNextOfKinRequest($request)
        );
    }

    // ========= Helpers =========

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

        // 🔹 Delete all existing O-Level results for this student/level
        $student->oLevelResults()->where('academic_level_id', $level->id)->delete();

        // 🔹 Insert main subjects
        if (!empty($mainSubjects) && is_array($mainSubjects)) {
            foreach ($mainSubjects as $subjectId => $gradeId) {
                $examSitting = $examSittings[$subjectId] ?? null;
                $examYear = $examYears[$subjectId] ?? null;

                $student->oLevelResults()->create([
                    'academic_level_id' => $level->id,
                    'subject_id' => $subjectId,
                    'exam_year' => $examYear,
                    'exam_sitting' => $examSitting['value'] ?? null,
                    'grade_id' => $gradeId,
                ]);
            }
        }

        // 🔹 Insert other subjects
        if (!empty($otherSubjects) && is_array($otherSubjects)) {
            foreach ($otherSubjects as $key => $subject) {
                $otherGrade = $otherGrades[$key] ?? null;
                $otherSitting = $otherSittings[$key] ?? null;
                $otherExamYear = $otherExamYears[$key] ?? null;

                $student->oLevelResults()->create([
                    'academic_level_id' => $level->id,
                    'subject_id' => $subject['value'] ?? null,
                    'exam_year' => $otherExamYear,
                    'exam_sitting' => $otherSitting['value'] ?? null,
                    'grade_id' => $otherGrade,
                ]);
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
        $modeOfStudyId = request('mode_of_study_id') > 0 ? (int)request('mode_of_study_id') : null;
        $intakePeriodId = request('intake_period_id') > 0 ? (int)request('intake_period_id') : null;

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

}
