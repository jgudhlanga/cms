<?php

namespace App\Http\Controllers\Institution\Departments;

use App\DTO\Institution\DepartmentLevelDto;
use App\Helpers\WorkflowHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\DepartmentLevelRequest;
use App\Http\Resources\Enrolments\EnrolmentGroupResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Http\Resources\Institution\IntakePeriodResource;
use App\Http\Resources\Institution\ModeOfStudyResource;
use App\Http\Resources\Shared\WorkflowStepResource;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Institution\interface\IDepartmentLevelRepository;
use App\Services\DepartmentEnrolmentService;
use App\Services\Institution\ProgrammeLinkUsageGuard;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentLevelController extends Controller
{
    public function __construct(
        protected IDepartmentLevelRepository $repository,
        protected DepartmentEnrolmentService $departmentEnrolmentService,
        protected ProgrammeLinkUsageGuard $usageGuard,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function syncDepartmentLevels(InstitutionDepartment $institutionDepartment, DepartmentLevelRequest $request): void
    {
        $this->authorize('createDepartmentMetaData');
        $this->repository->syncDepartmentLevels($institutionDepartment, DepartmentLevelDto::fromDepartmentLevelRequest($request));
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(DepartmentLevel $departmentLevel): void
    {
        $this->authorize('deleteDepartmentMetaData');
        $this->usageGuard->assertLevelsUnused([(int) $departmentLevel->id]);
        $this->repository->delete($departmentLevel);
    }

    /**
     * @throws AuthorizationException
     */
    public function restore(string $id): void
    {
        $departmentLevel = $this->repository->findTrashed($id);
        $this->authorize('restoreDepartmentMetaData');
        $this->repository->restore($departmentLevel);
    }

    /**
     * @throws AuthorizationException
     */
    public function forceDelete(DepartmentLevel $departmentLevel): void
    {
        $this->authorize('forceDeleteDepartmentMetaData');
        $this->usageGuard->assertLevelsUnused([(int) $departmentLevel->id]);
        $this->repository->delete($departmentLevel, true);
    }

    /**
     * @throws AuthorizationException
     */
    public function enrolments(InstitutionDepartment $institutionDepartment, DepartmentLevel $departmentLevel): Response
    {
        $this->authorize('viewDepartmentMetaData');

        [$intakePeriod, $modeOfStudy, $courseId, $intakePeriods, $modesOfStudy] = $this->departmentEnrolmentService->resolveEnrolmentContext();

        $departmentCourse = $courseId
            ? DepartmentCourse::with(['course'])->find($courseId)
            : null;

        $departmentLevel->loadMissing('requirement');
        $includeOLevelResults = (bool) ($departmentLevel->requirement?->is_o_level_required);

        // ------------------------------------------------------------
        // 2. Query enrolments efficiently
        // ------------------------------------------------------------
        $results = $this->departmentEnrolmentService->queryEnrolments(
            $institutionDepartment->id,
            $departmentLevel->id,
            (int) $intakePeriod->id,
            (int) $modeOfStudy->id,
            $courseId,
            null,
            $includeOLevelResults,
        );

        // ------------------------------------------------------------
        // 3. Prepare data for Inertia
        // ------------------------------------------------------------
        return Inertia::render('institution/enrolments/CourseLevelEnrolments', [
            'department' => InstitutionDepartmentResource::make($institutionDepartment),
            'level' => DepartmentLevelResource::make($departmentLevel),
            'intakePeriod' => IntakePeriodResource::make($intakePeriod),
            'modeOfStudy' => ModeOfStudyResource::make($modeOfStudy),
            'workflowSteps' => WorkflowStepResource::collection(WorkflowHelper::getAllSteps()),
            'classSize' => $courseId
                ? $this->departmentEnrolmentService->getClassSize($institutionDepartment, $departmentLevel->id, $courseId, $intakePeriod->id, $modeOfStudy->id)
                : 0,
            'enrolments' => EnrolmentGroupResource::make($results),
            'modesOfStudy' => ModeOfStudyResource::collection($modesOfStudy),
            'intakePeriods' => IntakePeriodResource::collection($intakePeriods),
            'course' => $departmentCourse ? ['name' => $departmentCourse?->course?->name, 'department_course_id' => $courseId] : null,
        ]);
    }
}
