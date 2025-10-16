<?php

namespace App\Http\Controllers\Institution\Departments;

use App\Enums\Institution\ModeOfStudyEnum;
use App\Enums\Shared\GenderEnum;
use App\Models\Institution\ModeOfStudy;
use Closure;
use App\DTO\Institution\{DepartmentLevelDto, DepartmentLevelRequirementsDto};
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\{DepartmentLevelRequest, DepartmentLevelRequirementRequest};
use App\Http\Resources\Enrolments\EnrolmentResource;
use App\Http\Resources\Institution\{DepartmentLevelResource,
    InstitutionDepartmentResource,
    DepartmentApplicationStepResource,
    DepartmentLevelRequirementResource,
    IntakePeriodResource,
    ModeOfStudyResource
};
use App\Models\Institution\DepartmentLevel;
use App\Models\Students\StudentProgram;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Institution\interface\IDepartmentLevelRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;
use App\Helpers\WorkflowHelper;
use App\Models\Institution\IntakePeriod;

class DepartmentLevelController extends Controller
{
    public function __construct(protected IDepartmentLevelRepository $repository)
    {
    }

    /**
     * @throws AuthorizationException
     */
    public function departmentLevelRequirements(DepartmentLevel $departmentLevel): Response
    {
        $this->authorize('updateDepartmentMetaData');
        $departmentLevel = DepartmentLevelResource::make($departmentLevel);
        $institutionDepartment = InstitutionDepartmentResource::make($departmentLevel->institutionDepartment);
        $levels = DepartmentLevelResource::collection($institutionDepartment->departmentLevels);
        $requirements = $departmentLevel->requirement ? DepartmentLevelRequirementResource::make($departmentLevel->requirement) : null;
        return Inertia::render('institution/departments/DepartmentLevelRequirements',
            compact('departmentLevel', 'institutionDepartment', 'levels', 'requirements'));
    }

