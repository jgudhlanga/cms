<?php

namespace App\Http\Controllers\Students;

use App\Helpers\DropdownHelper;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Enrolments\BrowseDepartmentApplicationsRequest;
use App\Http\Requests\Students\UpdateStudentRequest;
use App\Http\Resources\Enrolments\DepartmentDistributionResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Http\Resources\Institution\IntakePeriodResource;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Repositories\Institution\interface\IDepartmentLevelRepository;
use App\Repositories\Students\interface\IStudentApplicationRepository;
use App\Services\ApplicationMetricsService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
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

    public function departmentEnrolments(
        BrowseDepartmentApplicationsRequest $request,
        InstitutionDepartment $institutionDepartment,
    ): Response {
        $validated = $request->validated();
        $intakePeriod = IntakePeriod::query()->find($validated['intake_period_id']);

        return Inertia::render('enrolments/DepartmentEnrolments', [
            'department' => InstitutionDepartmentResource::make($institutionDepartment),
            'intakePeriod' => $intakePeriod !== null ? IntakePeriodResource::make($intakePeriod) : null,
        ]);
    }
}
