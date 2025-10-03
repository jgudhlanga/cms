<?php

namespace App\Http\Controllers\Institution\Departments;

use App\DTO\Institution\DepartmentCourseDto;
use App\DTO\Institution\DepartmentCourseUpdateDto;
use App\DTO\Institution\CourseRequirementsDto;
use App\Enums\Institution\LevelEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\DepartmentCourseRequest;
use App\Http\Requests\Institution\DepartmentCourseUpdateRequest;
use App\Http\Requests\Institution\CourseRequirementRequest;
use App\Http\Resources\Institution\CourseRequirementResource;
use App\Http\Resources\Institution\DepartmentCourseResource;
use App\Http\Resources\Institution\DepartmentLevelRequirementResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Repositories\Institution\interface\IDepartmentCourseRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentCourseController extends Controller
{
    public function __construct(protected IDepartmentCourseRepository $repository)
    {
    }

    public function syncDepartmentCourses(InstitutionDepartment $institutionDepartment, DepartmentCourseRequest $request): void
    {
        $this->authorize('createDepartmentMetaData');
        $this->repository->syncDepartmentCourses($institutionDepartment, DepartmentCourseDto::fromDepartmentCourseRequest($request));
    }

    public function courseRequirements(DepartmentCourse $departmentCourse): Response
    {
        $this->authorize('updateDepartmentMetaData');
        $requirements = $departmentCourse->requirement ? CourseRequirementResource::make($departmentCourse->requirement) : null;
        $departmentCourse = DepartmentCourseResource::make($departmentCourse);
        $institutionDepartment = InstitutionDepartmentResource::make($departmentCourse->institutionDepartment);
        $levels = DepartmentLevelResource::collection($departmentCourse->departmentCourseLevels);
        $allowedLevelIds = Level::whereIn('name', [LevelEnum::NC->name()])->pluck('id')->toArray();
        $allowedLevels = DepartmentLevel::where('institution_department_id', $departmentCourse?->institutionDepartment?->id)
            ->whereIn('level_id', $allowedLevelIds)->pluck('id')->toArray();
        return Inertia::render('institution/departments/courses/CourseRequirements',
            compact('departmentCourse', 'requirements', 'levels', 'institutionDepartment', 'allowedLevels'));
    }

    /**
     * @throws AuthorizationException
     */
    public function updateCourseRequirements(DepartmentCourse $departmentCourse, CourseRequirementRequest $request): void
    {
        $this->authorize('updateDepartmentMetaData');
        $this->repository->updateLevelCourseRequirements($departmentCourse, CourseRequirementsDto::fromCourseRequirementRequest($request));
    }

    public function show(DepartmentCourse $departmentCourse)
    {
        $this->authorize('viewDepartmentMetaData');
        $departmentCourse = DepartmentCourseResource::make($departmentCourse);
        $institutionDepartment = InstitutionDepartmentResource::make($departmentCourse->institutionDepartment);
        $departmentLevels = DepartmentLevelResource::collection($departmentCourse->institutionDepartment->departmentLevels);;
        return Inertia::render('institution/departments/courses/Edit',
            compact('institutionDepartment', 'departmentCourse', 'departmentLevels'),
        );
    }

    public function update(DepartmentCourse $departmentCourse, DepartmentCourseUpdateRequest $request): void
    {
        $this->authorize('updateDepartmentMetaData');
        $this->repository->update($departmentCourse, DepartmentCourseUpdateDto::fromDepartmentCourseUpdateRequest($request));
    }

    public function destroy(DepartmentCourse $departmentCourse)
    {
        $this->authorize('deleteDepartmentMetaData');
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
        $this->repository->delete($departmentCourse, true);
    }
}
