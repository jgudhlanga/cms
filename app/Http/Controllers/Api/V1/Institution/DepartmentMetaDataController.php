<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Controller;
use App\Http\Resources\Institution\DepartmentCourseResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Models\Institution\InstitutionDepartment;

class DepartmentMetaDataController extends Controller
{
    public function __construct()
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
}
