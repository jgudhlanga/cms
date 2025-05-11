<?php

namespace App\Http\Controllers\Institution;

use App\DTO\Institution\DepartmentCourseDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\DepartmentCourseRequest;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Institution\interface\IDepartmentCourseRepository;

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
