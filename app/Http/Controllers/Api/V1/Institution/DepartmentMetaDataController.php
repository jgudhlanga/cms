<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Enums\Shared\ClassListTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Filters\Institution\StaffFilter;
use App\Http\Resources\Institution\DepartmentCourseResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Http\Resources\Institution\InstitutionDepartmentWithWorkflowStepsResource;
use App\Http\Resources\Institution\IntakePeriodClassSizeResource;
use App\Http\Resources\Institution\StaffResource;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Institution\interface\IStaffRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DepartmentMetaDataController extends Controller
{
    public function __construct(protected IStaffRepository $staffRepository)
    {
    }

    public function courses(InstitutionDepartment $institutionDepartment): JsonResponse
    {
        $courses = DepartmentCourseResource::collection($institutionDepartment->departmentCourses);
        $departmentCoursesIds = $institutionDepartment->departmentCourses?->pluck('course_id');
        return response()->json(compact('courses', 'departmentCoursesIds'));
    }

    public function levels(InstitutionDepartment $institutionDepartment): JsonResponse
    {
        $levels = DepartmentLevelResource::collection($institutionDepartment->departmentLevels);
        $departmentLevelsIds = $institutionDepartment?->departmentLevels?->pluck('level_id');
        return response()->json(compact('levels', 'departmentLevelsIds'));
    }

    public function staff(StaffFilter $filters, InstitutionDepartment $institutionDepartment): AnonymousResourceCollection
    {
        return StaffResource::collection($this->staffRepository->allFilter(['*'], $filters))->additional([
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->staffRepository->allTrashed()->count(),
        ]);
    }

    public function workflowSteps(InstitutionDepartment $institutionDepartment): InstitutionDepartmentWithWorkflowStepsResource
    {
        return InstitutionDepartmentWithWorkflowStepsResource::make($institutionDepartment);
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
        $intakePeriodId = request('intake_period_id');
        $modeOfStudyId = request('mode_of_study_id');

        // Eager-load relationships to avoid N+1
        $enrolments = $institutionDepartment->enrolments()
            ->with(['departmentCourse', 'departmentLevel.level'])
            ->when($intakePeriodId, fn($q) => $q->where('intake_period_id', $intakePeriodId))
            ->when($modeOfStudyId, fn($q) => $q->where('mode_of_study_id', $modeOfStudyId))
            ->get();

        // Group by department_course_id
        $grouped = $enrolments->groupBy('department_course_id')->map(function ($courseGroup) use ($institutionDepartment) {
            $course = $courseGroup->first()->departmentCourse;

            // Group within each course by department_level_id
            $levels = $courseGroup->groupBy('department_level_id')->map(function ($levelGroup) {
                $level = $levelGroup->first()->departmentLevel;

                return [
                    'departmentLevelId' => $level->id,
                    'levelName' => $level->level->name ?? null,
                    'enrolmentsCount' => $levelGroup->count(),
                ];
            })->values(); // reset numeric keys

            return [
                'institutionDepartmentId' => $institutionDepartment->id,
                'departmentCourseId' => $course->id,
                'courseName' => $course?->course?->name,
                'levels' => $levels,
            ];
        })->values(); // reset numeric keys

        return response()->json($grouped);
    }

    public function departmentClassLists(InstitutionDepartment $institutionDepartment): JsonResponse
    {
        $intakePeriodId = request('intake_period_id');
        $modeOfStudyId = request('mode_of_study_id');

        // Eager-load relationships to avoid N+1
        $enrolments = $institutionDepartment->enrolments()
            ->join('class_lists', 'student_programs.id', '=', 'class_lists.student_program_id')
            ->with(['departmentCourse', 'departmentLevel.level'])
            ->when($intakePeriodId, fn($q) => $q->where('intake_period_id', $intakePeriodId))
            ->when($modeOfStudyId, fn($q) => $q->where('mode_of_study_id', $modeOfStudyId))
            ->whereIn('class_lists.type', [ClassListTypeEnum::PROVISIONAL->value, ClassListTypeEnum::FINAL->value])
            ->get();

        // Group by department_course_id
        $grouped = $enrolments->groupBy('department_course_id')->map(function ($courseGroup) use ($institutionDepartment) {
            $course = $courseGroup->first()->departmentCourse;

            // Group within each course by department_level_id
            $levels = $courseGroup->groupBy('department_level_id')->map(function ($levelGroup) {
                $level = $levelGroup->first()->departmentLevel;

                return [
                    'departmentLevelId' => $level->id,
                    'levelName' => $level->level->name ?? null,
                    'enrolmentsCount' => $levelGroup->count(),
                ];
            })->values(); // reset numeric keys

            return [
                'institutionDepartmentId' => $institutionDepartment->id,
                'departmentCourseId' => $course->id,
                'courseName' => $course?->course?->name,
                'levels' => $levels,
            ];
        })->values(); // reset numeric keys

        return response()->json($grouped);
    }
}
