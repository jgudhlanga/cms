<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Helpers\EnrolmentHelper;
use App\Http\Controllers\Controller;
use App\Http\Filters\Institution\StaffFilter;
use App\Http\Resources\Institution\CourseSyllabusResource;
use App\Http\Resources\Institution\DepartmentCourseResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Http\Resources\Institution\IntakePeriodClassSizeResource;
use App\Http\Resources\Institution\ModeOfStudyResource;
use App\Http\Resources\Institution\StaffResource;
use App\Models\Institution\CourseLevelMode;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\ModeOfStudy;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Repositories\Institution\interface\IStaffRepository;
use App\Services\DepartmentEnrolmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DepartmentMetaDataController extends Controller
{
    public function __construct(
        protected IStaffRepository $staffRepository,
        protected DepartmentEnrolmentService $departmentEnrolmentService,
    ) {}

    public function courses(InstitutionDepartment $institutionDepartment): JsonResponse
    {
        $institutionDepartment->loadMissing('departmentCourses.course');

        $courses = DepartmentCourseResource::collection($institutionDepartment->departmentCourses);
        $departmentCoursesIds = $institutionDepartment->departmentCourses?->pluck('course_id');

        return response()->json(compact('courses', 'departmentCoursesIds'));
    }

    public function levels(InstitutionDepartment $institutionDepartment): JsonResponse
    {
        $institutionDepartment->loadMissing(['departmentLevels.level', 'departmentLevels.requirement']);

        $levels = DepartmentLevelResource::collection($institutionDepartment->departmentLevels);
        $departmentLevelsIds = $institutionDepartment?->departmentLevels?->pluck('level_id');
        $showOnCurrentApplicationPeriodIds = $institutionDepartment?->departmentLevels->where('show_on_current_application_period', true)->pluck('level_id');

        return response()->json(compact('levels', 'departmentLevelsIds', 'showOnCurrentApplicationPeriodIds'));
    }

    public function staff(StaffFilter $filters, InstitutionDepartment $institutionDepartment): AnonymousResourceCollection
    {
        return StaffResource::collection($this->staffRepository->allFilter(['*'], $filters))->additional([
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->staffRepository->allTrashed()->count(),
        ]);
    }

    public function modes(InstitutionDepartment $institutionDepartment): AnonymousResourceCollection
    {
        $courseIds = $institutionDepartment->departmentCourses()->pluck('id');

        if ($courseIds->isEmpty()) {
            return ModeOfStudyResource::collection(collect());
        }

        $modeIds = CourseLevelMode::query()
            ->whereIn('department_course_id', $courseIds)
            ->get()
            ->pluck('modes')
            ->flatten()
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($modeIds === []) {
            return ModeOfStudyResource::collection(collect());
        }

        return ModeOfStudyResource::collection(
            ModeOfStudy::query()->whereIn('id', $modeIds)->orderBy('name')->get(),
        );
    }

    public function classSizes(InstitutionDepartment $institutionDepartment): AnonymousResourceCollection
    {
        $filteredClassSizes = $institutionDepartment->intakeClassSizes
            ->when(request('intake_period'), function ($query, $intakePeriodId) {
                return $query->where('intake_period_id', $intakePeriodId);
            })
            ->when(request('mode_of_study'), function ($query, $modeOfStudyId) {
                return $query->where('mode_of_study_id', $modeOfStudyId);
            });

        return IntakePeriodClassSizeResource::collection($filteredClassSizes);
    }

    public function departmentEnrolments(InstitutionDepartment $institutionDepartment): JsonResponse
    {
        $intakePeriodId = request('intake_period_id') > 0 ? (int) request('intake_period_id') : null;
        $modeOfStudyId = request('mode_of_study_id') > 0 ? (int) request('mode_of_study_id') : null;

        $document = $this->departmentEnrolmentService->summariseDepartmentEnrolments(
            $institutionDepartment,
            $intakePeriodId,
            $modeOfStudyId,
        );

        return response()->json($document);
    }

    public function departmentClassLists(InstitutionDepartment $institutionDepartment): JsonResponse
    {
        $type = request()->string('type')->toString();
        $permission = EnrolmentHelper::classListBrowsePermissionForType($type !== '' ? $type : null);
        if ($permission === null) {
            abort(403);
        }
        $this->authorize($permission);

        $intakePeriodId = request('intake_period_id') > 0 ? (int) request('intake_period_id') : null;
        $modeOfStudyId = request('mode_of_study_id') > 0 ? (int) request('mode_of_study_id') : null;

        $document = $this->departmentEnrolmentService->summariseDepartmentEnrolments(
            $institutionDepartment,
            $intakePeriodId,
            $modeOfStudyId,
            $type,
        );

        return response()->json($document);
    }

    public function classConfigCourseSyllabuses(InstitutionDepartment $institutionDepartment): AnonymousResourceCollection
    {
        $validated = validator(request()->query(), [
            'department_course_id' => ['required', 'integer'],
            'department_level_id' => ['required', 'integer'],
        ])->validate();

        $departmentCourse = DepartmentCourse::query()
            ->whereKey((int) $validated['department_course_id'])
            ->where('institution_department_id', $institutionDepartment->id)
            ->firstOrFail();

        $departmentLevelCourse = DepartmentLevelCourse::query()
            ->where('department_course_id', $departmentCourse->id)
            ->where('department_level_id', (int) $validated['department_level_id'])
            ->first();

        if ($departmentLevelCourse === null) {
            return CourseSyllabusResource::collection(collect());
        }

        $syllabi = CourseSyllabus::query()
            ->where('institution_department_id', $institutionDepartment->id)
            ->where('department_level_course_id', $departmentLevelCourse->id)
            ->with([
                'departmentLevelCourse.departmentCourse.course',
                'departmentLevelCourse.departmentLevel.level',
            ])
            ->orderBy('code')
            ->get();

        return CourseSyllabusResource::collection($syllabi);
    }
}
