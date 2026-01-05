<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Controller;
use App\Http\Resources\Institution\DepartmentLevelCourseResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use Illuminate\Http\Request;

class DepartmentLevelCourseController extends Controller
{
    public function index(DepartmentLevel $departmentLevel)
    {
        $courses = $departmentLevel->courses()
            ->join('department_courses', 'department_courses.id', '=', 'department_level_courses.department_course_id')
            ->where('department_courses.show_on_current_application_period', true)->get();
        //return DepartmentLevelCourseResource::collection($departmentLevel->courses);
        return DepartmentLevelCourseResource::collection($courses);
    }

    public function store(Request $request)
    {
    }

    public function show(string $id)
    {
    }

    public function update(Request $request, string $id)
    {
    }

    public function destroy(string $id)
    {
    }
}
