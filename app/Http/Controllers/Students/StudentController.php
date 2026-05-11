<?php

namespace App\Http\Controllers\Students;

use App\DTO\Students\CreateStudentApplicationDto;
use App\DTO\Students\UpdateStudentDto;
use App\DTO\Users\UserDto;
use App\Enums\Acl\RoleEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Helpers\Helper;
use App\Helpers\PaymentHelper;
use App\Http\Controllers\Controller;
use App\Http\Filters\Students\StudentFilter;
use App\Http\Requests\Students\CreateStudentApplicationRequest;
use App\Http\Requests\Students\UpdateStudentRequest;
use App\Http\Resources\Students\StudentResource;
use App\Http\Resources\Users\UserResource;
use App\Models\Institution\FeeStructure;
use App\Models\Institution\Level;
use App\Models\Ledgers\Ledger;
use App\Models\Students\Student;
use App\Models\Users\User;
use App\Repositories\Shared\interface\IAddressRepository;
use App\Repositories\Shared\interface\IContactRepository;
use App\Repositories\Shared\interface\INextOfKinRepository;
use App\Repositories\Students\interface\IStudentProgramRepository;
use App\Repositories\Students\interface\IStudentRepository;
use App\Repositories\Users\interface\IUserRepository;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class StudentController extends Controller
{
    public function __construct(
        protected IStudentRepository        $repository,
        protected IUserRepository           $userRepository,
        protected IContactRepository        $contactRepository,
        protected IAddressRepository        $addressRepository,
        protected INextOfKinRepository      $nextOfKinRepository,
        protected IStudentProgramRepository $studentProgramRepository,
    )
    {
    }

    /**
     * @throws AuthorizationException
     */
    public function index(StudentFilter $filters): Response
    {
        $this->authorize('viewAny', Student::class);
        $students = StudentResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('students/Index', [
            'students' => $students,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }


    public function create()
    {
        //
    }

    public function createProfile(string $paymentMode)
    {
        $this->authorize('create', Student::class);
        return Inertia::render('enrolments/Create', compact('paymentMode'));
    }

    public function enrolmentLookup()
    {
        return Inertia::render('enrolments/EnrolmentLookup');
    }


    /**
     * @throws Throwable
     */
    public function store(CreateStudentApplicationRequest $request)
    {
        $this->authorize('create', Student::class);

        DB::beginTransaction();
        try {
            $tenant = Helper::getTenant();
            $status = Helper::getActiveStatus();

            $user = $this->createUser($request, $tenant->id, $status->id);
            $student = $this->createStudentApplication($request, $user->id);
            $program = $student->programs()->latest()->first();
            Helper::initializeProgramWorkflow($program);
            Helper::generateAndAssignStudentNumber($student, $program);
            // invoice student
            $this->invoiceStudent($user, $request, $program);

            DB::commit();

            return to_route('enrolments.show-profile', $student->id)
                ->with('success', 'Enrolment profile created successfully.');
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withErrors([
                'error' => 'An error occurred while submitting your profile. Please try again.',
            ]);
        }
    }


    public function showProfile(Student $student)
    {
        $student = StudentResource::make($student);
        return Inertia::render('enrolments/Show', compact('student'));
    }

    public function show(Student $student)
    {
        $this->authorize('view', $student);
        $user = UserResource::make($student->user);
        $student = StudentResource::make($student);
        return Inertia::render('students/Show', compact('user', 'student'));
    }


    public function edit(string $id)
    {
        //
    }


    public function update(UpdateStudentRequest $request, Student $student): void
    {
        $this->repository->update($student, UpdateStudentDto::fromUpdateStudentRequest($request));
    }

    public function destroy(string $id)
    {
        //
    }

    public function searchProfile()
    {
        [$search] = $this->extractRequestFilters();

        if (blank($search)) {
            return $this->returnSearchResponse(
                message: 'Please provide a search value.',
                status: 422
            );
        }

        $user = $this->findUserBySearch($search);

        if (!$user) {
            return $this->returnSearchResponse(
                user: null,
                message: 'No matching record found.',
                status: 404
            );
        }

        $hasPaidApplicationFee = PaymentHelper::hasPaidApplicationFee($user);
        $student = $user->studentProfile;
        $hasAdminRole = !$user->isStudent();

        $currentLevel = null;
        $currentProgramCount = null;
        $eligibleForEnrolment = true;

        if ($student instanceof Student) {
            [$currentLevel, $currentProgramCount, $eligibleForEnrolment] =
                $this->evaluateStudentEligibility($student, $hasPaidApplicationFee);
        }

        return $this->returnSearchResponse(
            user: UserResource::make($user),
            hasPaidApplicationFee: $hasPaidApplicationFee,
            eligibleForEnrolment: $eligibleForEnrolment,
            currentLevel: $currentLevel,
            currentProgramCount: $currentProgramCount,
            message: 'User Account found.',
            hasAdminRole: $hasAdminRole,
            studentId: $student?->id,
        );
    }

    /**
     * Attempt to find a user using various strategies.
     */
    private function findUserBySearch(string $search): ?User
    {
        // 1. Search by email
        $user = User::where('email', $search)->first();
        if ($user) {
            return $user;
        }

        // 2. Search by student identifiers
        $student = Student::where('id_number', $search)
            ->orWhere('student_number', $search)
            ->orWhere('passport_number', $search)
            ->first();

        $user = $student?->user;
        if ($user) {
            return $user;
        }

        // 3. Search by ledger references
        $ledger = Ledger::withTrashed()
            ->where('system_reference', $search)
            ->orWhere('payment_reference', $search)
            ->first();

        $user = $ledger?->ledgerable()->first();
        if ($user) {
            return $user;
        }

        return null;
    }


    private function extractRequestFilters(): array
    {
        $search = request()->has('search') ? request('search') : null;

        return [
            $search,
        ];
    }

    protected function createUser(CreateStudentApplicationRequest $request, int $tenantId, int $statusId): User
    {
        $password = $request->has('password')
            ? $request->password
            : Helper::generatePasswordFromName($request->first_name, $request->last_name);

        $userDto = new UserDto(
            tenant_id: $tenantId,
            status_id: $statusId,
            first_name: $request->first_name,
            middle_name: $request?->middle_name,
            last_name: $request->last_name,
            email: $request->email,
            phone_number: $request->phone_number,
            password: $password,
            role_ids: null,
        );

        $user = $this->userRepository->create($userDto);
        $user->assignRole(RoleEnum::STUDENT);

        return $user;
    }

    protected function createStudentApplication(CreateStudentApplicationRequest $request, int $userId): Student
    {
        $intakePeriod = Helper::resolveIntakePeriod();

        $dto = CreateStudentApplicationDto::fromCreateStudentApplicationRequest(
            $request,
            $userId,
            $intakePeriod->id
        );

        return $this->repository->create($dto);
    }

    /**
     * @throws Exception
     */
    private function invoiceStudent($user, CreateStudentApplicationRequest $request, $program): void
    {
        $feeType = PaymentHelper::getFeeTypeBySlug(FeeTypeEnum::APPLICATION_FEE->slug());
        $registrationFee = FeeStructure::where('fee_type_id', $feeType->id)->first();
        if (!$feeType || !$registrationFee) {
            throw new Exception('Required fee type or structure not found');
        }
        $systemReference = Helper::generateRandomCode('ORD');
        PaymentHelper::invoiceCashStudent($user, $feeType, $registrationFee->local_fca_amount, $systemReference, $request->payment_reference, $program);
    }

    /**
     * Evaluate student's current level, program count, and enrolment eligibility.
     */
    private function evaluateStudentEligibility(Student $student, bool $hasPaidApplicationFee): array
    {
        $currentLevel = $student->currentLevel();
        $currentProgramCount = $student->programs()?->count() ?? 0;

        $latestProgram = $student->programs()->latest()->first();
        $level = $latestProgram?->departmentLevel?->level;

        $eligibleForEnrolment = true;

        if ($level instanceof Level && $level->allowed_applications_per_level > 0) {
            $eligibleForEnrolment = $currentProgramCount < $level->allowed_applications_per_level;
        }

        return [$currentLevel, $currentProgramCount, $eligibleForEnrolment];
    }

    /**
     * Helper to build a standardized search response.
     */
    private function returnSearchResponse(
        $user = null,
        bool $hasPaidApplicationFee = false,
        bool $eligibleForEnrolment = true,
        $currentLevel = null,
        $currentProgramCount = null,
        string $message = null,
        int $status = 200,
        bool $hasAdminRole = false,
        int $studentId = null,
    )
    {
        return response()->json([
            'user' => $user,
            'studentId' => $studentId,
            'hasPaidApplicationFee' => $hasPaidApplicationFee,
            'hasAdminRole' => $hasAdminRole,
            'eligibleForEnrolment' => $eligibleForEnrolment,
            'currentLevel' => $currentLevel,
            'currentProgramCount' => $currentProgramCount,
            'statusCode' => $status,
            'message' => $message,
        ]);
    }
}
