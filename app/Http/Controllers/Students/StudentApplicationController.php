<?php

namespace App\Http\Controllers\Students;

use App\Enums\Institution\LevelEnum;
use App\Helpers\DropdownHelper;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\UpdateStudentRequest;
use App\Http\Resources\Enrolments\DepartmentDistributionResource;
use App\Http\Resources\Enrolments\EnrolmentResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Http\Resources\Institution\IntakePeriodResource;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use App\Repositories\Institution\interface\IDepartmentLevelRepository;
use App\Repositories\Students\interface\IStudentApplicationRepository;
use App\Services\ApplicationMetricsService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StudentApplicationController extends Controller
{
    public function __construct(
        protected IStudentApplicationRepository $repository,
        protected IDepartmentLevelRepository $departmentLevelRepository,
        protected ApplicationMetricsService $metricsService
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function index(): Response
    {
        $this->authorize('viewAny', StudentApplication::class);
        $intakePeriods = DropdownHelper::getIntakePeriods();
        $intakePeriod = Helper::resolveIntakePeriod();

        $departmentDistribution = DepartmentDistributionResource::collection($this->metricsService->applicationsByDepartment());

        return Inertia::render('enrolments/Index', [
            'departmentDistribution' => $departmentDistribution,
            'intakePeriods' => IntakePeriodResource::collection($intakePeriods),
            'intakePeriod' => IntakePeriodResource::make($intakePeriod),
        ]);
    }

    public function create() {}

    public function store(Request $request)
    {
        $this->authorize('create', StudentApplication::class);
        //
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(UpdateStudentRequest $request, Student $student): void
    {
        // $this->repository->update($student, UpdateStudentDto::fromUpdateStudentRequest($request));
    }

    public function destroy(string $id)
    {
        //
    }

    public function faultyApplications(): Response
    {
        $this->authorize('viewAny', StudentApplication::class);

        $isDepartmentUser = Helper::isDepartmentUser();
        $userDepartments = Helper::resolveUserDepartments();
        $allowedLevelIds = $this->getAllowedLevelIds();

        // Collect all the faulty cases
        $faultyCases = [
            'enrolmentWithoutOLevel' => $this->buildFaultyQuery(
                $this->getNoOLevelResultsIds($allowedLevelIds),
                $isDepartmentUser,
                $userDepartments
            ),
            'enrolmentWithFewerThanFive' => $this->buildFaultyQuery(
                $this->getOLevelResultsFewerThanFiveIds($allowedLevelIds),
                $isDepartmentUser,
                $userDepartments
            ),
            'noApplicationsFeePaid' => $this->buildFaultyQuery(
                $this->getNoReceiptsIds(),
                $isDepartmentUser,
                $userDepartments,
                ['student', 'departmentLevel', 'departmentCourse']
            ),
        ];

        // Transform and return
        return Inertia::render('enrolments/FaultyApplications', [
            'enrolmentWithoutOLevel' => EnrolmentResource::collection($faultyCases['enrolmentWithoutOLevel']),
            'enrolmentWithFewerThanFive' => EnrolmentResource::collection($faultyCases['enrolmentWithFewerThanFive']),
            'noApplicationsFeePaid' => EnrolmentResource::collection($faultyCases['noApplicationsFeePaid']),
        ]);
    }

    private function buildFaultyQuery(
        array $ids,
        bool $isDepartmentUser,
        ?array $userDepartments,
        array $relations = ['student'],
        int $perPage = 1000
    ) {
        $query = StudentApplication::with($relations)
            ->whereIn('id', $ids);

        if ($isDepartmentUser) {
            if (empty($userDepartments)) {
                $query->whereRaw('1 = 0'); // prevents any results
            } else {
                $query->whereIn('institution_department_id', $userDepartments);
            }
        }

        return $query->latest()->paginate($perPage);
    }

    private function getAllowedLevelIds()
    {
        return DepartmentLevel::whereHas('level', function ($query) {
            $query->whereIn('name', [
                LevelEnum::NC->name(),
                LevelEnum::ABMA_LEVEL_3->name(),
            ]);
        })->pluck('id');
    }

    private function getNoOLevelResultsIds($allowedLevelIds)
    {
        return StudentApplication::select(DB::raw('MAX(id) as id'))
            ->whereHas('student', function (Builder $query) {
                $query->whereDoesntHave('oLevelResults');
            })
            ->whereIn('department_level_id', $allowedLevelIds)
            ->groupBy('student_id')
            ->pluck('id')->toArray();
    }

    private function getOLevelResultsFewerThanFiveIds($allowedLevelIds)
    {
        return StudentApplication::select(DB::raw('MAX(id) as id'))
            ->whereHas('student', function (Builder $query) {
                $query->has('oLevelResults', '>=', 1)
                    ->has('oLevelResults', '<', 5);
            })
            ->whereIn('department_level_id', $allowedLevelIds)
            ->groupBy('student_id')
            ->pluck('id')->toArray();
    }

    private function getNoReceiptsIds()
    {
        return StudentApplication::whereNotExists(function ($query) {
            $query->selectRaw('1')
                ->from('ledgers')
                ->join('users', 'ledgers.ledgerable_id', '=', 'users.id')
                ->where('ledgers.ledgerable_type', User::class) // Only ledgers for User
                ->whereColumn('ledgers.ledgerable_id', 'users.id') // Match the User ID
                ->join('students', 'students.user_id', '=', 'users.id')
                ->whereColumn('students.id', 'student_applications.student_id')
                ->where('ledgers.type', 'receipt');
        })
            ->selectRaw('MAX(id) as id')
            ->groupBy('student_id')
            ->pluck('id')->toArray();
    }

    public function departmentEnrolments(InstitutionDepartment $institutionDepartment)
    {
        $this->authorize('viewAny', StudentApplication::class);
        $department = InstitutionDepartmentResource::make($institutionDepartment);

        return Inertia::render('enrolments/DepartmentEnrolments', [
            'department' => $department,
        ]);
    }
}
