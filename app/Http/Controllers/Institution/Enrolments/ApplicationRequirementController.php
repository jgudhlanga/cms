<?php

declare(strict_types=1);

namespace App\Http\Controllers\Institution\Enrolments;

use App\DTO\Institution\CourseRequirementsDto;
use App\DTO\Institution\DepartmentLevelRequirementsDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\CourseRequirementRequest;
use App\Http\Requests\Institution\DepartmentLevelRequirementRequest;
use App\Http\Resources\Institution\CourseRequirementResource;
use App\Http\Resources\Institution\DepartmentLevelRequirementResource;
use App\Http\Resources\Institution\DepartmentCourseResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Models\Applications\ApplicationCourseRequirement;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Services\Applications\ApplicationRequirementSyncService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationRequirementController extends Controller
{
    public function __construct(
        protected ApplicationRequirementSyncService $syncService,
    ) {}

    public function departmentRequirements(InstitutionDepartment $institution_department): Response
    {
        $this->authorize('manage:online-application-catalogue');

        $institution_department->load([
            'department',
            'departmentLevels.level',
            'departmentLevels.requirement',
            'departmentCourses.course',
            'departmentCourses.departmentCourseLevels',
        ]);

        $levelLinks = DepartmentLevelCourse::query()
            ->whereIn('department_level_id', $institution_department->departmentLevels->pluck('id'))
            ->get()
            ->groupBy('department_course_id');

        $courses = $institution_department->departmentCourses
            ->sortBy(fn ($dc) => (string) ($dc->course?->name ?? ''))
            ->values()
            ->map(function ($departmentCourse) use ($institution_department, $levelLinks) {
                $linkedLevelIds = collect($levelLinks->get($departmentCourse->id, []))
                    ->pluck('department_level_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $levels = $institution_department->departmentLevels
                    ->whereIn('id', $linkedLevelIds)
                    ->sortBy(fn ($dl) => (int) ($dl->level?->position ?? 0))
                    ->values()
                    ->map(fn ($dl) => [
                        'id' => (int) $dl->id,
                        'name' => (string) ($dl->level?->name ?? ''),
                    ])
                    ->all();

                return [
                    'id' => (int) $departmentCourse->id,
                    'name' => (string) ($departmentCourse->course?->name ?? ''),
                    'hasEnrolmentRequirements' => (bool) ($departmentCourse->course?->has_enrolment_requirements ?? false),
                    'levels' => $levels,
                ];
            })
            ->filter(fn (array $course) => $course['hasEnrolmentRequirements'] && $course['levels'] !== [])
            ->values()
            ->all();

        return Inertia::render('institution/enrolments/requirements/Index', [
            'department' => [
                'id' => (int) $institution_department->id,
                'name' => (string) ($institution_department->department?->name ?? ''),
                'departmentCode' => (string) ($institution_department->department_code ?? ''),
                'colorCode' => $institution_department->color_code,
            ],
            'levels' => $institution_department->departmentLevels
                ->sortBy(fn ($dl) => (int) ($dl->level?->position ?? 0))
                ->values()
                ->map(fn ($dl) => [
                    'id' => (int) $dl->id,
                    'name' => (string) ($dl->level?->name ?? ''),
                    'configured' => $dl->requirement !== null,
                ])
                ->all(),
            'courses' => $courses,
            'navigationDepartments' => $this->navigationDepartments(),
        ]);
    }

    public function levelRequirements(
        InstitutionDepartment $institution_department,
        DepartmentLevel $department_level,
    ): Response {
        $this->authorize('manage:online-application-catalogue');
        abort_unless((int) $department_level->institution_department_id === (int) $institution_department->id, 404);

        $department_level->loadMissing(['level', 'requirement']);
        $institution_department->loadMissing(['department', 'departmentLevels.level']);

        return Inertia::render('institution/enrolments/requirements/LevelRequirements', [
            'institutionDepartment' => InstitutionDepartmentResource::make($institution_department),
            'departmentLevel' => DepartmentLevelResource::make($department_level),
            'levels' => DepartmentLevelResource::collection($institution_department->departmentLevels),
            'requirements' => $department_level->requirement
                ? DepartmentLevelRequirementResource::make($department_level->requirement)
                : null,
        ]);
    }

    public function updateLevelRequirements(
        InstitutionDepartment $institution_department,
        DepartmentLevel $department_level,
        DepartmentLevelRequirementRequest $request,
    ): RedirectResponse {
        $this->authorize('manage:online-application-catalogue');

        $this->syncService->syncLevelRequirement(
            $institution_department,
            $department_level,
            DepartmentLevelRequirementsDto::fromDepartmentLevelRequirementRequest($request),
        );

        return redirect()
            ->route('application-requirements.department', $institution_department->id)
            ->with('success', __('application_requirements.saved'));
    }

    public function courseRequirements(
        InstitutionDepartment $institution_department,
        DepartmentCourse $department_course,
    ): Response {
        $this->authorize('manage:online-application-catalogue');
        abort_unless((int) $department_course->institution_department_id === (int) $institution_department->id, 404);
        $department_course->loadMissing('course');
        abort_unless((bool) ($department_course->course?->has_enrolment_requirements ?? false), 404);

        $department_course->loadMissing([
            'course',
            'institutionDepartment.department',
            'departmentCourseLevels.departmentLevel.level',
        ]);
        $institution_department->loadMissing(['department', 'departmentLevels.level']);

        $linkedLevelIds = $department_course->departmentCourseLevels->pluck('department_level_id')->map(fn ($id) => (int) $id);
        $allowedLevels = $institution_department->departmentLevels
            ->whereIn('id', $linkedLevelIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $departmentLevelId = (int) request()->integer('department_level_id', $allowedLevels[0] ?? 0);
        $requirement = $departmentLevelId > 0
            ? ApplicationCourseRequirement::query()
                ->where('department_course_id', $department_course->id)
                ->where('department_level_id', $departmentLevelId)
                ->first()
            : null;

        return Inertia::render('institution/enrolments/requirements/CourseRequirements', [
            'institutionDepartment' => InstitutionDepartmentResource::make($institution_department),
            'departmentCourse' => DepartmentCourseResource::make($department_course),
            'levels' => DepartmentLevelResource::collection(
                $institution_department->departmentLevels->whereIn('id', $linkedLevelIds),
            ),
            'allowedLevels' => $allowedLevels,
            'selectedDepartmentLevelId' => $departmentLevelId > 0 ? $departmentLevelId : null,
            'requirements' => $requirement ? CourseRequirementResource::make($requirement) : null,
        ]);
    }

    public function updateCourseRequirements(
        InstitutionDepartment $institution_department,
        DepartmentCourse $department_course,
        CourseRequirementRequest $request,
    ): RedirectResponse {
        $this->authorize('manage:online-application-catalogue');
        abort_unless((int) $department_course->institution_department_id === (int) $institution_department->id, 404);
        $department_course->loadMissing('course');
        abort_unless((bool) ($department_course->course?->has_enrolment_requirements ?? false), 404);

        $this->syncService->syncCourseRequirement(
            $institution_department,
            $department_course,
            CourseRequirementsDto::fromCourseRequirementRequest($request),
        );

        return redirect()
            ->route('application-requirements.department', $institution_department->id)
            ->with('success', __('application_requirements.saved'));
    }

    public function classSizes(InstitutionDepartment $institution_department): Response
    {
        $this->authorize('manage:online-application-catalogue');

        $institution_department->loadMissing(['department']);

        return Inertia::render('institution/enrolments/classSizes/Show', [
            'department' => InstitutionDepartmentResource::make($institution_department),
            'navigationDepartments' => $this->navigationDepartments(),
        ]);
    }

    /**
     * @return list<array{id: int, name: string, departmentCode: string, colorCode: string|null}>
     */
    private function navigationDepartments(): array
    {
        return InstitutionDepartment::query()
            ->with(['department'])
            ->whereHas('department', fn ($q) => $q->where('is_academic', true))
            ->orderBy('id')
            ->get()
            ->map(fn (InstitutionDepartment $department) => [
                'id' => (int) $department->id,
                'name' => (string) ($department->department?->name ?? ''),
                'departmentCode' => (string) ($department->department_code ?? ''),
                'colorCode' => $department->color_code,
            ])
            ->sortBy(fn (array $row) => mb_strtolower($row['name']), SORT_NATURAL)
            ->values()
            ->all();
    }
}
