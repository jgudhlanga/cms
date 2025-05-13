<?php

namespace App\Http\Controllers\Institution;

use App\DTO\Institution\DepartmentCourseDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\DepartmentCourseRequest;
use App\Http\Resources\Institution\DepartmentCourseResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Institution\interface\IDepartmentCourseRepository;
use Inertia\Inertia;

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
