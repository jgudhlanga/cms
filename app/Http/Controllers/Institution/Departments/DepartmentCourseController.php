<?php

namespace App\Http\Controllers\Institution\Departments;

use App\DTO\Institution\DepartmentCourseDto;
use App\DTO\Institution\DepartmentCourseUpdateDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\CourseLevelModeRequest;
use App\Http\Requests\Institution\DepartmentCourseRequest;
use App\Http\Requests\Institution\DepartmentCourseUpdateRequest;
use App\Http\Resources\Institution\CourseLevelModeResource;
use App\Http\Resources\Institution\DepartmentCourseResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Http\Resources\Institution\ModeOfStudyResource;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\ModeOfStudy;
use App\Repositories\Institution\interface\IDepartmentCourseRepository;
use App\Services\Institution\ProgrammeLinkUsageGuard;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentCourseController extends Controller
{
    public function __construct(
        protected IDepartmentCourseRepository $repository,
        protected ProgrammeLinkUsageGuard $usageGuard,
    ) {}

    public function syncDepartmentCourses(InstitutionDepartment $institutionDepartment, DepartmentCourseRequest $request): void
    {
        $this->authorize('createDepartmentMetaData');
        $this->repository->syncDepartmentCourses($institutionDepartment, DepartmentCourseDto::fromDepartmentCourseRequest($request));
    }

    public function show(DepartmentCourse $departmentCourse)
    {
        $this->authorize('viewDepartmentMetaData');
        $departmentCourse = DepartmentCourseResource::make($departmentCourse);
        $institutionDepartment = InstitutionDepartmentResource::make($departmentCourse->institutionDepartment);
        $departmentLevels = DepartmentLevelResource::collection($departmentCourse->institutionDepartment->departmentLevels);
        $modes = ModeOfStudy::whereNull('deleted_at')->get();
        $modesOfStudy = ModeOfStudyResource::collection($modes);

        return Inertia::render('institution/departments/courses/Edit',
            compact('institutionDepartment', 'departmentCourse', 'departmentLevels', 'modesOfStudy'),
        );
    }

    public function courseLevelModes(DepartmentCourse $departmentCourse): Response
    {
        $this->authorize('viewDepartmentMetaData');

        $departmentCourse->loadMissing([
            'course',
            'institutionDepartment.department',
            'institutionDepartment.division',
            'institutionDepartment.departmentLevels.level',
            'departmentCourseLevels.departmentLevel.level',
            'departmentCourseLevels.departmentCourse.course',
            'courseLevelModes.departmentCourse.course',
            'courseLevelModes.departmentLevel.level',
        ]);

        $institutionDepartment = InstitutionDepartmentResource::make($departmentCourse->institutionDepartment);
        $courseLevels = $departmentCourse->departmentCourseLevels
            ->pluck('departmentLevel')
            ->filter()
            ->unique('id')
            ->sortBy(fn ($departmentLevel) => $departmentLevel->level?->position)
            ->values();
        $departmentLevels = DepartmentLevelResource::collection($courseLevels);
        $courseLevelModes = CourseLevelModeResource::collection($departmentCourse->courseLevelModes);
        $modesOfStudy = ModeOfStudyResource::collection(ModeOfStudy::whereNull('deleted_at')->get());
        $departmentCourse = DepartmentCourseResource::make($departmentCourse);

        return Inertia::render('institution/departments/courses/CourseLevelModes',
            compact('institutionDepartment', 'departmentCourse', 'departmentLevels', 'courseLevelModes', 'modesOfStudy'),
        );
    }

    public function storeCourseLevelModes(DepartmentCourse $departmentCourse, CourseLevelModeRequest $request): void
    {
        $this->authorize('updateDepartmentMetaData');
        foreach ($request->mode_ids as $levelId => $modes) {
            if (empty($modes)) {
                $departmentCourse->courseLevelModes()
                    ->where('department_level_id', $levelId)
                    ->delete();

                continue;
            }
            $departmentCourse->courseLevelModes()->updateOrCreate(
                ['department_level_id' => $levelId],
                ['modes' => array_values($modes)]
            );
        }
    }

    public function update(DepartmentCourse $departmentCourse, DepartmentCourseUpdateRequest $request): void
    {
        $this->authorize('updateDepartmentMetaData');
        $this->repository->update($departmentCourse, DepartmentCourseUpdateDto::fromDepartmentCourseUpdateRequest($request));
    }

    public function destroy(DepartmentCourse $departmentCourse)
    {
        $this->authorize('deleteDepartmentMetaData');
        $this->usageGuard->assertCoursesUnused([(int) $departmentCourse->id]);
        $this->repository->delete($departmentCourse);
    }

    public function restore(string $id)
    {
        $departmentCourse = $this->repository->findTrashed($id);
        $this->authorize('restoreDepartmentMetaData');
        $this->repository->restore($departmentCourse);
    }

    public function forceDelete(DepartmentCourse $departmentCourse)
    {
        $this->authorize('forceDeleteDepartmentMetaData');
        $this->usageGuard->assertCoursesUnused([(int) $departmentCourse->id]);
        $this->repository->delete($departmentCourse, true);
    }
}
