<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Controller;
use App\Http\Filters\Institution\StaffFilter;
use App\Http\Resources\Institution\DepartmentCourseResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Http\Resources\Institution\StaffResource;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Institution\interface\IStaffRepository;

class DepartmentMetaDataController extends Controller
{
    public function __construct(protected IStaffRepository $staffRepository)
    {
    }

    public function courses(InstitutionDepartment $institutionDepartment)
    {
        $courses = DepartmentCourseResource::collection($institutionDepartment->departmentCourses);
        $departmentCoursesIds = $institutionDepartment->departmentCourses?->pluck('course_id');
        return response()->json(compact('courses', 'departmentCoursesIds'));
    }

    public function levels(InstitutionDepartment $institutionDepartment)
    {
        $levels = DepartmentLevelResource::collection($institutionDepartment->departmentLevels);
        $departmentLevelsIds = $institutionDepartment?->departmentLevels?->pluck('level_id');
        return response()->json(compact('levels', 'departmentLevelsIds'));
    }

    public function staff(StaffFilter $filters, InstitutionDepartment $institutionDepartment)
    {
        $staff = StaffResource::collection($this->staffRepository->allFilter(['*'], $filters));
        return response()->json([
            'staff' => $staff,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->staffRepository->allTrashed()->count(),
        ]);
    }
}