    /**
     * @throws AuthorizationException
     */
    public function updateDepartmentLevelRequirements(DepartmentLevel $departmentLevel, DepartmentLevelRequirementRequest $request): void
    {
        $this->authorize('updateDepartmentMetaData');
        $this->repository->updateDepartmentLevelRequirements($departmentLevel, DepartmentLevelRequirementsDto::fromDepartmentLevelRequirementRequest($request));
    }

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
        $this->repository->delete($departmentLevel, true);
    }

    /**
     * @throws AuthorizationException
     */
    public function enrolments(InstitutionDepartment $institutionDepartment, DepartmentLevel $departmentLevel): Response
    {
        $this->authorize('viewDepartmentMetaData');
        [$intakePeriodId, $modeOfStudyId, $courseId] = $this->extractFilters();

        $intakePeriod = $this->resolveIntakePeriod($intakePeriodId);
        $modeOfStudy = $this->resolveModeOfStudy($modeOfStudyId);

        $workflowSteps = DepartmentApplicationStepResource::collection(WorkflowHelper::getAllSteps($institutionDepartment->id));
        $maxStep = WorkflowHelper::getMaxStep($institutionDepartment->id);
        $classSize = $courseId ? $this->getClassSize($institutionDepartment, $departmentLevel->id, $courseId, $intakePeriod->id, $modeOfStudy->id) : 0;
        $enrolments = $this->fetchEnrolments($institutionDepartment, $departmentLevel, $intakePeriodId, $modeOfStudyId, $maxStep, $courseId);
        //$disabledEnrolments = $this->getEnrolmentsForDisabled($institutionDepartment, $departmentLevel, $intakePeriodId, $modeOfStudyId, $maxStep, $courseId);
        return Inertia::render('institution/enrolments/CourseLevelEnrolments', [
            'department' => InstitutionDepartmentResource::make($institutionDepartment),
            'level' => DepartmentLevelResource::make($departmentLevel),
            'intakePeriod' => IntakePeriodResource::make($intakePeriod),
            'modeOfStudy' => ModeOfStudyResource::make($modeOfStudy),
            'workflowSteps' => $workflowSteps,
            'classSize' => $classSize,
            'enrolments' => $enrolments,
            //'disabledEnrolments' => $disabledEnrolments,
        ]);
    }

    private function extractFilters(): array
    {
        $intakePeriodId = request('intake_period_id') > 0 ? (int)request('intake_period_id') : null;
        $modeOfStudyId = request('mode_of_study_id') > 0 ? (int)request('mode_of_study_id') : null;
        $courseId = request('department_course_id') > 0 ? (int)request('department_course_id') : null;

        return [$intakePeriodId, $modeOfStudyId, $courseId];
    }

    private function resolveIntakePeriod(?int $intakePeriodId)
    {
        return $intakePeriodId
            ? IntakePeriod::find($intakePeriodId)
            : IntakePeriod::orderByDesc('end_date')->first();
    }

    private function resolveModeOfStudy(?int $modeOfStudyId)
    {
        return $modeOfStudyId
            ? ModeOfStudy::find($modeOfStudyId)
            : ModeOfStudy::where('name', ModeOfStudyEnum::FULL_TIME->value)->first();
    }

    /*private function fetchEnrolments(InstitutionDepartment $institutionDepartment, DepartmentLevel $departmentLevel, ?int $intakePeriodId, ?int $modeOfStudyId, $maxStep, $courseId)
    {
        $query = $institutionDepartment->enrolments()
            ->where('department_level_id', $departmentLevel->id)
            ->whereHas('departmentWorkflowStep', fn($q) => $q->where('position', '<', $maxStep->position))
            ->when($intakePeriodId, fn($q) => $q->where('intake_period_id', $intakePeriodId))
            ->when($modeOfStudyId, fn($q) => $q->where('mode_of_study_id', $modeOfStudyId))
            ->when($courseId, fn($q) => $q->where('department_course_id', $courseId))
            ->with([
                'departmentWorkflowStep',
                'student.user',
                'institutionDepartment.department',
                'departmentLevel.level',
                'departmentCourse.course',
                'student.oLevelResults',
            ])
            ->orderBy('student_programs.created_at');

        return $query->get()
            ->groupBy(fn($enrolment) => $enrolment->departmentWorkflowStep->workflowStep->name)
            ->sortByDesc(fn($group) => $group->first()->departmentWorkflowStep->position ?? 0)
            ->map(fn($group) => EnrolmentResource::collection($group));
    }*/

    // Base reusable function
    private function fetchEnrolments(
        InstitutionDepartment $institutionDepartment,
        DepartmentLevel       $departmentLevel,
        ?int                  $intakePeriodId,
        ?int                  $modeOfStudyId,
                              $maxStep,
                              $courseId,
        ?Closure              $additionalFilter = null
    )
    {
        $query = $institutionDepartment->enrolments()
            ->where('department_level_id', $departmentLevel->id)
            ->whereHas('departmentWorkflowStep', fn($q) => $q->where('position', '<', $maxStep->position)
            )
            ->when($intakePeriodId, fn($q) => $q->where('intake_period_id', $intakePeriodId)
            )
            ->when($modeOfStudyId, fn($q) => $q->where('mode_of_study_id', $modeOfStudyId)
            )
            ->when($courseId, fn($q) => $q->where('department_course_id', $courseId)
            )
            ->with([
                'departmentWorkflowStep',
                'student.user',
                'institutionDepartment.department',
                'departmentLevel.level',
                'departmentCourse.course',
                'student.oLevelResults',
            ])
            ->orderBy('student_programs.created_at');

        // Apply additional filter if provided
        if ($additionalFilter) {
            $additionalFilter($query);
        }

        return $query->get()
            ->groupBy(fn($enrolment) => $enrolment->departmentWorkflowStep->workflowStep->name)
            ->sortByDesc(fn($group) => $group->first()->departmentWorkflowStep->position ?? 0)
            ->map(fn($group) => EnrolmentResource::collection($group));
    }

    public function getEnrolmentsForDisabled(
        InstitutionDepartment $institutionDepartment,
        DepartmentLevel       $departmentLevel,
        ?int                  $intakePeriodId,
        ?int                  $modeOfStudyId,
                              $maxStep,
                              $courseId
    )
    {
        return $this->fetchEnrolments(
            $institutionDepartment,
            $departmentLevel,
            $intakePeriodId,
            $modeOfStudyId,
            $maxStep,
            $courseId,
            fn($q) => $q->whereHas('student', fn($q2) =>
            $q2->where('disability_status', 'yes')
            )
        );
    }



    public function getMaleEnrolments(
        InstitutionDepartment $institutionDepartment,
        DepartmentLevel       $departmentLevel,
        ?int                  $intakePeriodId,
        ?int                  $modeOfStudyId,
                              $maxStep,
                              $courseId
    )
    {
        return $this->fetchEnrolments(
            $institutionDepartment,
            $departmentLevel,
            $intakePeriodId,
            $modeOfStudyId,
            $maxStep,
            $courseId,
            fn($q) => $q->whereHas('student.gender', fn($q2) => $q2->where('gender.name', GenderEnum::MALE->value))
        );
    }

    public function getFemaleEnrolments(
        InstitutionDepartment $institutionDepartment,
        DepartmentLevel       $departmentLevel,
        ?int                  $intakePeriodId,
        ?int                  $modeOfStudyId,
                              $maxStep,
                              $courseId
    )
    {
        return $this->fetchEnrolments(
            $institutionDepartment,
            $departmentLevel,
            $intakePeriodId,
            $modeOfStudyId,
            $maxStep,
            $courseId,
            fn($q) => $q->whereHas('student.gender', fn($q2) => $q2->where('gender.name', GenderEnum::FEMALE->value))
        );
    }


    private function getClassSize(InstitutionDepartment $institutionDepartment, $departmentLevelId, $departmentCourseId, $intakePeriodId, $modeOfStudyId): int
    {
        return $institutionDepartment->intakeClassSizes()
            ->where('department_level_id', $departmentLevelId)
            ->where('department_course_id', $departmentCourseId)
            ->where('intake_period_id', $intakePeriodId)
            ->where('intake_period_id', $modeOfStudyId)->pluck('class_size')->first() ?? 0;
    }
}
