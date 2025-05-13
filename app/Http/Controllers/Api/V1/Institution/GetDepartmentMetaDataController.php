<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Controller;
use App\Http\Resources\Institution\DepartmentCourseResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;

class GetDepartmentMetaDataController extends Controller
{

    public function __construct()
    {

    }

    public function __invoke(InstitutionDepartment $institutionDepartment)
    {
        $levels = DepartmentLevelResource::collection($institutionDepartment->departmentLevels);
        $courses = DepartmentCourseResource::collection($institutionDepartment->departmentCourses);
        $departmentLevelsIds = DepartmentLevel::all()->pluck('id');
        $departmentCoursesIds = DepartmentCourse::all()->pluck('id');
        return response()->json(compact('levels','departmentLevelsIds', 'courses', 'departmentCoursesIds'));
    }
}
